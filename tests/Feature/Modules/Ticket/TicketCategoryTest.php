<?php

namespace Tests\Feature\Modules\Ticket;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\Ticket\TicketCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected BusinessUnit $businessUnit;

    protected BusinessUnit $otherBusinessUnit;

    protected Department $department;

    protected Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);

        $this->businessUnit = BusinessUnit::create([
            'name' => 'Test BU',
            'code' => 'TBU',
            'is_active' => true,
        ]);

        $this->otherBusinessUnit = BusinessUnit::create([
            'name' => 'Other BU',
            'code' => 'OBU',
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

        $this->admin = User::create([
            'name' => 'IT Admin',
            'email' => 'itadmin@example.com',
            'phone_number' => '081234567800',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->position->id,
            'global_role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->admin->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
            'is_it_support_admin' => true,
        ]);
    }

    #[Test]
    public function it_allows_admin_to_create_category(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->post(route('it-support.admin.categories.store'), [
                'name' => 'Network Issues',
                'description' => 'All network-related problems',
                'color' => '#FF5733',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('it-support.admin.categories.index'));

        $this->assertDatabaseHas('ticket_categories', [
            'name' => 'Network Issues',
            'business_unit_id' => $this->businessUnit->id,
            'description' => 'All network-related problems',
            'color' => '#FF5733',
        ]);
    }

    #[Test]
    public function it_allows_admin_to_update_category(): void
    {
        $category = TicketCategory::create([
            'business_unit_id' => $this->businessUnit->id,
            'name' => 'Old Name',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->put(route('it-support.admin.categories.update', $category), [
                'name' => 'Updated Name',
                'description' => 'Updated description',
            ]);

        $response->assertRedirect(route('it-support.admin.categories.index'));

        $this->assertSame('Updated Name', $category->fresh()->name);
    }

    #[Test]
    public function it_allows_admin_to_delete_category(): void
    {
        $category = TicketCategory::create([
            'business_unit_id' => $this->businessUnit->id,
            'name' => 'To Delete',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->delete(route('it-support.admin.categories.destroy', $category));

        $response->assertRedirect(route('it-support.admin.categories.index'));

        $this->assertDatabaseMissing('ticket_categories', [
            'id' => $category->id,
        ]);
    }

    #[Test]
    public function it_scopes_categories_to_business_unit(): void
    {
        // Create categories in different BUs
        TicketCategory::create([
            'business_unit_id' => $this->businessUnit->id,
            'name' => 'BU1 Category',
            'is_active' => true,
        ]);

        TicketCategory::create([
            'business_unit_id' => $this->otherBusinessUnit->id,
            'name' => 'BU2 Category',
            'is_active' => true,
        ]);

        // Verify scoping
        $bu1Categories = TicketCategory::where('business_unit_id', $this->businessUnit->id)->get();
        $bu2Categories = TicketCategory::where('business_unit_id', $this->otherBusinessUnit->id)->get();

        $this->assertCount(1, $bu1Categories);
        $this->assertSame('BU1 Category', $bu1Categories->first()->name);

        $this->assertCount(1, $bu2Categories);
        $this->assertSame('BU2 Category', $bu2Categories->first()->name);

        // Admin viewing categories should only see their BU's categories
        $response = $this->actingAs($this->admin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->get(route('it-support.admin.categories.index'));

        $response->assertOk();
    }
}
