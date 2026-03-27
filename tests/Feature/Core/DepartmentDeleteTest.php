<?php

namespace Tests\Feature\Core;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DepartmentDeleteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function department_can_be_deleted_when_it_only_has_unassigned_positions(): void
    {
        config(['inertia.testing.ensure_pages_exist' => false]);

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super.admin@example.com',
            'phone_number' => '081234567800',
            'password' => bcrypt('password'),
            'global_role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $businessUnit = BusinessUnit::create([
            'name' => 'Maharaja Pratama',
            'code' => 'MP',
            'is_active' => true,
        ]);

        $department = Department::create([
            'business_unit_id' => $businessUnit->id,
            'code' => 'GA',
            'name' => 'General Administration',
            'is_active' => true,
        ]);

        $this->assertGreaterThan(0, Position::where('department_id', $department->id)->count());

        $response = $this->actingAs($superAdmin)->delete(route('admin.departments.destroy', $department));

        $response->assertRedirect(route('admin.departments.index'));
        $response->assertSessionHas('success', 'Department deleted successfully.');
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    #[Test]
    public function department_delete_is_blocked_when_users_are_assigned(): void
    {
        config(['inertia.testing.ensure_pages_exist' => false]);

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super.admin@example.com',
            'phone_number' => '081234567800',
            'password' => bcrypt('password'),
            'global_role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $businessUnit = BusinessUnit::create([
            'name' => 'Maharaja Pratama',
            'code' => 'MP',
            'is_active' => true,
        ]);

        $department = Department::create([
            'business_unit_id' => $businessUnit->id,
            'code' => 'GA',
            'name' => 'General Administration',
            'is_active' => true,
        ]);

        $position = Position::where('department_id', $department->id)->where('is_active', true)->firstOrFail();

        User::create([
            'name' => 'Assigned User',
            'email' => 'assigned.user@example.com',
            'phone_number' => '081234567801',
            'password' => bcrypt('password'),
            'primary_department_id' => $department->id,
            'primary_position_id' => $position->id,
            'global_role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($superAdmin)->delete(route('admin.departments.destroy', $department));

        $response->assertRedirect(route('admin.departments.index'));
        $response->assertSessionHas('error', 'Cannot delete department that has users assigned.');
        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }
}
