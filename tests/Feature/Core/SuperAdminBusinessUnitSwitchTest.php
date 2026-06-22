<?php

namespace Tests\Feature\Core;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SuperAdminBusinessUnitSwitchTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function super_admin_keeps_selected_business_unit_when_target_has_no_logo(): void
    {
        config(['inertia.testing.ensure_pages_exist' => false]);

        $primaryBusinessUnit = BusinessUnit::create([
            'code' => 'WG',
            'name' => 'Werkudara Group',
            'logo' => 'business-units/wg-logo.png',
            'is_active' => true,
        ]);

        $targetBusinessUnit = BusinessUnit::create([
            'code' => 'TEE',
            'name' => 'Takshaka',
            'parent_id' => $primaryBusinessUnit->id,
            'logo' => null,
            'is_active' => true,
        ]);

        $primaryDepartment = Department::create([
            'business_unit_id' => $primaryBusinessUnit->id,
            'name' => 'System Administration',
            'code' => 'SYSADMIN',
            'is_active' => true,
        ]);

        $primaryPosition = Position::create([
            'department_id' => $primaryDepartment->id,
            'name' => 'Super Administrator',
            'code' => 'SUPERADMIN',
            'level' => 'c_level',
            'access_level' => 'executive',
            'hierarchy_level' => 0,
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567890',
            'primary_department_id' => $primaryDepartment->id,
            'primary_position_id' => $primaryPosition->id,
            'global_role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->businessUnits()->create([
            'business_unit_id' => $primaryBusinessUnit->id,
            'department_id' => $primaryDepartment->id,
            'position_id' => $primaryPosition->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user)->withSession([
            'current_business_unit_id' => $primaryBusinessUnit->id,
            'current_business_unit_code' => $primaryBusinessUnit->code,
            'current_business_unit_name' => $primaryBusinessUnit->name,
            'current_business_unit_logo' => $primaryBusinessUnit->logo,
            'current_department_id' => $primaryDepartment->id,
        ]);

        $switchResponse = $this->post(route('api.business-unit.switch'), [
            'business_unit_id' => $targetBusinessUnit->id,
        ]);

        $switchResponse->assertRedirect();
        $this->assertSame($targetBusinessUnit->id, session('current_business_unit_id'));
        $this->assertSame($targetBusinessUnit->code, session('current_business_unit_code'));
        $this->assertSame($targetBusinessUnit->name, session('current_business_unit_name'));
        $this->assertNull(session('current_business_unit_logo'));

        $profileResponse = $this->get(route('profile'));

        $profileResponse->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Index')
            ->where('currentBusinessUnit.id', $targetBusinessUnit->id)
            ->where('currentBusinessUnit.code', $targetBusinessUnit->code)
            ->where('currentBusinessUnit.name', $targetBusinessUnit->name)
        );

        $this->assertSame($targetBusinessUnit->id, session('current_business_unit_id'));
        $this->assertSame($targetBusinessUnit->code, session('current_business_unit_code'));
        $this->assertSame($targetBusinessUnit->name, session('current_business_unit_name'));
        $this->assertNull(session('current_business_unit_logo'));
    }
}
