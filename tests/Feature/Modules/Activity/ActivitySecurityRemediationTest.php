<?php

namespace Tests\Feature\Modules\Activity;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\BackdatePermission;
use App\Models\Modules\Activity\EmployeeTask;
use App\Models\Modules\Activity\TaskAttachment;
use App\Models\Modules\Activity\TaskComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ActivitySecurityRemediationTest extends TestCase
{
    use RefreshDatabase;

    private BusinessUnit $businessUnit;

    private BusinessUnit $otherBusinessUnit;

    private Department $department;

    private Department $otherDepartment;

    private Position $staffPosition;

    private User $staff;

    private User $owner;

    private User $foreignUser;

    private ActivityType $activityType;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false, 'features.backdate_approval' => true]);
        Notification::fake();

        $this->businessUnit = BusinessUnit::create(['name' => 'BU One', 'code' => 'BU1', 'is_active' => true]);
        $this->otherBusinessUnit = BusinessUnit::create(['name' => 'BU Two', 'code' => 'BU2', 'is_active' => true]);
        $this->department = Department::create([
            'business_unit_id' => $this->businessUnit->id, 'name' => 'Dept One', 'code' => 'D1', 'is_active' => true,
        ]);
        $this->otherDepartment = Department::create([
            'business_unit_id' => $this->otherBusinessUnit->id, 'name' => 'Dept Two', 'code' => 'D2', 'is_active' => true,
        ]);
        $this->staffPosition = Position::create([
            'department_id' => $this->department->id, 'name' => 'Staff', 'code' => 'STF',
            'level' => 'staff', 'access_level' => 'staff', 'hierarchy_level' => 3, 'is_active' => true,
        ]);
        $otherPosition = Position::create([
            'department_id' => $this->otherDepartment->id, 'name' => 'Other Staff', 'code' => 'OSTF',
            'level' => 'staff', 'access_level' => 'staff', 'hierarchy_level' => 3, 'is_active' => true,
        ]);
        $this->staff = $this->createUser('staff@example.test', $this->businessUnit, $this->department, $this->staffPosition);
        $this->owner = $this->createUser('owner@example.test', $this->businessUnit, $this->department, $this->staffPosition);
        $this->foreignUser = $this->createUser('foreign@example.test', $this->otherBusinessUnit, $this->otherDepartment, $otherPosition);
        $this->activityType = ActivityType::create([
            'code' => 'GEN', 'name' => 'General', 'color' => '#000000', 'is_active' => true, 'sort_order' => 1,
        ]);
        $this->department->activityTypes()->attach($this->activityType->id, ['sort_order' => 1]);
    }

    public function test_regular_staff_can_list_and_export_own_department_scope(): void
    {
        $this->asCurrent($this->staff)
            ->get(route('activity.task.index', ['scope' => 'department']))
            ->assertOk();

        $this->asCurrent($this->staff)
            ->get(route('activity.task.export', ['scope' => 'department']))
            ->assertOk();
    }

    public function test_forged_foreign_participant_is_rejected_and_participant_cannot_edit(): void
    {
        $task = $this->task($this->owner);
        $task->participants()->attach($this->staff->id, ['is_owner' => false, 'joined_at' => now()]);

        $this->asCurrent($this->staff)
            ->put(route('activity.task.update', $task), ['status' => 'in_progress'])
            ->assertForbidden();

        $this->asCurrent($this->owner)
            ->post(route('activity.task.store'), $this->taskPayload([
                'participant_ids' => [$this->foreignUser->id],
            ]))
            ->assertSessionHasErrors('participant_ids');
    }

    public function test_cross_business_unit_backdate_and_comment_access_are_denied(): void
    {
        $permission = BackdatePermission::create([
            'user_id' => $this->foreignUser->id,
            'department_id' => $this->otherDepartment->id,
            'business_unit_id' => $this->otherBusinessUnit->id,
            'requested_date' => now()->subWeek(),
            'reason' => 'Missed activity entry',
            'status' => 'pending',
        ]);
        $task = $this->task($this->foreignUser, $this->otherBusinessUnit, $this->otherDepartment);
        $task->participants()->attach($this->staff->id, ['is_owner' => false, 'joined_at' => now()]);

        $this->asCurrent($this->staff)
            ->post(route('activity.backdate.approve', $permission))
            ->assertRedirect();
        $this->assertSame('pending', $permission->fresh()->status);

        $this->asCurrent($this->staff)
            ->post(route('activity.task.comments.store', $task), ['body' => 'Cross tenant'])
            ->assertForbidden();
    }

    public function test_backdate_submission_requires_and_persists_requested_date(): void
    {
        $this->asCurrent($this->staff)
            ->post(route('activity.backdate.request.submit'), ['reason' => 'Missed activity entry'])
            ->assertSessionHasErrors('requested_date');

        $requestedDate = now()->subWeek()->toDateString();
        $this->asCurrent($this->staff)->post(route('activity.backdate.request.submit'), [
            'requested_date' => $requestedDate,
            'reason' => 'Missed activity entry',
        ])->assertRedirect();
        $permission = BackdatePermission::query()
            ->where('user_id', $this->staff->id)
            ->where('business_unit_id', $this->businessUnit->id)
            ->first();

        $this->assertNotNull($permission);
        $this->assertSame($requestedDate, $permission->requested_date->toDateString());
    }

    public function test_user_deletion_preserves_task_and_comment_history(): void
    {
        $task = $this->task($this->owner);
        $task->participants()->attach($this->owner->id, [
            'is_owner' => true,
            'joined_at' => now(),
            'participant_name' => $this->owner->name,
        ]);
        $comment = TaskComment::create([
            'employee_task_id' => $task->id, 'user_id' => $this->owner->id, 'body' => 'Historical note',
        ]);
        $attachment = TaskAttachment::create([
            'employee_task_id' => $task->id,
            'file_name' => 'historical.pdf',
            'file_path' => 'activity/historical.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 10,
            'uploaded_by' => $this->owner->id,
            'created_at' => now(),
        ]);
        $permission = BackdatePermission::create([
            'user_id' => $this->owner->id,
            'department_id' => $this->department->id,
            'business_unit_id' => $this->businessUnit->id,
            'requested_date' => now()->subWeek(),
            'reason' => 'Historical permission request',
            'status' => 'pending',
        ]);

        $this->owner->delete();

        $this->assertDatabaseHas('employee_tasks', ['id' => $task->id, 'created_by' => null]);
        $this->assertDatabaseHas('task_comments', ['id' => $comment->id, 'user_id' => null]);
        $this->assertDatabaseHas('task_attachments', ['id' => $attachment->id, 'uploaded_by' => null]);
        $this->assertDatabaseHas('task_participants', [
            'employee_task_id' => $task->id,
            'user_id' => null,
            'participant_name' => $this->owner->name,
        ]);
        $this->assertDatabaseHas('backdate_permissions', ['id' => $permission->id, 'user_id' => null]);
    }

    public function test_export_writes_formula_like_values_as_text(): void
    {
        $task = $this->task($this->staff);
        $task->update([
            'task_title' => '=HYPERLINK("https://example.test")',
            'notes' => '+CMD',
        ]);
        $this->activityType->update(['name' => '@FORMULA']);
        $this->staff->update(['name' => '-FORMULA']);
        $task->participants()->attach($this->staff->id, ['is_owner' => false, 'joined_at' => now()]);

        $response = $this->asCurrent($this->staff)
            ->get(route('activity.task.export', ['scope' => 'my']))
            ->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'activity-security-');
        file_put_contents($path, $response->streamedContent());
        $spreadsheet = IOFactory::load($path);
        $detail = $spreadsheet->getSheetByName('Detail');
        $raw = $spreadsheet->getSheetByName('Data Mentah');
        $breakdown = $spreadsheet->getSheetByName('Breakdown Kategori');
        $summary = $spreadsheet->getSheetByName('Ringkasan');
        @unlink($path);

        $this->assertStringStartsWith("'=HYPERLINK", (string) $detail?->getCell('C2')->getValue());
        $this->assertStringStartsWith("'+CMD", (string) $detail?->getCell('P2')->getValue());
        $this->assertStringStartsWith("'-FORMULA", (string) $detail?->getCell('R2')->getValue());
        $this->assertStringStartsWith("'=HYPERLINK", (string) $raw?->getCell('C2')->getValue());
        $this->assertStringStartsWith("'@FORMULA", (string) $breakdown?->getCell('A2')->getValue());
        $this->assertStringStartsWith("'@FORMULA", (string) $summary?->getCell('B10')->getValue());
    }

    public function test_task_modal_returns_latest_fifty_comments_in_chronological_order(): void
    {
        $task = $this->task($this->owner);
        foreach (range(1, 55) as $index) {
            TaskComment::create([
                'employee_task_id' => $task->id,
                'user_id' => $this->owner->id,
                'body' => "Comment {$index}",
                'created_at' => now()->addSeconds($index),
                'updated_at' => now()->addSeconds($index),
            ]);
        }

        $response = $this->asCurrent($this->owner)->get(route('activity.task.index', [
            'task' => $task->id, 'modal' => 'detail',
        ]))->assertOk();
        $comments = $response->viewData('page')['props']['selectedTask']['comments_data'];

        $this->assertCount(50, $comments);
        $this->assertSame('Comment 6', $comments[0]['body']);
        $this->assertSame('Comment 55', $comments[49]['body']);
    }

    private function asCurrent(User $user): static
    {
        return $this->actingAs($user)->withSession([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_department_id' => $this->department->id,
        ]);
    }

    private function createUser(string $email, BusinessUnit $businessUnit, Department $department, Position $position): User
    {
        $user = User::create([
            'name' => $email, 'email' => $email, 'password' => bcrypt('password'),
            'phone_number' => '08'.random_int(1000000000, 9999999999),
            'primary_department_id' => $department->id, 'primary_position_id' => $position->id,
            'global_role' => 'user', 'is_active' => true, 'email_verified_at' => now(),
        ]);
        $user->businessUnits()->create([
            'business_unit_id' => $businessUnit->id, 'department_id' => $department->id,
            'position_id' => $position->id, 'is_primary' => true, 'is_active' => true,
        ]);

        return $user;
    }

    private function task(User $owner, ?BusinessUnit $businessUnit = null, ?Department $department = null): EmployeeTask
    {
        return EmployeeTask::create([
            'business_unit_id' => ($businessUnit ?? $this->businessUnit)->id,
            'department_id' => ($department ?? $this->department)->id,
            'created_by' => $owner->id, 'activity_type_id' => $this->activityType->id,
            'task_title' => 'Security task', 'task_date' => now(), 'due_date' => now()->addDay(),
            'status' => 'planned', 'priority' => 'medium',
        ]);
    }

    private function taskPayload(array $overrides = []): array
    {
        return array_merge([
            'task_title' => 'New task', 'activity_type_id' => $this->activityType->id,
            'status' => 'planned', 'priority' => 'medium', 'task_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(), 'participant_ids' => [],
        ], $overrides);
    }
}
