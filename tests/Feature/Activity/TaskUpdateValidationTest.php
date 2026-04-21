<?php

namespace Tests\Feature\Activity;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\EmployeeTask;
use App\Models\Modules\Activity\SubActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskUpdateValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected BusinessUnit $businessUnit;

    protected Department $department;

    protected ActivityType $activityType;

    protected SubActivity $subActivity;

    protected Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->businessUnit = BusinessUnit::create([
            'name' => 'Test Business Unit',
            'code' => 'TBU',
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'name' => 'Test Department',
            'code' => 'TDP',
            'business_unit_id' => $this->businessUnit->id,
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Task Tester',
            'username' => 'task.tester',
            'email' => 'task.tester@example.com',
            'phone_number' => '081234567800',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->position = Position::create([
            'department_id' => $this->department->id,
            'name' => 'Staff Test',
            'code' => 'STF_TEST',
            'level' => 'staff',
            'access_level' => 'staff',
            'hierarchy_level' => 3,
            'is_active' => true,
        ]);

        $this->user->update(['primary_position_id' => $this->position->id]);

        $this->user->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->activityType = ActivityType::create([
            'code' => 'PLAN',
            'name' => 'Planning',
            'color' => '#16599c',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->department->activityTypes()->attach($this->activityType->id, [
            'is_default' => true,
            'sort_order' => 1,
        ]);

        $this->subActivity = SubActivity::create([
            'activity_type_id' => $this->activityType->id,
            'code' => 'PLAN-01',
            'name' => 'Planning Review',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_department_id' => $this->department->id,
        ]);
    }

    #[Test]
    public function it_persists_task_date_on_full_update(): void
    {
        $task = $this->createTask(['status' => 'planned', 'task_date' => now()->format('Y-m-d')]);
        $newTaskDate = now()->addDays(2)->format('Y-m-d');
        $newDueDate = now()->addDays(3)->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->put(route('activity.task.update', $task), $this->updatePayload($task, [
                'task_date' => $newTaskDate,
                'due_date' => $newDueDate,
            ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $task->refresh();
        $this->assertEquals($newTaskDate, $task->task_date->format('Y-m-d'));
    }

    #[Test]
    public function it_notifies_only_newly_added_participants_when_updating_a_task(): void
    {
        Notification::fake();

        $existingParticipant = User::factory()->create([
            'primary_department_id' => $this->department->id,
            'email_verified_at' => now(),
        ]);
        $newParticipant = User::factory()->create([
            'primary_department_id' => $this->department->id,
            'email_verified_at' => now(),
        ]);

        foreach ([$existingParticipant, $newParticipant] as $participant) {
            $participant->businessUnits()->create([
                'business_unit_id' => $this->businessUnit->id,
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'is_primary' => true,
                'is_active' => true,
            ]);
        }

        $task = $this->createTask();
        $task->participants()->attach($existingParticipant->id, [
            'is_owner' => false,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('activity.task.update', $task), $this->updatePayload($task, [
                'participant_ids' => [$existingParticipant->id, $newParticipant->id],
            ]));

        $response->assertRedirect();

        Notification::assertCount(1);
    }

    #[Test]
    public function it_does_not_notify_when_participants_do_not_change(): void
    {
        Notification::fake();

        $existingParticipant = User::factory()->create([
            'primary_department_id' => $this->department->id,
            'email_verified_at' => now(),
        ]);

        $existingParticipant->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $task = $this->createTask();
        $task->participants()->attach($existingParticipant->id, [
            'is_owner' => false,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('activity.task.update', $task), $this->updatePayload($task, [
                'participant_ids' => [$existingParticipant->id],
            ]));

        $response->assertRedirect();

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_accepts_string_activity_type_id_when_updating_task(): void
    {
        $task = $this->createTask();

        $response = $this->actingAs($this->user)
            ->put(route('activity.task.update', $task), $this->updatePayload($task, [
                'activity_type_id' => (string) $this->activityType->id,
                'sub_activity_id' => (string) $this->subActivity->id,
            ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    #[Test]
    public function it_edits_in_progress_task_without_requiring_start_time_when_task_date_unchanged(): void
    {
        $taskDate = now()->format('Y-m-d');
        $task = $this->createTask(['status' => 'in_progress', 'task_date' => $taskDate, 'started_at' => now()->setTime(9, 0)]);

        $response = $this->actingAs($this->user)
            ->put(route('activity.task.update', $task), $this->updatePayload($task, [
                'task_title' => 'Updated title',
                'task_date' => $taskDate,
                'start_time' => null,
            ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('employee_tasks', ['id' => $task->id, 'task_title' => 'Updated title']);
    }

    #[Test]
    public function it_auto_shifts_started_at_date_when_task_date_changes_on_in_progress_task(): void
    {
        $startedAt = now()->setTime(9, 0);
        $task = $this->createTask(['status' => 'in_progress', 'task_date' => now()->format('Y-m-d'), 'started_at' => $startedAt]);
        $newTaskDate = now()->addDay()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->put(route('activity.task.update', $task), $this->updatePayload($task, ['task_date' => $newTaskDate, 'start_time' => null]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $task->refresh();
        $this->assertEquals($newTaskDate, $task->task_date->format('Y-m-d'));
        $this->assertEquals($newTaskDate, $task->started_at?->format('Y-m-d'));
        $this->assertEquals($startedAt->format('H:i:s'), $task->started_at?->format('H:i:s'));
    }

    #[Test]
    public function it_validates_due_date_against_submitted_task_date(): void
    {
        $task = $this->createTask(['status' => 'planned', 'task_date' => now()->format('Y-m-d'), 'due_date' => now()->addDay()->format('Y-m-d')]);
        $newTaskDate = now()->addDays(3)->format('Y-m-d');
        $invalidDueDate = now()->addDay()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->put(route('activity.task.update', $task), $this->updatePayload($task, ['task_date' => $newTaskDate, 'due_date' => $invalidDueDate]));

        $response->assertSessionHasErrors(['due_date']);
    }

    #[Test]
    public function it_blocks_quick_start_for_backdate_task(): void
    {
        $task = $this->createTask(['status' => 'planned', 'task_date' => now()->subDay()->format('Y-m-d')]);

        $response = $this->actingAs($this->user)->put(route('activity.task.update', $task), ['status' => 'in_progress']);

        $response->assertSessionHasErrors();
        $task->refresh();
        $this->assertEquals('planned', $task->status);
    }

    #[Test]
    public function it_blocks_quick_start_for_future_task(): void
    {
        $task = $this->createTask(['status' => 'planned', 'task_date' => now()->addDay()->format('Y-m-d')]);

        $response = $this->actingAs($this->user)->put(route('activity.task.update', $task), ['status' => 'in_progress']);

        $response->assertSessionHasErrors();
        $task->refresh();
        $this->assertEquals('planned', $task->status);
    }

    #[Test]
    public function it_allows_quick_start_for_today_task(): void
    {
        $task = $this->createTask(['status' => 'planned', 'task_date' => now()->format('Y-m-d')]);

        $response = $this->actingAs($this->user)->put(route('activity.task.update', $task), ['status' => 'in_progress']);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHasNoErrors();

        $task->refresh();
        $this->assertEquals('in_progress', $task->status);
        $this->assertNotNull($task->started_at);
    }

    #[Test]
    public function it_blocks_quick_complete_for_historical_task(): void
    {
        $task = $this->createTask(['status' => 'in_progress', 'task_date' => now()->subDay()->format('Y-m-d'), 'started_at' => now()->subDay()->setTime(9, 0)]);

        $response = $this->actingAs($this->user)->put(route('activity.task.update', $task), ['status' => 'completed']);

        $response->assertSessionHasErrors();
        $task->refresh();
        $this->assertEquals('in_progress', $task->status);
    }

    #[Test]
    public function it_allows_quick_complete_for_today_task_started_today(): void
    {
        $task = $this->createTask(['status' => 'in_progress', 'task_date' => now()->format('Y-m-d'), 'started_at' => now()->setTime(9, 0)]);

        $response = $this->actingAs($this->user)->put(route('activity.task.update', $task), ['status' => 'completed']);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHasNoErrors();

        $task->refresh();
        $this->assertEquals('completed', $task->status);
        $this->assertNotNull($task->completed_at);
    }

    #[Test]
    public function it_preserves_timestamps_when_status_changes_to_cancelled(): void
    {
        $startedAt = now()->setTime(9, 0);
        $task = $this->createTask(['status' => 'in_progress', 'task_date' => now()->format('Y-m-d'), 'started_at' => $startedAt]);

        $response = $this->actingAs($this->user)->put(route('activity.task.update', $task), ['status' => 'cancelled']);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $task->refresh();
        $this->assertEquals('cancelled', $task->status);
        $this->assertEquals($startedAt->format('Y-m-d H:i:s'), $task->started_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_requires_confirmation_before_resetting_started_task_to_planned(): void
    {
        $task = $this->createTask([
            'status' => 'in_progress',
            'task_date' => now()->format('Y-m-d'),
            'started_at' => now()->setTime(9, 0),
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('activity.task.update', $task), $this->updatePayload($task, [
                'status' => 'planned',
                'confirm_reset_execution' => false,
            ]));

        $response->assertSessionHasErrors(['status']);
    }

    #[Test]
    public function it_allows_resetting_started_task_to_planned_after_confirmation(): void
    {
        $task = $this->createTask([
            'status' => 'in_progress',
            'task_date' => now()->format('Y-m-d'),
            'started_at' => now()->setTime(9, 0),
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('activity.task.update', $task), $this->updatePayload($task, [
                'status' => 'planned',
                'confirm_reset_execution' => true,
            ]));

        $response->assertRedirect();

        $task->refresh();
        $this->assertSame('planned', $task->status);
        $this->assertNull($task->started_at);
    }

    #[Test]
    public function it_requires_confirmation_for_status_only_reset_to_planned(): void
    {
        $task = $this->createTask([
            'status' => 'in_progress',
            'task_date' => now()->format('Y-m-d'),
            'started_at' => now()->setTime(9, 0),
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('activity.task.update', $task), [
                'status' => 'planned',
                'confirm_reset_execution' => false,
            ]);

        $response->assertSessionHasErrors(['status']);

        $task->refresh();
        $this->assertSame('in_progress', $task->status);
        $this->assertNotNull($task->started_at);
    }

    #[Test]
    public function it_clears_execution_history_for_status_only_reset_to_planned_after_confirmation(): void
    {
        $task = $this->createTask([
            'status' => 'completed',
            'task_date' => now()->format('Y-m-d'),
            'started_at' => now()->setTime(9, 0),
            'completed_at' => now()->setTime(10, 0),
            'completed_by' => $this->user->id,
            'duration_minutes' => 60,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('activity.task.update', $task), [
                'status' => 'planned',
                'confirm_reset_execution' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $task->refresh();
        $this->assertSame('planned', $task->status);
        $this->assertNull($task->started_at);
        $this->assertNull($task->completed_at);
        $this->assertNull($task->completed_by);
        $this->assertNull($task->duration_minutes);
    }

    #[Test]
    public function it_updates_due_date_and_status_when_partial_payload_contains_both(): void
    {
        $task = $this->createTask([
            'status' => 'planned',
            'task_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDay()->format('Y-m-d'),
        ]);
        $newDueDate = now()->addDays(3)->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->put(route('activity.task.update', $task), [
                'status' => 'in_progress',
                'due_date' => $newDueDate,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $task->refresh();
        $this->assertSame('in_progress', $task->status);
        $this->assertSame($newDueDate, $task->due_date?->format('Y-m-d'));
        $this->assertNotNull($task->started_at);
    }

    #[Test]
    public function it_edits_completed_task_without_requiring_times_when_dates_unchanged(): void
    {
        $task = $this->createTask([
            'status' => 'completed',
            'task_date' => now()->format('Y-m-d'),
            'started_at' => now()->setTime(9, 0),
            'completed_at' => now()->setTime(11, 0),
            'completed_by' => $this->user->id,
            'duration_minutes' => 120,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('activity.task.update', $task), $this->updatePayload($task, [
                'task_title' => 'Updated completed task',
                'start_time' => null,
                'end_time' => null,
                'completed_date' => null,
            ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('employee_tasks', ['id' => $task->id, 'task_title' => 'Updated completed task']);
    }

    #[Test]
    public function it_does_not_require_start_time_when_completed_task_date_matches_existing_started_at_date(): void
    {
        $startedAt = now()->subDay()->setTime(9, 0);
        $completedAt = now()->setTime(11, 0);

        $task = $this->createTask([
            'status' => 'completed',
            'task_date' => now()->format('Y-m-d'),
            'due_date' => null,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'completed_by' => $this->user->id,
            'duration_minutes' => $startedAt->diffInMinutes($completedAt),
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('activity.task.update', $task), $this->updatePayload($task, [
                'task_title' => 'Completed task aligned to historical start',
                'task_date' => $startedAt->format('Y-m-d'),
                'completed_date' => $completedAt->format('Y-m-d'),
                'due_date' => null,
                'start_time' => null,
                'end_time' => null,
            ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $task->refresh();
        $this->assertSame($startedAt->format('Y-m-d H:i:s'), $task->started_at?->format('Y-m-d H:i:s'));
        $this->assertSame('Completed task aligned to historical start', $task->task_title);
    }

    #[Test]
    public function it_returns_validation_error_for_invalid_task_date_on_update_instead_of_throwing(): void
    {
        $task = $this->createTask();

        $response = $this->actingAs($this->user)
            ->put(route('activity.task.update', $task), $this->updatePayload($task, [
                'task_date' => 'not-a-date',
            ]));

        $response->assertSessionHasErrors(['task_date']);
    }

    protected function createTask(array $attributes = []): EmployeeTask
    {
        $task = EmployeeTask::create(array_merge([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'activity_type_id' => $this->activityType->id,
            'sub_activity_id' => $this->subActivity->id,
            'task_title' => 'Test Task',
            'task_description' => 'Test Description',
            'status' => 'planned',
            'priority' => 'medium',
            'task_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDay()->format('Y-m-d'),
        ], $attributes));

        $task->participants()->attach($this->user->id, ['is_owner' => true, 'joined_at' => now()]);

        return $task->fresh();
    }

    protected function updatePayload(EmployeeTask $task, array $overrides = []): array
    {
        return array_merge([
            'task_title' => $task->task_title,
            'task_description' => $task->task_description,
            'activity_type_id' => $task->activity_type_id,
            'sub_activity_id' => $task->sub_activity_id,
            'status' => $task->status,
            'priority' => $task->priority,
            'task_date' => $task->task_date->format('Y-m-d'),
            'due_date' => $task->due_date?->format('Y-m-d'),
            'participant_ids' => [],
            'start_time' => null,
            'end_time' => null,
            'completed_date' => null,
            'confirm_reset_execution' => false,
        ], $overrides);
    }
}
