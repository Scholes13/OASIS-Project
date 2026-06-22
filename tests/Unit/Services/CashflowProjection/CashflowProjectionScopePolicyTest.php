<?php

namespace Tests\Unit\Services\CashflowProjection;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Services\Modules\CashflowProjection\CashflowProjectionScopePolicy;
use App\Services\Modules\CashflowProjection\CashflowProjectionScopeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashflowProjectionScopePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_name_containing_finance_does_not_grant_finance_assignment(): void
    {
        $businessUnit = BusinessUnit::create([
            'code' => 'WNS',
            'name' => 'Werkudara Nirwana Sakti',
            'is_active' => true,
        ]);

        $department = Department::create([
            'business_unit_id' => $businessUnit->id,
            'code' => 'PFR',
            'name' => 'Project Finance Reporting',
            'is_active' => true,
        ]);

        $position = Position::query()
            ->where('department_id', $department->id)
            ->where('code', 'HOD_PFR')
            ->firstOrFail();

        $user = User::create([
            'name' => 'Reporting HOD',
            'email' => 'reporting.policy@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567813',
            'primary_department_id' => $department->id,
            'primary_position_id' => $position->id,
            'global_role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->businessUnits()->create([
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->assertFalse(app(CashflowProjectionScopePolicy::class)->userHasFinanceAssignment($user, $businessUnit->id));
    }

    public function test_super_admin_active_parent_business_unit_can_target_child_departments(): void
    {
        $parentBusinessUnit = BusinessUnit::create(['code' => 'WG', 'name' => 'Werkudara Group', 'is_active' => true]);
        $childBusinessUnit = BusinessUnit::create(['parent_id' => $parentBusinessUnit->id, 'code' => 'WNS', 'name' => 'Werkudara Nirwana Sakti', 'is_active' => true]);
        $parentDepartment = Department::create(['business_unit_id' => $parentBusinessUnit->id, 'code' => 'SYSADMIN', 'name' => 'System Administration', 'is_active' => true]);
        $childDepartment = Department::create(['business_unit_id' => $childBusinessUnit->id, 'code' => 'ACC', 'name' => 'Accounting', 'is_active' => true]);

        $position = Position::query()->where('department_id', $parentDepartment->id)->where('code', 'STAFF_SYSADMIN')->firstOrFail();
        $user = User::create([
            'name' => 'Super Scope User',
            'email' => 'super.scope@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567814',
            'primary_department_id' => $parentDepartment->id,
            'primary_position_id' => $position->id,
            'global_role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->businessUnits()->create([
            'business_unit_id' => $parentBusinessUnit->id,
            'department_id' => $parentDepartment->id,
            'position_id' => $position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $departments = app(CashflowProjectionScopeService::class)->allowedDepartments($user, $parentBusinessUnit->id);

        $this->assertTrue($departments->contains('id', $childDepartment->id));
    }
}
