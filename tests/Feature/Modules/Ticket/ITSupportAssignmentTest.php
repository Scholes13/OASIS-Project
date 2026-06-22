<?php

namespace Tests\Feature\Modules\Ticket;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ITSupportAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $regularUser;

    protected BusinessUnit $businessUnit;

    protected Department $department;

    protected Position $position;

    protected UserBusinessUnit $regularUbu;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);

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

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'phone_number' => '081234567800',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->position->id,
            'global_role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->superAdmin->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
            'phone_number' => '081234567801',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->position->id,
            'global_role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->regularUbu = $this->regularUser->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
            'is_it_support_admin' => false,
            'is_it_support_report_access' => false,
        ]);
    }

    #[Test]
    public function it_shows_assignment_page_for_super_admin(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->get(route('admin.it-support-admins.index'));

        $response->assertOk();
    }

    #[Test]
    public function it_toggles_it_support_admin_flag(): void
    {
        $this->assertFalse($this->regularUbu->fresh()->is_it_support_admin);

        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->post(route('admin.it-support-admins.toggle', $this->regularUbu->id));

        $response->assertRedirect();

        $this->assertTrue($this->regularUbu->fresh()->is_it_support_admin);

        // Toggle back off
        $this->actingAs($this->superAdmin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->post(route('admin.it-support-admins.toggle', $this->regularUbu->id));

        $this->assertFalse($this->regularUbu->fresh()->is_it_support_admin);
    }

    #[Test]
    public function it_auto_revokes_report_access_when_admin_turned_off(): void
    {
        // First enable admin + report access
        $this->regularUbu->update([
            'is_it_support_admin' => true,
            'is_it_support_report_access' => true,
        ]);

        $this->assertTrue($this->regularUbu->fresh()->is_it_support_report_access);

        // Toggle admin OFF — should auto-revoke report access
        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->post(route('admin.it-support-admins.toggle', $this->regularUbu->id));

        $response->assertRedirect();

        $fresh = $this->regularUbu->fresh();
        $this->assertFalse($fresh->is_it_support_admin);
        $this->assertFalse($fresh->is_it_support_report_access);
    }

    #[Test]
    public function it_blocks_report_access_toggle_when_admin_is_off(): void
    {
        // Ensure admin is OFF
        $this->regularUbu->update([
            'is_it_support_admin' => false,
            'is_it_support_report_access' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->post(route('admin.it-support-admins.toggle-report', $this->regularUbu->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertFalse($this->regularUbu->fresh()->is_it_support_report_access);
    }

    #[Test]
    public function it_denies_access_to_non_admin_users(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->get(route('admin.it-support-admins.index'));

        $response->assertForbidden();
    }
}
