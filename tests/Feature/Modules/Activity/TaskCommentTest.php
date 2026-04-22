<?php

namespace Tests\Feature\Modules\Activity;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\EmployeeTask;
use App\Models\Modules\Activity\TaskComment;
use App\Notifications\Activity\TaskCommentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskCommentTest extends TestCase
{
    use RefreshDatabase;

    protected User $creator;

    protected User $participant;

    protected User $outsider;

    protected EmployeeTask $task;

    protected BusinessUnit $businessUnit;

    protected Department $department;

    protected ActivityType $activityType;

    protected Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->businessUnit = BusinessUnit::create([
            'name' => 'Test BU',
            'code' => 'TBU',
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'name' => 'Test Dept',
            'code' => 'TDP',
            'business_unit_id' => $this->businessUnit->id,
            'is_active' => true,
        ]);

        $this->position = Position::create([
            'department_id' => $this->department->id,
            'name' => 'Staff',
            'code' => 'STF',
            'level' => 'staff',
            'access_level' => 'staff',
            'hierarchy_level' => 3,
            'is_active' => true,
        ]);

        $this->creator = $this->createUser('creator');
        $this->participant = $this->createUser('participant');
        $this->outsider = $this->createUser('outsider');

        $this->activityType = ActivityType::create([
            'code' => 'GEN',
            'name' => 'General',
            'color' => '#000000',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->task = EmployeeTask::create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'created_by' => $this->creator->id,
            'activity_type_id' => $this->activityType->id,
            'task_title' => 'Test Task',
            'task_date' => now(),
            'status' => 'in_progress',
            'priority' => 'medium',
        ]);

        // Attach creator as owner participant
        $this->task->participants()->attach($this->creator->id, [
            'is_owner' => true,
            'joined_at' => now(),
        ]);

        // Attach participant
        $this->task->participants()->attach($this->participant->id, [
            'is_owner' => false,
            'joined_at' => now(),
        ]);
    }

    protected function createUser(string $prefix): User
    {
        $user = User::create([
            'name' => ucfirst($prefix).' User',
            'username' => $prefix.'.user',
            'email' => $prefix.'@example.com',
            'phone_number' => '08'.rand(1000000000, 9999999999),
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->position->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        return $user;
    }

    protected function commentRoute(string $action, ?TaskComment $comment = null): string
    {
        if ($action === 'store') {
            return route('activity.task.comments.store', ['task' => $this->task->id]);
        }

        return route("activity.task.comments.{$action}", [
            'task' => $this->task->id,
            'comment' => $comment->id,
        ]);
    }

    // ==================== STORE TESTS ====================

    #[Test]
    public function participant_can_post_comment(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->participant)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->post($this->commentRoute('store'), ['body' => 'Hello from participant']);

        $response->assertRedirect();

        $this->assertDatabaseHas('task_comments', [
            'employee_task_id' => $this->task->id,
            'user_id' => $this->participant->id,
            'body' => 'Hello from participant',
        ]);
    }

    #[Test]
    public function creator_can_post_comment(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->creator)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->post($this->commentRoute('store'), ['body' => 'Hello from creator']);

        $response->assertRedirect();

        $this->assertDatabaseHas('task_comments', [
            'employee_task_id' => $this->task->id,
            'user_id' => $this->creator->id,
            'body' => 'Hello from creator',
        ]);
    }

    #[Test]
    public function non_participant_denied(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->outsider)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->post($this->commentRoute('store'), ['body' => 'Should be denied']);

        $response->assertForbidden();
    }

    #[Test]
    public function cancelled_task_rejects_comment(): void
    {
        Notification::fake();

        $this->task->update(['status' => 'cancelled']);

        $response = $this->actingAs($this->creator)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->post($this->commentRoute('store'), ['body' => 'Should fail']);

        $response->assertForbidden();
    }

    #[Test]
    public function whitespace_only_comment_rejected(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->creator)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->post($this->commentRoute('store'), ['body' => '   ']);

        $response->assertSessionHasErrors('body');
    }

    // ==================== UPDATE TESTS ====================

    #[Test]
    public function author_can_edit_own_comment(): void
    {
        $comment = TaskComment::create([
            'employee_task_id' => $this->task->id,
            'user_id' => $this->creator->id,
            'body' => 'Original body',
        ]);

        $response = $this->actingAs($this->creator)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->put($this->commentRoute('update', $comment), ['body' => 'Updated body']);

        $response->assertRedirect();

        $comment->refresh();
        $this->assertSame('Updated body', $comment->body);
        $this->assertNotNull($comment->edited_at);
    }

    #[Test]
    public function author_can_delete_own_comment(): void
    {
        $comment = TaskComment::create([
            'employee_task_id' => $this->task->id,
            'user_id' => $this->creator->id,
            'body' => 'To be deleted',
        ]);

        $response = $this->actingAs($this->creator)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->delete($this->commentRoute('destroy', $comment));

        $response->assertRedirect();

        // Soft deleted - still in DB with deleted_at set
        $this->assertSoftDeleted('task_comments', ['id' => $comment->id]);
    }

    #[Test]
    public function non_author_cannot_edit(): void
    {
        $comment = TaskComment::create([
            'employee_task_id' => $this->task->id,
            'user_id' => $this->creator->id,
            'body' => 'Creator comment',
        ]);

        $response = $this->actingAs($this->participant)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->put($this->commentRoute('update', $comment), ['body' => 'Hijack attempt']);

        $response->assertForbidden();
    }

    #[Test]
    public function non_author_cannot_delete(): void
    {
        $comment = TaskComment::create([
            'employee_task_id' => $this->task->id,
            'user_id' => $this->creator->id,
            'body' => 'Creator comment',
        ]);

        $response = $this->actingAs($this->participant)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->delete($this->commentRoute('destroy', $comment));

        $response->assertForbidden();
    }

    // ==================== NOTIFICATION TESTS ====================

    #[Test]
    public function notification_dispatched_to_participants_except_commenter(): void
    {
        Notification::fake();

        $this->actingAs($this->creator)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->post($this->commentRoute('store'), ['body' => 'Notify test']);

        // Participant should be notified
        Notification::assertSentTo($this->participant, TaskCommentNotification::class);

        // Creator (commenter) should NOT be notified
        Notification::assertNotSentTo($this->creator, TaskCommentNotification::class);
    }

    #[Test]
    public function notification_payload_matches_center_contract(): void
    {
        Notification::fake();

        $this->actingAs($this->creator)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->post($this->commentRoute('store'), ['body' => 'Contract test comment']);

        Notification::assertSentTo(
            $this->participant,
            TaskCommentNotification::class,
            function (TaskCommentNotification $notification) {
                $payload = $notification->toArray($this->participant);

                $this->assertSame('activity', $payload['category']);
                $this->assertSame('task_comment', $payload['event']);
                $this->assertSame('task_comment', $payload['type']);
                $this->assertStringContainsString($this->creator->name, $payload['title']);
                $this->assertStringContainsString($this->task->task_title, $payload['title']);
                $this->assertSame('Contract test comment', $payload['message']);
                $this->assertSame(route('activity.task.show', $this->task), $payload['action_url']);
                $this->assertSame('normal', $payload['priority']);
                $this->assertArrayHasKey('occurred_at', $payload);
                $this->assertSame($this->creator->id, $payload['actor']['id']);
                $this->assertSame($this->creator->name, $payload['actor']['name']);
                $this->assertSame('activity_task', $payload['entity']['type']);
                $this->assertSame($this->task->id, $payload['entity']['id']);
                $this->assertSame($this->task->task_title, $payload['entity']['title']);

                return true;
            }
        );
    }
}
