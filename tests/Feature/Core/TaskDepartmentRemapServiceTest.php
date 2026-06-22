<?php

namespace Tests\Feature\Core;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\EmployeeTask;
use App\Services\Core\Restructure\TaskDepartmentRemapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Coverage for TaskDepartmentRemapService::remapForUser.
 *
 * Source: PRD docs/specs/2026-05-25-wns-restructure-prd/04-data-migration-plan.md
 * follow-up. Ensures historical tasks reattach to a moved user's new dept
 * without touching unrelated tasks or other users.
 */
class TaskDepartmentRemapServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskDepartmentRemapService $service;

    private BusinessUnit $bu;

    private Department $oldDept;

    private Department $newDept;

    private User $user;

    private User $otherUser;

    private ActivityType $activityType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TaskDepartmentRemapService::class);

        $this->bu = BusinessUnit::create([
            'name' => 'WNS Test',
            'code' => 'WNS',
            'is_active' => true,
        ]);

        $this->oldDept = Department::create([
            'business_unit_id' => $this->bu->id,
            'code' => 'OLD',
            'name' => 'Old Department',
            'is_active' => true,
        ]);

        $this->newDept = Department::create([
            'business_unit_id' => $this->bu->id,
            'code' => 'NEW',
            'name' => 'New Department',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Moved User',
            'email' => 'moved@example.com',
            'phone_number' => '081200000099',
            'password' => bcrypt('secret'),
            'global_role' => 'user',
            'is_active' => true,
            'primary_department_id' => $this->newDept->id,
        ]);

        $this->otherUser = User::create([
            'name' => 'Stay Put',
            'email' => 'stay@example.com',
            'phone_number' => '081200000098',
            'password' => bcrypt('secret'),
            'global_role' => 'user',
            'is_active' => true,
            'primary_department_id' => $this->oldDept->id,
        ]);

        $this->activityType = ActivityType::create([
            'code' => 'TEST_AT',
            'name' => 'Test Activity Type',
            'color' => '#000000',
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function makeTask(int $createdBy, int $deptId, string $title = 'task'): EmployeeTask
    {
        return EmployeeTask::create([
            'business_unit_id' => $this->bu->id,
            'department_id' => $deptId,
            'created_by' => $createdBy,
            'activity_type_id' => $this->activityType->id,
            'task_title' => $title,
            'task_date' => now()->toDateString(),
            'status' => 'planned',
        ]);
    }

    #[Test]
    public function dry_run_does_not_change_task_department(): void
    {
        $task = $this->makeTask($this->user->id, $this->oldDept->id);

        $result = $this->service->remapForUser(
            email: $this->user->email,
            businessUnitCode: 'WNS',
            newDepartmentCode: 'NEW',
            dryRun: true,
        );

        $this->assertSame('would_update', $result['status']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame($this->oldDept->id, $task->fresh()->department_id);
    }

    #[Test]
    public function execute_updates_only_users_own_tasks(): void
    {
        $movedTask = $this->makeTask($this->user->id, $this->oldDept->id, 'moved');
        $otherTask = $this->makeTask($this->otherUser->id, $this->oldDept->id, 'other');

        $result = $this->service->remapForUser(
            email: $this->user->email,
            businessUnitCode: 'WNS',
            newDepartmentCode: 'NEW',
            dryRun: false,
        );

        $this->assertSame('updated', $result['status']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame($this->newDept->id, $movedTask->fresh()->department_id);
        $this->assertSame($this->oldDept->id, $otherTask->fresh()->department_id);
    }

    #[Test]
    public function execute_is_idempotent(): void
    {
        $this->makeTask($this->user->id, $this->oldDept->id);

        $first = $this->service->remapForUser(
            email: $this->user->email,
            businessUnitCode: 'WNS',
            newDepartmentCode: 'NEW',
            dryRun: false,
        );
        $second = $this->service->remapForUser(
            email: $this->user->email,
            businessUnitCode: 'WNS',
            newDepartmentCode: 'NEW',
            dryRun: false,
        );

        $this->assertSame('updated', $first['status']);
        $this->assertSame('no_change', $second['status']);
        $this->assertSame(0, $second['updated']);
    }

    #[Test]
    public function user_not_found_returns_user_not_found(): void
    {
        $result = $this->service->remapForUser(
            email: 'ghost@example.com',
            businessUnitCode: 'WNS',
            newDepartmentCode: 'NEW',
            dryRun: false,
        );

        $this->assertSame('user_not_found', $result['status']);
    }

    #[Test]
    public function dept_not_found_returns_dept_not_found(): void
    {
        $result = $this->service->remapForUser(
            email: $this->user->email,
            businessUnitCode: 'WNS',
            newDepartmentCode: 'DOES_NOT_EXIST',
            dryRun: false,
        );

        $this->assertSame('dept_not_found', $result['status']);
    }

    #[Test]
    public function task_at_target_dept_is_not_changed(): void
    {
        $task = $this->makeTask($this->user->id, $this->newDept->id);

        $result = $this->service->remapForUser(
            email: $this->user->email,
            businessUnitCode: 'WNS',
            newDepartmentCode: 'NEW',
            dryRun: false,
        );

        $this->assertSame('no_change', $result['status']);
        $this->assertSame($this->newDept->id, $task->fresh()->department_id);
    }
}
