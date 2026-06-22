<?php

namespace Tests\Feature\Core;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationGateTest extends TestCase
{
    use RefreshDatabase;

    private BusinessUnit $parentBU;

    private BusinessUnit $childBU;

    private Department $parentDept;

    private Department $childDept;

    private Position $cLevelPosition;

    private Position $hodPosition;

    private Position $staffPosition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parentBU = BusinessUnit::create([
            'code' => 'WG',
            'name' => 'Werkudara Group',
            'is_active' => true,
        ]);

        $this->childBU = BusinessUnit::create([
            'code' => 'WNS',
            'name' => 'Werkudara Nusantara Sejahtera',
            'parent_id' => $this->parentBU->id,
            'is_active' => true,
        ]);

        $this->parentDept = Department::create([
            'business_unit_id' => $this->parentBU->id,
            'name' => 'Executive Office',
            'code' => 'EXEC',
            'is_active' => true,
        ]);

        $this->childDept = Department::create([
            'business_unit_id' => $this->childBU->id,
            'name' => 'Finance',
            'code' => 'FIN',
            'is_active' => true,
            'is_purchasing_department' => true,
        ]);

        $this->cLevelPosition = Position::create([
            'department_id' => $this->parentDept->id,
            'name' => 'Director',
            'code' => 'TEST_DIR_'.uniqid(),
            'level' => 'c_level',
            'access_level' => 'executive',
            'hierarchy_level' => 0,
            'is_active' => true,
        ]);

        $this->hodPosition = Position::create([
            'department_id' => $this->childDept->id,
            'name' => 'Head of Finance',
            'code' => 'TEST_HOD_'.uniqid(),
            'level' => 'hod',
            'access_level' => 'department_head',
            'hierarchy_level' => 1,
            'is_active' => true,
        ]);

