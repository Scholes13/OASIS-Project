<?php

namespace Tests\Feature\Core;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContextAwareAccessLevelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

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

        // Create business units
        $this->parentBU = BusinessUnit::create([
            'code' => 'PARENT',
            'name' => 'Parent Group',
            'is_active' => true,
        ]);

        $this->childBU = BusinessUnit::create([
            'code' => 'CHILD',
            'name' => 'Child Company',
            'parent_id' => $this->parentBU->id,
            'is_active' => true,
        ]);

        // Create departments
        $this->parentDept = Department::create([
            'business_unit_id' => $this->parentBU->id,
            'name' => 'Managing Director',
            'code' => 'MD',
            'is_active' => true,
        ]);

        $this->childDept = Department::create([
            'business_unit_id' => $this->childBU->id,
            'name' => 'Finance',
            'code' => 'FIN',
            'is_active' => true,
        ]);

        // Create positions with unique codes to avoid seeder conflicts
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

        // Create user with primary position as C-Level
        $this->user = User::factory()->create([
            'primary_department_id' => $this->parentDept->id,
            'primary_position_id' => $this->cLevelPosition->id,
            'global_role' => 'user',
        ]);
    }

    /**
     * Test global getAccessLevel without BU context returns primary position level.
     */
    public function test_global_access_level_returns_primary_position(): void
    {
        $this->assertEquals('executive', $this->user->getAccessLevel());
    }

    /**
     * Test getAccessLevel with BU context returns BU-specific position level.
     */
    public function test_access_level_with_bu_context_returns_bu_specific_position(): void
    {
        // Assign user as C-Level in parent BU
        UserBusinessUnit::create([
            'user_id' => $this->user->id,
            'business_unit_id' => $this->parentBU->id,
            'department_id' => $this->parentDept->id,
            'position_id' => $this->cLevelPosition->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        // Assign user as HOD in child BU
        UserBusinessUnit::create([
            'user_id' => $this->user->id,
            'business_unit_id' => $this->childBU->id,
            'department_id' => $this->childDept->id,
            'position_id' => $this->hodPosition->id,
            'is_primary' => false,
            'is_active' => true,
        ]);

        // In parent BU → executive (c_level)
        $this->assertEquals('executive', $this->user->getAccessLevel($this->parentBU->id));

        // In child BU → department_head (hod)
        $this->assertEquals('department_head', $this->user->getAccessLevel($this->childBU->id));
    }

    /**
     * Test getRoleInBusinessUnit returns correct per-BU role.
     */
    public function test_get_role_in_business_unit_returns_per_bu_role(): void
    {
        UserBusinessUnit::create([
            'user_id' => $this->user->id,
            'business_unit_id' => $this->parentBU->id,
            'department_id' => $this->parentDept->id,
            'position_id' => $this->cLevelPosition->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        UserBusinessUnit::create([
            'user_id' => $this->user->id,
            'business_unit_id' => $this->childBU->id,
            'department_id' => $this->childDept->id,
            'position_id' => $this->hodPosition->id,
            'is_primary' => false,
            'is_active' => true,
        ]);

        $this->assertEquals('executive', $this->user->getRoleInBusinessUnit($this->parentBU->id));
        $this->assertEquals('department_head', $this->user->getRoleInBusinessUnit($this->childBU->id));
    }

    /**
     * Test getPositionInBusinessUnit returns correct position model.
     */
    public function test_get_position_in_business_unit(): void
    {
        UserBusinessUnit::create([
            'user_id' => $this->user->id,
            'business_unit_id' => $this->parentBU->id,
            'department_id' => $this->parentDept->id,
            'position_id' => $this->cLevelPosition->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        UserBusinessUnit::create([
            'user_id' => $this->user->id,
            'business_unit_id' => $this->childBU->id,
            'department_id' => $this->childDept->id,
            'position_id' => $this->hodPosition->id,
            'is_primary' => false,
            'is_active' => true,
        ]);

        $parentPosition = $this->user->getPositionInBusinessUnit($this->parentBU->id);
        $childPosition = $this->user->getPositionInBusinessUnit($this->childBU->id);

        $this->assertEquals('Director', $parentPosition->name);
        $this->assertEquals('c_level', $parentPosition->level);

        $this->assertEquals('Head of Finance', $childPosition->name);
        $this->assertEquals('hod', $childPosition->level);
    }

    /**
     * Test fallback to primary position when no BU assignment exists.
     */
    public function test_fallback_to_primary_position_when_no_bu_assignment(): void
    {
        // No BU assignments created — should fallback to primaryPosition
        $nonExistentBuId = 99999;

        $this->assertEquals('executive', $this->user->getAccessLevel($nonExistentBuId));
    }

    /**
     * Test super admin always returns super_admin regardless of BU.
     */
    public function test_super_admin_always_returns_super_admin(): void
    {
        $superAdmin = User::factory()->create([
            'global_role' => 'super_admin',
            'primary_position_id' => $this->staffPosition->id,
        ]);

        $this->assertEquals('super_admin', $superAdmin->getAccessLevel());
        $this->assertEquals('super_admin', $superAdmin->getAccessLevel($this->parentBU->id));
        $this->assertEquals('super_admin', $superAdmin->getAccessLevel($this->childBU->id));
    }

    /**
     * Test Position model isCLevel method.
     */
    public function test_position_is_c_level(): void
    {
        $this->assertTrue($this->cLevelPosition->isCLevel());
        $this->assertFalse($this->hodPosition->isCLevel());
        $this->assertFalse($this->staffPosition->isCLevel());
    }

    /**
     * Test c_level level maps to executive access_level.
     */
    public function test_c_level_maps_to_executive(): void
    {
        $user = User::factory()->create([
            'primary_position_id' => $this->cLevelPosition->id,
            'global_role' => 'user',
        ]);

        $this->assertEquals('executive', $user->getAccessLevel());
    }

    /**
     * Test backward compatibility — getAccessLevel() without argument still works.
     */
    public function test_backward_compatible_without_argument(): void
    {
        $hodUser = User::factory()->create([
            'primary_position_id' => $this->hodPosition->id,
            'global_role' => 'user',
        ]);

        $staffUser = User::factory()->create([
            'primary_position_id' => $this->staffPosition->id,
            'global_role' => 'user',
        ]);

        $this->assertEquals('department_head', $hodUser->getAccessLevel());
        $this->assertEquals('staff', $staffUser->getAccessLevel());
    }

    /**
     * Test getAccessibleBusinessUnitIds includes child BU descendants
     * when user has c_level in a non-primary BU (dual-role scenario).
     */
    public function test_accessible_bu_ids_includes_descendants_for_non_primary_c_level(): void
    {
        // Create grandchild BU under child
        $grandchildBU = BusinessUnit::create([
            'code' => 'GRANDCHILD',
            'name' => 'Grandchild Company',
            'parent_id' => $this->childBU->id,
            'is_active' => true,
        ]);

        // Create user with HoD as PRIMARY in parent, c_level as NON-PRIMARY in child
        $dualUser = User::factory()->create([
            'primary_department_id' => $this->parentDept->id,
            'primary_position_id' => $this->hodPosition->id,
            'global_role' => 'user',
        ]);

        // HoD in parent BU (primary)
        UserBusinessUnit::create([
            'user_id' => $dualUser->id,
            'business_unit_id' => $this->parentBU->id,
            'department_id' => $this->parentDept->id,
            'position_id' => $this->hodPosition->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        // C-Level in child BU (non-primary)
        UserBusinessUnit::create([
            'user_id' => $dualUser->id,
            'business_unit_id' => $this->childBU->id,
            'department_id' => $this->childDept->id,
            'position_id' => $this->cLevelPosition->id,
            'is_primary' => false,
            'is_active' => true,
        ]);

        // Global access level = HoD (primary), but hasTopManagementAccess = true
        $this->assertEquals('department_head', $dualUser->getAccessLevel());
        $this->assertTrue($dualUser->hasTopManagementAccess());

        // Should include parent BU, child BU, AND grandchild BU
        $accessibleIds = $dualUser->getAccessibleBusinessUnitIds();
        $this->assertContains($this->parentBU->id, $accessibleIds);
        $this->assertContains($this->childBU->id, $accessibleIds);
        $this->assertContains($grandchildBU->id, $accessibleIds);
    }
}
