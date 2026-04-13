<?php

namespace Tests\Feature\Modules\Activity;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ActivityMemberFocusFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $viewer;

    protected User $memberA;

    protected User $memberB;

    protected User $inactiveMember;

    protected BusinessUnit $bu;

    protected Department $dept;

    protected Department $otherDept;

    protected Position $position;

    protected ActivityType $activityType;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);

        $this->bu = BusinessUnit::create([
            'name' => 'Test BU',
            'code' => 'TBU',
            'is_active' => true,
        ]);

        $this->dept = Department::create([
            'business_unit_id' => $this->bu->id,
            'name' => 'Test Department',
            'code' => 'TDP',
            'is_active' => true,
        ]);

        $this->otherDept = Department::create([
            'business_unit_id' => $this->bu->id,
            'name' => 'Other Department',
            'code' => 'ODP',
            'is_active' => true,
        ]);

        $this->position = Position::create([
            'department_id' => $this->dept->id,
            'name' => 'Staff',
            'code' => 'STAFF',
            'level' => 'staff',
            'access_level' => 'staff',
            'hierarchy_level' => 1,
            'is_active' => true,
        ]);

        $otherPosition = Position::create([
            'department_id' => $this->otherDept->id,
            'name' => 'Other Staff',
            'code' => 'OSTAFF',
            'level' => 'staff',
            'access_level' => 'staff',
            'hierarchy_level' => 1,
            'is_active' => true,
        ]);

        $this->viewer = $this->createUserWithAssignment('Viewer User', 'viewer@example.test', $this->dept, $this->position);
        $this->memberA = $this->createUserWithAssignment('Member A', 'member-a@example.test', $this->dept, $this->position);
        $this->memberB = $this->createUserWithAssignment('Member B', 'member-b@example.test', $this->dept, $this->position);
        $this->inactiveMember = $this->createUserWithAssignment('Inactive Member', 'inactive@example.test', $this->dept, $this->position, false);
        $this->createUserWithAssignment('Outside User', 'outside@example.test', $this->otherDept, $otherPosition);

        $this->activityType = ActivityType::create([
            'code' => 'PLAN',
            'name' => 'Planning',
            'color' => '#16599c',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->dept->activityTypes()->attach($this->activityType->id, [
            'is_default' => true,
            'sort_order' => 1,
        ]);

        session([
            'current_business_unit_id' => $this->bu->id,
            'current_department_id' => $this->dept->id,
        ]);
    }

    public function test_task_index_filters_by_creator_when_member_focus_active(): void
    {
        $taskByMemberA = $this->createTask($this->memberA, [
            'task_title' => 'Task created by Member A',
        ]);

        $this->createTask($this->memberB, [
            'task_title' => 'Task created by Member B',
        ]);

        $response = $this->actingAs($this->viewer)
            ->get(route('activity.task.index', [
                'scope' => 'department',
                'member_user_id' => (string) $this->memberA->id,
            ]));

        $response->assertOk();
        $tasks = $response->viewData('page')['props']['tasks']['data'];
        $stats = $response->viewData('page')['props']['stats'];

        $this->assertCount(1, $tasks);
        $this->assertSame($taskByMemberA->id, $tasks[0]['id']);
        $this->assertSame(1, $stats['total']);
    }

    public function test_task_index_filters_by_participant_when_member_focus_active(): void
    {
        $taskWithMemberA = $this->createTask($this->viewer, [
            'task_title' => 'Task with Member A',
        ]);
        $taskWithMemberA->participants()->attach($this->memberA->id, ['joined_at' => now()]);

        $taskWithMemberB = $this->createTask($this->viewer, [
            'task_title' => 'Task with Member B',
        ]);
        $taskWithMemberB->participants()->attach($this->memberB->id, ['joined_at' => now()]);

        $response = $this->actingAs($this->viewer)
            ->get(route('activity.task.index', [
                'scope' => 'department',
                'member_user_id' => (string) $this->memberA->id,
            ]));

        $response->assertOk();
        $tasks = $response->viewData('page')['props']['tasks']['data'];

        $this->assertCount(1, $tasks);
        $this->assertSame($taskWithMemberA->id, $tasks[0]['id']);
    }

    public function test_task_index_does_not_double_count_when_member_is_creator_and_participant(): void
    {
        $task = $this->createTask($this->memberA, [
            'task_title' => 'Task counted once',
        ]);
        $task->participants()->attach($this->memberA->id, [
            'joined_at' => now(),
            'is_owner' => true,
        ]);

        $response = $this->actingAs($this->viewer)
            ->get(route('activity.task.index', [
                'scope' => 'department',
                'member_user_id' => (string) $this->memberA->id,
            ]));

        $response->assertOk();
        $tasks = $response->viewData('page')['props']['tasks']['data'];
        $stats = $response->viewData('page')['props']['stats'];

        $this->assertCount(1, $tasks);
        $this->assertSame($task->id, $tasks[0]['id']);
        $this->assertSame(1, $stats['total']);
    }

    public function test_task_index_ignores_member_filter_when_scope_is_my(): void
    {
        $taskByViewer = $this->createTask($this->viewer, [
            'task_title' => 'Task by viewer',
        ]);

        $this->createTask($this->memberA, [
            'task_title' => 'Task by Member A',
        ]);

        $response = $this->actingAs($this->viewer)
            ->get(route('activity.task.index', [
                'scope' => 'my',
                'member_user_id' => (string) $this->memberA->id,
            ]));

        $response->assertOk();
        $tasks = $response->viewData('page')['props']['tasks']['data'];
        $filters = $response->viewData('page')['props']['filters'];

        $this->assertCount(1, $tasks);
        $this->assertSame($taskByViewer->id, $tasks[0]['id']);
        $this->assertSame('', $filters['member_user_id']);
    }

    public function test_task_index_preserves_legacy_department_scope_then_applies_member_focus(): void
    {
        $outsideTask = $this->createTask($this->memberA, [
            'department_id' => $this->otherDept->id,
            'task_title' => 'Outside department task',
        ]);
        $outsideTask->participants()->attach($this->viewer->id, ['joined_at' => now()]);
        $outsideTask->participants()->attach($this->memberA->id, ['joined_at' => now()]);

        $response = $this->actingAs($this->viewer)
            ->get(route('activity.task.index', [
                'scope' => 'department',
                'member_user_id' => (string) $this->memberA->id,
            ]));

        $response->assertOk();
        $taskIds = array_column($response->viewData('page')['props']['tasks']['data'], 'id');

        $this->assertContains($outsideTask->id, $taskIds);
    }

    public function test_task_index_sanitizes_invalid_member_id(): void
    {
        $response = $this->actingAs($this->viewer)
            ->get(route('activity.task.index', [
                'scope' => 'department',
                'member_user_id' => '99999',
            ]));

        $response->assertOk();
        $filters = $response->viewData('page')['props']['filters'];

        $this->assertSame('', $filters['member_user_id']);
    }

    public function test_task_index_ignores_malformed_member_filter_payload(): void
    {
        $this->createTask($this->memberA, [
            'task_title' => 'Task by Member A',
        ]);

        $response = $this->actingAs($this->viewer)
            ->get(route('activity.task.index', ['scope' => 'department']).'&member_user_id[]='.$this->memberA->id);

        $response->assertOk();

        $props = $response->viewData('page')['props'];

        $this->assertSame('', $props['filters']['member_user_id']);
        $this->assertSame(1, $props['stats']['total']);
    }

    public function test_task_index_returns_team_members_from_active_assignments(): void
    {
        $response = $this->actingAs($this->viewer)
            ->get(route('activity.task.index', ['scope' => 'department']));

        $response->assertOk();
        $teamMembers = $response->viewData('page')['props']['teamMembers'];

        $this->assertIsArray($teamMembers);
        $this->assertCount(3, $teamMembers);
        $this->assertSame(
            collect([$this->memberA->id, $this->memberB->id, $this->viewer->id])->sort()->values()->all(),
            collect($teamMembers)->pluck('id')->sort()->values()->all()
        );
        $this->assertNotContains($this->inactiveMember->id, collect($teamMembers)->pluck('id')->all());
    }

    public function test_dashboard_filters_only_department_datasets_when_member_focus_active(): void
    {
        $this->createTask($this->memberA, [
            'task_title' => 'Member A department task',
            'status' => 'in_progress',
        ])->participants()->attach($this->memberA->id, ['joined_at' => now()]);

        $this->createTask($this->memberB, [
            'task_title' => 'Member B department task',
            'status' => 'completed',
        ])->participants()->attach($this->memberB->id, ['joined_at' => now()]);

        $response = $this->actingAs($this->viewer)
            ->get(route('activity.dashboard', [
                'member_user_id' => (string) $this->memberA->id,
                'dept_distribution_period' => 'all',
                'distribution_period' => 'all',
            ]));

        $response->assertOk();
        $props = $response->viewData('page')['props'];

        $this->assertSame((string) $this->memberA->id, $props['queryParams']['member_user_id']);
        $this->assertCount(3, $props['departmentMembers']);
        $this->assertSame(1, $props['departmentStats']['total']);
        $this->assertSame(0, $props['personalStats']['total']);
    }

    public function test_export_filters_department_scope_by_member_focus_and_keeps_my_scope_semantics(): void
    {
        $departmentTask = $this->createTask($this->memberA, [
            'task_title' => 'Department export task',
            'task_date' => now()->toDateString(),
        ]);
        $departmentTask->participants()->attach($this->memberA->id, ['joined_at' => now()]);

        $myTask = $this->createTask($this->viewer, [
            'task_title' => 'My export task',
            'task_date' => now()->toDateString(),
        ]);
        $myTask->participants()->attach($this->viewer->id, ['joined_at' => now()]);

        $departmentResponse = $this->actingAs($this->viewer)
            ->get(route('activity.task.export', [
                'scope' => 'department',
                'member_user_id' => (string) $this->memberA->id,
            ]));

        $departmentResponse->assertOk();
        $departmentResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $detailSheetRows = $this->detailSheetRows($departmentResponse->streamedContent());

        $this->assertContains('Department export task', $detailSheetRows);
        $this->assertNotContains('My export task', $detailSheetRows);

        $myResponse = $this->actingAs($this->viewer)
            ->get(route('activity.task.export', [
                'scope' => 'my',
                'member_user_id' => (string) $this->memberA->id,
            ]));

        $myResponse->assertOk();
        $myRows = $this->detailSheetRows($myResponse->streamedContent());

        $this->assertContains('My export task', $myRows);
        $this->assertNotContains('Department export task', $myRows);
    }

    public function test_export_ignores_malformed_member_filter_payload(): void
    {
        $departmentTask = $this->createTask($this->memberA, [
            'task_title' => 'Department export task',
            'task_date' => now()->toDateString(),
        ]);
        $departmentTask->participants()->attach($this->memberA->id, ['joined_at' => now()]);

        $response = $this->actingAs($this->viewer)
            ->get(route('activity.task.export', ['scope' => 'department']).'&member_user_id[]='.$this->memberA->id);

        $response->assertOk();

        $detailSheetRows = $this->detailSheetRows($response->streamedContent());

        $this->assertContains('Department export task', $detailSheetRows);
    }

    public function test_member_focus_service_returns_empty_members_when_department_context_missing(): void
    {
        $service = app(\App\Services\Modules\Activity\ActivityMemberFocusService::class);

        $this->assertSame([], $service->resolveDepartmentMembers($this->bu->id, null));
    }

    protected function createUserWithAssignment(string $name, string $email, Department $department, Position $position, bool $isActive = true): User
    {
        $user = User::create([
            'name' => $name,
            'username' => str_replace('@', '.', $email),
            'email' => $email,
            'phone_number' => '081234567890',
            'password' => bcrypt('password'),
            'primary_department_id' => $department->id,
            'primary_position_id' => $position->id,
            'is_active' => $isActive,
            'email_verified_at' => now(),
        ]);

        UserBusinessUnit::create([
            'user_id' => $user->id,
            'business_unit_id' => $this->bu->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'is_active' => true,
            'is_primary' => true,
        ]);

        return $user;
    }

    protected function createTask(User $creator, array $overrides = []): EmployeeTask
    {
        return EmployeeTask::create(array_merge([
            'business_unit_id' => $this->bu->id,
            'department_id' => $this->dept->id,
            'created_by' => $creator->id,
            'activity_type_id' => $this->activityType->id,
            'task_title' => 'Focused task',
            'task_description' => 'Focused description',
            'task_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'planned',
            'priority' => 'medium',
        ], $overrides));
    }

    /**
     * @return array<int, string>
     */
    protected function detailSheetRows(string $streamedContent): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'activity-export-');
        file_put_contents($tempFile, $streamedContent);

        $spreadsheet = IOFactory::load($tempFile);
        $sheet = $spreadsheet->getSheetByName('Detail');
        $rows = $sheet?->toArray() ?? [];

        @unlink($tempFile);

        return collect($rows)
            ->skip(1)
            ->pluck(2)
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->values()
            ->all();
    }
}