        $this->staffPosition = Position::create([
            'department_id' => $this->childDept->id,
            'name' => 'Staff Finance',
            'code' => 'TEST_STAFF_'.uniqid(),
            'level' => 'staff',
            'access_level' => 'staff',
            'hierarchy_level' => 3,
            'is_active' => true,
        ]);
    }

    private function createUserWithPosition(Position $position, BusinessUnit $bu, array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'primary_department_id' => $position->department_id,
            'primary_position_id' => $position->id,
            'global_role' => 'user',
        ], $overrides));

        UserBusinessUnit::create([
            'user_id' => $user->id,
            'business_unit_id' => $bu->id,
            'department_id' => $position->department_id,
            'position_id' => $position->id,
            'is_active' => true,
            'is_primary' => true,
        ]);

        return $user;
    }

    // ====================================================================
    // view-reports Gate
    // ====================================================================

    public function test_super_admin_can_view_reports(): void
    {
        $user = User::factory()->create(['global_role' => 'super_admin']);

        $this->assertTrue(Gate::forUser($user)->allows('view-reports'));
    }

    public function test_c_level_user_can_view_reports(): void
    {
        $user = $this->createUserWithPosition($this->cLevelPosition, $this->parentBU);

        $this->assertTrue(Gate::forUser($user)->allows('view-reports'));
    }

    public function test_hod_user_cannot_view_reports(): void
    {
        $user = $this->createUserWithPosition($this->hodPosition, $this->childBU);

        $this->assertFalse(Gate::forUser($user)->allows('view-reports'));
    }

    public function test_staff_user_cannot_view_reports(): void
    {
        $user = $this->createUserWithPosition($this->staffPosition, $this->childBU);

        $this->assertFalse(Gate::forUser($user)->allows('view-reports'));
    }

    public function test_executive_access_level_grants_view_reports(): void
    {
        $executivePosition = Position::create([
            'department_id' => $this->parentDept->id,
            'name' => 'Executive Operations',
            'code' => 'TEST_EXEC_'.uniqid(),
            'level' => 'hod',
            'access_level' => 'executive',
            'hierarchy_level' => 0,
            'is_active' => true,
        ]);

        $user = $this->createUserWithPosition($executivePosition, $this->parentBU);

        $this->assertTrue(Gate::forUser($user)->allows('view-reports'));
    }

    // ====================================================================
    // view-department-analytics Gate
    // ====================================================================

    public function test_user_with_department_can_view_department_analytics(): void
    {
        $user = $this->createUserWithPosition($this->hodPosition, $this->childBU);

        $this->assertTrue(Gate::forUser($user)->allows('view-department-analytics'));
    }

    public function test_user_without_department_cannot_view_department_analytics(): void
    {
        $user = User::factory()->create([
            'primary_department_id' => null,
            'global_role' => 'user',
        ]);

        $this->assertFalse(Gate::forUser($user)->allows('view-department-analytics'));
    }

    // ====================================================================
    // access-purchasing-admin Gate
    // ====================================================================

    public function test_super_admin_can_access_purchasing_admin(): void
    {
        $user = User::factory()->create(['global_role' => 'super_admin']);

        $this->assertTrue(Gate::forUser($user)->allows('access-purchasing-admin'));
    }

    public function test_top_management_in_parent_bu_can_access_purchasing_admin(): void
    {
        $user = $this->createUserWithPosition($this->cLevelPosition, $this->parentBU);

        $this->assertTrue(Gate::forUser($user)->allows('access-purchasing-admin'));
    }

    public function test_top_management_in_child_bu_can_access_purchasing_admin(): void
    {
        // PO 2026-05-26: Chief of Staff sits at WNS/EXEC (child BU) but needs
        // executive-tier visibility into Purchasing Admin. Gate must accept
        // top-management positions in any active BU, not only parent BU.
        $childExec = Position::create([
            'department_id' => $this->childDept->id,
            'name' => 'Chief of Staff',
            'code' => 'TEST_COS_'.uniqid(),
            'level' => 'c_level',
            'access_level' => 'executive',
            'hierarchy_level' => 0,
            'is_active' => true,
        ]);

        $user = $this->createUserWithPosition($childExec, $this->childBU);

        session(['current_business_unit_id' => $this->childBU->id]);

        $this->assertTrue(Gate::forUser($user)->allows('access-purchasing-admin'));
    }

    public function test_hod_in_parent_bu_can_access_purchasing_admin(): void
    {
        $parentHod = Position::create([
            'department_id' => $this->parentDept->id,
            'name' => 'Head of Operations',
            'code' => 'TEST_PHOD_'.uniqid(),
            'level' => 'hod',
            'access_level' => 'department_head',
            'hierarchy_level' => 1,
            'is_active' => true,
        ]);

        $user = $this->createUserWithPosition($parentHod, $this->parentBU);

        $this->assertTrue(Gate::forUser($user)->allows('access-purchasing-admin'));
    }

    public function test_purchasing_admin_in_current_bu_can_access(): void
    {
        $user = $this->createUserWithPosition($this->staffPosition, $this->childBU);

        UserBusinessUnit::where('user_id', $user->id)
            ->where('business_unit_id', $this->childBU->id)
            ->update(['is_purchasing_admin' => true]);

        session(['current_business_unit_id' => $this->childBU->id]);

        $this->assertTrue(Gate::forUser($user)->allows('access-purchasing-admin'));
    }

    public function test_staff_in_child_bu_without_purchasing_admin_denied(): void
    {
        $user = $this->createUserWithPosition($this->staffPosition, $this->childBU);

        session(['current_business_unit_id' => $this->childBU->id]);

        $this->assertFalse(Gate::forUser($user)->allows('access-purchasing-admin'));
    }

    // ====================================================================
    // Position model: topManagement / managerAndAbove scopes
    // ====================================================================

    public function test_top_management_scope_returns_c_level_and_executive(): void
    {
        $topPositions = Position::topManagement()->pluck('id')->toArray();

        $this->assertContains($this->cLevelPosition->id, $topPositions);
        $this->assertNotContains($this->hodPosition->id, $topPositions);
        $this->assertNotContains($this->staffPosition->id, $topPositions);
    }

    public function test_manager_and_above_scope_returns_c_level_and_hod(): void
    {
        $managerPositions = Position::managerAndAbove()->pluck('id')->toArray();

        $this->assertContains($this->cLevelPosition->id, $managerPositions);
        $this->assertContains($this->hodPosition->id, $managerPositions);
        $this->assertNotContains($this->staffPosition->id, $managerPositions);
    }

    // ====================================================================
    // User model convenience methods
    // ====================================================================

    public function test_has_top_management_access_returns_true_for_c_level(): void
    {
        $user = $this->createUserWithPosition($this->cLevelPosition, $this->parentBU);

        $this->assertTrue($user->hasTopManagementAccess());
    }

    public function test_has_top_management_access_returns_false_for_staff(): void
    {
        $user = $this->createUserWithPosition($this->staffPosition, $this->childBU);

        $this->assertFalse($user->hasTopManagementAccess());
    }

    public function test_has_manager_access_returns_true_for_hod(): void
    {
        $user = $this->createUserWithPosition($this->hodPosition, $this->childBU);

        $this->assertTrue($user->hasManagerAccess());
    }

    public function test_has_manager_access_returns_false_for_staff(): void
    {
        $user = $this->createUserWithPosition($this->staffPosition, $this->childBU);

        $this->assertFalse($user->hasManagerAccess());
    }

    public function test_has_top_management_in_parent_bu(): void
    {
        $user = $this->createUserWithPosition($this->cLevelPosition, $this->parentBU);

        $this->assertTrue($user->hasTopManagementInParentBU());
    }

    public function test_c_level_in_child_bu_is_not_top_management_in_parent_bu(): void
    {
        $childCLevel = Position::create([
            'department_id' => $this->childDept->id,
            'name' => 'Child Director',
            'code' => 'TEST_CDIR_'.uniqid(),
            'level' => 'c_level',
            'access_level' => 'executive',
            'hierarchy_level' => 0,
            'is_active' => true,
        ]);

        $user = $this->createUserWithPosition($childCLevel, $this->childBU);

        $this->assertFalse($user->hasTopManagementInParentBU());
        $this->assertTrue($user->hasTopManagementAccess());
    }

    // ====================================================================
    // Multi-BU context: same user, different roles per BU
    // ====================================================================

    public function test_user_with_c_level_in_one_bu_and_staff_in_another_can_view_reports(): void
    {
        $user = $this->createUserWithPosition($this->cLevelPosition, $this->parentBU);

        UserBusinessUnit::create([
            'user_id' => $user->id,
            'business_unit_id' => $this->childBU->id,
            'department_id' => $this->childDept->id,
            'position_id' => $this->staffPosition->id,
            'is_active' => true,
            'is_primary' => false,
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('view-reports'));
    }

    // ====================================================================
    // Inactive assignment edge case
    // ====================================================================

    public function test_inactive_c_level_assignment_does_not_grant_view_reports(): void
    {
        $user = User::factory()->create([
            'primary_department_id' => $this->parentDept->id,
            'primary_position_id' => $this->cLevelPosition->id,
            'global_role' => 'user',
        ]);

        UserBusinessUnit::create([
            'user_id' => $user->id,
            'business_unit_id' => $this->parentBU->id,
            'department_id' => $this->parentDept->id,
            'position_id' => $this->cLevelPosition->id,
            'is_active' => false,
            'is_primary' => true,
        ]);

        $this->assertFalse(Gate::forUser($user)->allows('view-reports'));
    }
}
