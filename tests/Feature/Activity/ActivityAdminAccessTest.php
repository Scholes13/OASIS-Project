<?php

namespace Tests\Feature\Activity;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Services\Core\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected BusinessUnit $businessUnit;

    protected Department $department;

    protected Position $position;

    protected User $activityAdmin;

    protected User $regularUser;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);

        $this->businessUnit = BusinessUnit::create([
            'name' => 'WNS Business Unit',
            'code' => 'WNS',
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'name' => 'General Affairs',
            'code' => 'GA',
            'business_unit_id' => $this->businessUnit->id,
            'is_active' => true,
        ]);

        $this->position = Position::create([
            'department_id' => $this->department->id,
            'name' => 'Staff',
            'code' => 'STF_GA',
            'level' => 'staff',
            'access_level' => 'staff',
            'hierarchy_level' => 3,
            'is_active' => true,
        ]);

        $this->activityAdmin = $this->createUser('activity.admin@example.com', true);
        $this->regularUser = $this->createUser('regular.user@example.com', false);
        $this->superAdmin = $this->createUser('super.admin@example.com', false, 'super_admin');
    }

    public function test_activity_admin_can_access_activity_admin_dashboard(): void
    {
        $response = $this->actingAs($this->activityAdmin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->get(route('activity.admin.dashboard'));

        $response->assertOk();
    }

    public function test_non_activity_admin_gets_forbidden_from_activity_admin_dashboard(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->get(route('activity.admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_navigation_includes_activity_admin_item_for_activity_admin_user(): void
    {
        $navigation = app(NavigationService::class)->buildMenuForUser($this->activityAdmin, $this->businessUnit->id);

        $this->assertTrue($this->hasChildNavigationItem($navigation, 'Activity Tracking', 'Activity', 'Activity Admin'));
    }

    public function test_navigation_hides_activity_admin_item_for_non_activity_admin_user(): void
    {
        $navigation = app(NavigationService::class)->buildMenuForUser($this->regularUser, $this->businessUnit->id);

        $this->assertFalse($this->hasChildNavigationItem($navigation, 'Activity Tracking', 'Activity', 'Activity Admin'));
    }

    public function test_super_admin_navigation_includes_activity_admin_assignment_menu(): void
    {
        $navigation = app(NavigationService::class)->buildMenuForUser($this->superAdmin, $this->businessUnit->id);

        $this->assertTrue($this->hasSectionItem($navigation, 'Administration', 'Activity Admin Assignment'));
    }

    protected function createUser(string $email, bool $isActivityAdmin, string $globalRole = 'staff'): User
    {
        $user = User::create([
            'name' => $email,
            'email' => $email,
            'password' => bcrypt('password'),
            'phone_number' => '081234567800',
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->position->id,
            'global_role' => $globalRole,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
            'is_activity_admin' => $isActivityAdmin,
        ]);

        return $user;
    }

    /**
     * @param  array<int, array<string, mixed>>  $navigation
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

    /**
     * @param  array<int, array<string, mixed>>  $navigation
     */
    protected function hasSectionItem(array $navigation, string $sectionName, string $itemName): bool
    {
        foreach ($navigation['sections'] ?? [] as $section) {
            if (($section['name'] ?? null) !== $sectionName) {
                continue;
            }

            foreach ($section['items'] ?? [] as $item) {
                if (($item['name'] ?? null) === $itemName) {
                    return true;
                }
            }
        }

        return false;
    }
}
