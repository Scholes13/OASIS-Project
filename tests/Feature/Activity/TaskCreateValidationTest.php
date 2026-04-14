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
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskCreateValidationTest extends TestCase
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

        $this->user->update([
            'primary_position_id' => $this->position->id,
        ]);

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
    public function it_creates_planned_task_successfully()
    {
        $response = $this->actingAs($this->user)
            ->from(route('activity.task.create'))
            ->post(route('activity.task.store'), $this->validPayload([
                'status' => 'planned',
            ]));

        $response->assertRedirect(route('activity.task.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employee_tasks', [
            'task_title' => 'Task from test',
            'status' => 'planned',
            'created_by' => $this->user->id,
            'activity_type_id' => $this->activityType->id,
            'sub_activity_id' => $this->subActivity->id,
        ]);
    }

    #[Test]
    public function it_accepts_string_activity_type_id_when_creating_task()
    {
        $response = $this->actingAs($this->user)
            ->from(route('activity.task.create'))
            ->post(route('activity.task.store'), $this->validPayload([
                'activity_type_id' => (string) $this->activityType->id,
                'sub_activity_id' => (string) $this->subActivity->id,
            ]));

        $response->assertRedirect(route('activity.task.index'));
        $response->assertSessionHasNoErrors();
    }

    #[Test]
    public function it_redirects_back_to_origin_page_after_successful_create()
    {
        $response = $this->actingAs($this->user)
            ->from(route('activity.dashboard'))
            ->post(route('activity.task.store'), $this->validPayload([
                'status' => 'planned',
            ]));

        $response->assertRedirect(route('activity.dashboard'));
    }

    #[Test]
    public function it_creates_in_progress_task_and_sets_started_at()
    {
        $response = $this->actingAs($this->user)
            ->from(route('activity.task.create'))
            ->post(route('activity.task.store'), $this->validPayload([
                'status' => 'in_progress',
            ]));

        $response->assertRedirect(route('activity.task.index'));

        $task = EmployeeTask::latest('id')->first();

        $this->assertNotNull($task);
        $this->assertSame('in_progress', $task->status);
        $this->assertNotNull($task->started_at);
    }

    #[Test]
    public function it_creates_completed_task_with_duration()
    {
        $response = $this->actingAs($this->user)
            ->from(route('activity.task.create'))
            ->post(route('activity.task.store'), $this->validPayload([
                'status' => 'completed',
                'due_date' => null,
                'start_time' => '09:00',
                'end_time' => '10:30',
                'completed_date' => now()->format('Y-m-d'),
            ]));

        $response->assertRedirect(route('activity.task.index'));

        $task = EmployeeTask::latest('id')->first();

        $this->assertNotNull($task);
        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->started_at);
        $this->assertNotNull($task->completed_at);
        $this->assertSame($this->user->id, $task->completed_by);
        $this->assertSame(90, $task->duration_minutes);
    }

    #[Test]
    public function it_rejects_due_date_before_task_date()
    {
        $taskDate = now()->addDays(2)->format('Y-m-d');
        $dueDate = now()->addDay()->format('Y-m-d');

        $response = $this->actingAs($this->user)->post(route('activity.task.store'), $this->validPayload([
            'status' => 'planned',
            'task_date' => $taskDate,
            'due_date' => $dueDate,
        ]));

        $response->assertSessionHasErrors(['due_date']);
    }

    #[Test]
    public function it_requires_start_time_for_backdate_in_progress_task()
    {
        $response = $this->actingAs($this->user)->post(route('activity.task.store'), $this->validPayload([
            'status' => 'in_progress',
            'task_date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '',
        ]));

        $response->assertSessionHasErrors(['start_time']);
    }

    #[Test]
    public function it_requires_start_time_for_future_in_progress_task()
    {
        $response = $this->actingAs($this->user)->post(route('activity.task.store'), $this->validPayload([
            'status' => 'in_progress',
            'task_date' => now()->addDay()->format('Y-m-d'),
            'due_date' => now()->addDays(2)->format('Y-m-d'),
            'start_time' => '',
        ]));

        $response->assertSessionHasErrors(['start_time']);
    }

    #[Test]
    public function it_returns_validation_error_for_invalid_task_date_instead_of_throwing()
    {
        $response = $this->actingAs($this->user)->post(route('activity.task.store'), $this->validPayload([
            'task_date' => 'not-a-date',
        ]));

        $response->assertSessionHasErrors(['task_date']);
    }

    #[Test]
    public function it_rejects_completed_task_with_end_time_before_start_time()
    {
        $response = $this->actingAs($this->user)->post(route('activity.task.store'), $this->validPayload([
            'status' => 'completed',
            'due_date' => null,
            'start_time' => '10:00',
            'end_time' => '09:30',
            'completed_date' => now()->format('Y-m-d'),
        ]));

        $response->assertSessionHasErrors(['end_time']);
    }

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'task_title' => 'Task from test',
            'task_description' => 'Generated by test case',
            'activity_type_id' => $this->activityType->id,
            'sub_activity_id' => $this->subActivity->id,
            'status' => 'planned',
            'priority' => 'medium',
            'task_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDay()->format('Y-m-d'),
            'participant_ids' => [],
            'start_time' => '',
            'end_time' => '',
            'completed_date' => '',
        ], $overrides);
    }
}
