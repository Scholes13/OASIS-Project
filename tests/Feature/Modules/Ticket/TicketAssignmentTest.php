<?php

namespace Tests\Feature\Modules\Ticket;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\Ticket\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $itSupportUser;

    protected User $regularUser;

    protected BusinessUnit $businessUnit;

    protected Department $department;

    protected Position $position;

    protected Ticket $ticket;

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

        // Super admin who performs the assignment
        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
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

        // IT Support admin user (assignable)
        $this->itSupportUser = User::create([
            'name' => 'IT Support Staff',
            'email' => 'itsupport@example.com',
            'phone_number' => '081234567801',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->position->id,
            'global_role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->itSupportUser->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
            'is_it_support_admin' => true,
        ]);

        // Regular user (not assignable)
        $this->regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
            'phone_number' => '081234567802',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->position->id,
            'global_role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->regularUser->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
            'is_it_support_admin' => false,
        ]);

        $this->ticket = Ticket::create([
            'business_unit_id' => $this->businessUnit->id,
            'ticket_number' => 'IT.TBU/'.now()->format('Ym').'/001',
            'title' => 'Test Ticket',
            'description' => 'Test description',
            'requester_id' => $this->regularUser->id,
            'department_id' => $this->department->id,
            'status' => 'waiting',
            'priority' => 'medium',
            'created_by' => $this->regularUser->id,
        ]);
    }

    #[Test]
    public function it_allows_assignment_to_it_support_admin(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->post(route('it-support.admin.tickets.assign', $this->ticket), [
                'assigned_to' => $this->itSupportUser->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame($this->itSupportUser->id, $this->ticket->fresh()->assigned_to);
    }

    #[Test]
    public function it_blocks_assignment_to_non_it_support_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->from(route('it-support.admin.tickets.show', $this->ticket))
            ->post(route('it-support.admin.tickets.assign', $this->ticket), [
                'assigned_to' => $this->regularUser->id,
            ]);

        $response->assertSessionHasErrors('assigned_to');

        $this->assertNull($this->ticket->fresh()->assigned_to);
    }

    #[Test]
    public function it_allows_self_assignment(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->post(route('it-support.admin.tickets.assign', $this->ticket), [
                'assigned_to' => $this->admin->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame($this->admin->id, $this->ticket->fresh()->assigned_to);
    }
}
