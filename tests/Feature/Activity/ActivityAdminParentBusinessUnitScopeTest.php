<?php

namespace Tests\Feature\Activity;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityAdminParentBusinessUnitScopeTest extends TestCase
{
    use RefreshDatabase;

    private BusinessUnit $parentBusinessUnit;

    private BusinessUnit $firstChildBusinessUnit;

    private BusinessUnit $secondChildBusinessUnit;

    private Department $parentDepartment;

    private Department $firstChildDepartment;

    private Department $secondChildDepartment;

    private Position $parentPosition;

    private User $superAdmin;

    private ActivityType $activityType;

    private EmployeeTask $firstChildTask;

    private EmployeeTask $secondChildTask;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);

        $this->parentBusinessUnit = BusinessUnit::create([
            'name' => 'Werkudara Group',
            'code' => 'WG',
            'is_active' => true,
        ]);

        $this->firstChildBusinessUnit = BusinessUnit::create([
            'name' => 'Werkudara Nusantara',
            'code' => 'WNS',
            'parent_id' => $this->parentBusinessUnit->id,
            'is_active' => true,
        ]);

        $this->secondChildBusinessUnit = BusinessUnit::create([
            'name' => 'Takshaka',
            'code' => 'TEE',
            'parent_id' => $this->parentBusinessUnit->id,
            'is_active' => true,
        ]);

        $this->parentDepartment = Department::create([
            'name' => 'System Administration',
            'code' => 'SYS',
            'business_unit_id' => $this->parentBusinessUnit->id,
            'is_active' => true,
        ]);

        $this->firstChildDepartment = Department::create([
            'name' => 'Operations WNS',
            'code' => 'OPS-WNS',
            'business_unit_id' => $this->firstChildBusinessUnit->id,
            'is_active' => true,
        ]);

        $this->secondChildDepartment = Department::create([
            'name' => 'Operations TEE',
            'code' => 'OPS-TEE',
            'business_unit_id' => $this->secondChildBusinessUnit->id,
            'is_active' => true,
        ]);

        $this->parentPosition = Position::create([
            'department_id' => $this->parentDepartment->id,
            'name' => 'Activity Admin Parent',
            'code' => 'ACT_PARENT',
            'level' => 'staff',
            'access_level' => 'staff',
            'hierarchy_level' => 3,
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Parent Super Admin',
            'email' => 'parent.super.admin@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567800',
            'primary_department_id' => $this->parentDepartment->id,
            'primary_position_id' => $this->parentPosition->id,
            'global_role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->superAdmin->businessUnits()->create([
            'business_unit_id' => $this->parentBusinessUnit->id,
            'department_id' => $this->parentDepartment->id,
            'position_id' => $this->parentPosition->id,
            'is_primary' => true,
            'is_active' => true,
            'is_activity_admin' => true,
        ]);

        $this->activityType = ActivityType::create([
            'code' => 'PLAN',
            'name' => 'Planning',
            'color' => '#16599c',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->firstChildTask = EmployeeTask::create([
            'business_unit_id' => $this->firstChildBusinessUnit->id,
            'department_id' => $this->firstChildDepartment->id,
            'created_by' => $this->superAdmin->id,
            'activity_type_id' => $this->activityType->id,
            'task_title' => 'Review WNS plan',
            'task_description' => 'Parent BU should see this child BU task.',
            'task_date' => '2026-04-02',
            'due_date' => '2026-04-03',
            'status' => 'completed',
            'priority' => 'medium',
            'duration_minutes' => 120,
        ]);

        $this->secondChildTask = EmployeeTask::create([
            'business_unit_id' => $this->secondChildBusinessUnit->id,
            'department_id' => $this->secondChildDepartment->id,
            'created_by' => $this->superAdmin->id,
            'activity_type_id' => $this->activityType->id,
            'task_title' => 'Review TEE plan',
            'task_description' => 'Parent BU should also see this child BU task.',
            'task_date' => '2026-04-04',
            'due_date' => '2026-04-05',
            'status' => 'planned',
            'priority' => 'medium',
        ]);
    }

    #[Test]
    public function parent_business_unit_dashboard_includes_child_business_unit_activity_data(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'current_business_unit_id' => $this->parentBusinessUnit->id,
                'current_business_unit_code' => $this->parentBusinessUnit->code,
                'current_business_unit_name' => $this->parentBusinessUnit->name,
                'current_business_unit_logo' => $this->parentBusinessUnit->logo,
                'current_department_id' => $this->parentDepartment->id,
            ])
            ->get(route('activity.admin.dashboard', [
                'date_from' => '2026-04-01',
                'date_to' => '2026-04-06',
            ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Activity/Admin/Dashboard')
            ->where('buSummary.total', 2)
            ->where('buSummary.completed', 1)
            ->where('buSummary.planned', 1)
            ->has('departments', 3)
            ->has('departmentStats', 3)
            ->has('departmentStats.0.department')
            ->has('departmentStats.1.department')
        );
    }

    #[Test]
    public function parent_business_unit_can_open_child_department_detail_from_activity_admin(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'current_business_unit_id' => $this->parentBusinessUnit->id,
                'current_business_unit_code' => $this->parentBusinessUnit->code,
                'current_business_unit_name' => $this->parentBusinessUnit->name,
                'current_business_unit_logo' => $this->parentBusinessUnit->logo,
                'current_department_id' => $this->parentDepartment->id,
            ])
            ->get(route('activity.admin.department', [
                'department' => $this->firstChildDepartment->id,
                'date_from' => '2026-04-01',
                'date_to' => '2026-04-06',
            ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Activity/Admin/DepartmentDetail')
            ->where('department.id', $this->firstChildDepartment->id)
            ->where('stats.total', 1)
            ->where('tasks.per_page', 10)
            ->where('tasks.data.0.id', $this->firstChildTask->id)
        );
    }

    #[Test]
    public function parent_business_unit_department_detail_can_hydrate_selected_task_modal_from_query(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'current_business_unit_id' => $this->parentBusinessUnit->id,
                'current_business_unit_code' => $this->parentBusinessUnit->code,
                'current_business_unit_name' => $this->parentBusinessUnit->name,
                'current_business_unit_logo' => $this->parentBusinessUnit->logo,
                'current_department_id' => $this->parentDepartment->id,
            ])
            ->get(route('activity.admin.department', [
                'department' => $this->firstChildDepartment->id,
                'date_from' => '2026-04-01',
                'date_to' => '2026-04-06',
                'modal' => 'detail',
                'task' => $this->firstChildTask->id,
            ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Activity/Admin/DepartmentDetail')
            ->where('selectedTask.id', $this->firstChildTask->id)
            ->where('selectedTaskModal', 'detail')
        );
    }

    #[Test]
    public function parent_business_unit_can_open_child_task_detail_from_activity_admin(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'current_business_unit_id' => $this->parentBusinessUnit->id,
                'current_business_unit_code' => $this->parentBusinessUnit->code,
                'current_business_unit_name' => $this->parentBusinessUnit->name,
                'current_business_unit_logo' => $this->parentBusinessUnit->logo,
                'current_department_id' => $this->parentDepartment->id,
            ])
            ->get(route('activity.admin.task', ['task' => $this->secondChildTask->id]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Activity/Admin/TaskDetail')
            ->where('task.id', $this->secondChildTask->id)
            ->where('task.business_unit_id', $this->secondChildBusinessUnit->id)
        );
    }

    #[Test]
    public function child_business_unit_dashboard_ignores_stale_department_filter_from_other_business_unit(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'current_business_unit_id' => $this->firstChildBusinessUnit->id,
                'current_business_unit_code' => $this->firstChildBusinessUnit->code,
                'current_business_unit_name' => $this->firstChildBusinessUnit->name,
                'current_business_unit_logo' => $this->firstChildBusinessUnit->logo,
                'current_department_id' => $this->firstChildDepartment->id,
            ])
            ->get(route('activity.admin.dashboard', [
                'date_from' => '2026-04-01',
                'date_to' => '2026-04-06',
                'department_id' => $this->secondChildDepartment->id,
            ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Activity/Admin/Dashboard')
            ->where('buSummary.total', 1)
            ->where('buSummary.completed', 1)
            ->where('buSummary.planned', 0)
            ->where('filters.department_id', null)
            ->has('departments', 1)
            ->where('departmentStats.0.department.id', $this->firstChildDepartment->id)
        );
    }

    #[Test]
    public function parent_business_unit_dashboard_rounds_total_hours_summary_after_aggregating_departments(): void
    {
        $this->firstChildTask->update([
            'duration_minutes' => 6,
        ]);

        $this->secondChildTask->update([
            'status' => 'completed',
            'duration_minutes' => 12,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'current_business_unit_id' => $this->parentBusinessUnit->id,
                'current_business_unit_code' => $this->parentBusinessUnit->code,
                'current_business_unit_name' => $this->parentBusinessUnit->name,
                'current_business_unit_logo' => $this->parentBusinessUnit->logo,
                'current_department_id' => $this->parentDepartment->id,
            ])
            ->get(route('activity.admin.dashboard', [
                'date_from' => '2026-04-01',
                'date_to' => '2026-04-06',
            ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Activity/Admin/Dashboard')
            ->where('buSummary.total_hours', 0.3)
        );
    }
}
