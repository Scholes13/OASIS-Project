<?php

namespace Tests\Feature\Core;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Services\Core\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NavigationTopManagementTest extends TestCase
{
    use RefreshDatabase;

    private BusinessUnit $parentBU;

    private BusinessUnit $childBU;

    private Department $parentDept;

    private Department $childDept;

    private Position $cLevelPosition;

    private Position $staffPosition;

    private User $cLevelUser;

    private User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);

        $this->parentBU = BusinessUnit::create([
            'code' => 'WG',
            'name' => 'Werkudara Group',
            'is_active' => true,
        ]);

        $this->childBU = BusinessUnit::create([
            'code' => 'WNS',
            'name' => 'Werkudara Nusantara',
            'parent_id' => $this->parentBU->id,
            'is_active' => true,
        ]);

        $this->parentDept = Department::create([
            'business_unit_id' => $this->parentBU->id,
            'name' => 'Managing Director',
            'code' => 'MD_NAV',
            'is_active' => true,
        ]);

        $this->childDept = Department::create([
            'business_unit_id' => $this->childBU->id,
            'name' => 'General Affairs',
            'code' => 'GA_NAV',
            'is_active' => true,
        ]);

        $this->cLevelPosition = Position::create([
            'department_id' => $this->parentDept->id,
            'name' => 'Director',
            'code' => 'NAV_DIR_'.uniqid(),
            'level' => 'c_level',
            'access_level' => 'executive',
            'hierarchy_level' => 0,
            'is_active' => true,
        ]);

        $this->staffPosition = Position::create([
            'department_id' => $this->childDept->id,
            'name' => 'Staff GA',
            'code' => 'NAV_STF_'.uniqid(),
            'level' => 'staff',
            'access_level' => 'staff',
            'hierarchy_level' => 3,
            'is_active' => true,
        ]);

        $this->cLevelUser = $this->createCLevelUser();
        $this->staffUser = $this->createStaffUser();
    }

    public function test_c_level_user_sees_purchasing_section_in_child_bu(): void
    {
        $navigation = app(NavigationService::class)
            ->buildMenuForUser($this->cLevelUser, $this->childBU->id);

        $this->assertTrue(
            $this->hasSection($navigation, 'Purchasing'),
            'C-level user should see Purchasing section in child BU navigation'
        );
    }

    public function test_c_level_user_sees_activity_tracking_section_in_child_bu(): void
    {
        $navigation = app(NavigationService::class)
            ->buildMenuForUser($this->cLevelUser, $this->childBU->id);

        $this->assertTrue(
            $this->hasSection($navigation, 'Activity Tracking'),
            'C-level user should see Activity Tracking section in child BU navigation'
        );
    }

    public function test_c_level_user_sees_activity_admin_in_child_bu(): void
    {
        $navigation = app(NavigationService::class)
            ->buildMenuForUser($this->cLevelUser, $this->childBU->id);

        $this->assertTrue(
            $this->hasChildNavigationItem($navigation, 'Activity Tracking', 'Activity', 'Activity Admin'),
            'C-level user should see Activity Admin in child BU navigation'
        );
    }

    public function test_c_level_user_sees_purchasing_section_in_own_bu(): void
    {
        $navigation = app(NavigationService::class)
            ->buildMenuForUser($this->cLevelUser, $this->parentBU->id);

        $this->assertTrue(
            $this->hasSection($navigation, 'Purchasing'),
            'C-level user should see Purchasing section in own BU navigation'
        );
    }

    public function test_staff_user_cannot_see_sections_in_other_bu(): void
    {
        $navigation = app(NavigationService::class)
            ->buildMenuForUser($this->staffUser, $this->parentBU->id);

        $this->assertFalse(
            $this->hasSection($navigation, 'Purchasing'),
            'Staff user should NOT see Purchasing section in a BU they are not assigned to'
        );

        $this->assertFalse(
            $this->hasSection($navigation, 'Activity Tracking'),
            'Staff user should NOT see Activity Tracking section in a BU they are not assigned to'
        );
    }

    public function test_staff_user_sees_sections_in_own_bu(): void
    {
        $navigation = app(NavigationService::class)
            ->buildMenuForUser($this->staffUser, $this->childBU->id);

        $this->assertTrue(
            $this->hasSection($navigation, 'Purchasing'),
            'Staff user should see Purchasing section in their own BU'
        );

        $this->assertTrue(
            $this->hasSection($navigation, 'Activity Tracking'),
            'Staff user should see Activity Tracking section in their own BU'
        );
    }

    public function test_c_level_can_access_activity_admin_dashboard_in_child_bu(): void
    {
        $response = $this->actingAs($this->cLevelUser)
            ->withSession([
                'current_business_unit_id' => $this->childBU->id,
                'current_department_id' => $this->childDept->id,
            ])
            ->get(route('activity.admin.dashboard'));

        $response->assertOk();
    }

    public function test_staff_cannot_access_activity_admin_dashboard(): void
    {
        $response = $this->actingAs($this->staffUser)
            ->withSession([
                'current_business_unit_id' => $this->childBU->id,
                'current_department_id' => $this->childDept->id,
            ])
            ->get(route('activity.admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_navigation_returns_empty_sections_when_no_business_unit(): void
    {
        $navigation = app(NavigationService::class)
            ->buildMenuForUser($this->cLevelUser, null);

        $this->assertEmpty($navigation['sections']);
    }

    public function test_new_business_unit_assignment_invalidates_cached_navigation(): void
    {
        $navigationService = app(NavigationService::class);
        $cacheKey = "nav:{$this->staffUser->id}:{$this->childBU->id}";
        /** @var UserBusinessUnit $assignment */
        $assignment = $this->staffUser->businessUnits()->firstOrFail();

        Cache::put($cacheKey, ['sections' => []], 900);

        $assignment->update([
            'is_activity_admin' => true,
        ]);

        $this->assertNull(Cache::get($cacheKey), 'Navigation cache should be cleared when assignment changes.');

        $navigation = $navigationService->buildMenuForUser($this->staffUser->fresh(), $this->childBU->id);

        $this->assertTrue(
            $this->hasSection($navigation, 'Purchasing'),
            'User should see navigation rebuilt after assignment cache invalidation.'
        );
    }

    protected function createCLevelUser(): User
    {
        $user = User::create([
            'name' => 'C-Level Director',
            'email' => 'clevel.nav@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567890',
            'primary_department_id' => $this->parentDept->id,
            'primary_position_id' => $this->cLevelPosition->id,
            'global_role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->businessUnits()->create([
            'business_unit_id' => $this->parentBU->id,
            'department_id' => $this->parentDept->id,
            'position_id' => $this->cLevelPosition->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        return $user;
    }

    protected function createStaffUser(): User
    {
        $user = User::create([
            'name' => 'Staff User',
            'email' => 'staff.nav@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567891',
            'primary_department_id' => $this->childDept->id,
            'primary_position_id' => $this->staffPosition->id,
            'global_role' => 'staff',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->businessUnits()->create([
            'business_unit_id' => $this->childBU->id,
            'department_id' => $this->childDept->id,
            'position_id' => $this->staffPosition->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $navigation
     */
    protected function hasSection(array $navigation, string $sectionName): bool
    {
        foreach ($navigation['sections'] ?? [] as $section) {
            if (($section['name'] ?? null) === $sectionName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $navigation
     */
    protected function hasChildNavigationItem(array $navigation, string $sectionName, string $parentName, string $childName): bool
    {
        foreach ($navigation['sections'] ?? [] as $section) {
            if (($section['name'] ?? null) !== $sectionName) {
                continue;
            }

            foreach ($section['items'] ?? [] as $item) {
                if (($item['name'] ?? null) !== $parentName) {
                    continue;
                }

                foreach ($item['children'] ?? [] as $child) {
                    if (($child['name'] ?? null) === $childName) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
