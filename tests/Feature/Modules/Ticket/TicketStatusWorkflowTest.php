<?php

namespace Tests\Feature\Modules\Ticket;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\Ticket\Ticket;
use App\Services\Modules\Ticket\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketStatusWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected BusinessUnit $businessUnit;

    protected Department $department;

    protected Position $position;

    protected TicketService $ticketService;

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

        $this->ticketService = app(TicketService::class);
    }

    protected function createTicket(string $status = 'waiting'): Ticket
    {
        return Ticket::create([
            'business_unit_id' => $this->businessUnit->id,
            'ticket_number' => 'IT.TBU/'.now()->format('Ym').'/'.str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
            'title' => 'Test Ticket',
            'description' => 'Test description',
            'requester_id' => $this->admin->id,
            'department_id' => $this->department->id,
            'status' => $status,
            'priority' => 'medium',
            'created_by' => $this->admin->id,
        ]);
    }

    #[Test]
    public function it_allows_waiting_to_in_progress(): void
    {
        $ticket = $this->createTicket('waiting');

        $response = $this->actingAs($this->admin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->put(route('it-support.admin.tickets.changeStatus', $ticket), [
                'status' => 'in_progress',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame('in_progress', $ticket->fresh()->status);
    }

    #[Test]
    public function it_allows_waiting_to_cancelled(): void
    {
        $ticket = $this->createTicket('waiting');

        $response = $this->actingAs($this->admin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->put(route('it-support.admin.tickets.changeStatus', $ticket), [
                'status' => 'cancelled',
            ]);

        $response->assertRedirect();

        $this->assertSame('cancelled', $ticket->fresh()->status);
    }

    #[Test]
    public function it_allows_in_progress_to_done(): void
    {
        $ticket = $this->createTicket('in_progress');

        $response = $this->actingAs($this->admin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->put(route('it-support.admin.tickets.changeStatus', $ticket), [
                'status' => 'done',
            ]);

        $response->assertRedirect();

        $this->assertSame('done', $ticket->fresh()->status);
    }

    #[Test]
    public function it_allows_in_progress_to_cancelled(): void
    {
        $ticket = $this->createTicket('in_progress');

        $response = $this->actingAs($this->admin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->put(route('it-support.admin.tickets.changeStatus', $ticket), [
                'status' => 'cancelled',
            ]);

        $response->assertRedirect();

        $this->assertSame('cancelled', $ticket->fresh()->status);
    }

    #[Test]
    public function it_blocks_done_to_any_transition(): void
    {
        $ticket = $this->createTicket('done');

        // Try to move to in_progress
        $response = $this->actingAs($this->admin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->from(route('it-support.admin.tickets.show', $ticket))
            ->put(route('it-support.admin.tickets.changeStatus', $ticket), [
                'status' => 'in_progress',
            ]);

        $response->assertSessionHasErrors('status');

        $this->assertSame('done', $ticket->fresh()->status);
    }

    #[Test]
    public function it_blocks_cancelled_to_any_transition(): void
    {
        $ticket = $this->createTicket('cancelled');

        // Try to move to waiting
        $response = $this->actingAs($this->admin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->from(route('it-support.admin.tickets.show', $ticket))
            ->put(route('it-support.admin.tickets.changeStatus', $ticket), [
                'status' => 'waiting',
            ]);

        $response->assertSessionHasErrors('status');

        $this->assertSame('cancelled', $ticket->fresh()->status);
    }

    #[Test]
    public function it_sets_resolved_at_when_done(): void
    {
        $ticket = $this->createTicket('in_progress');

        $this->assertNull($ticket->resolved_at);

        $response = $this->actingAs($this->admin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
            ])
            ->put(route('it-support.admin.tickets.changeStatus', $ticket), [
                'status' => 'done',
            ]);

        $response->assertRedirect();

        $ticket->refresh();
        $this->assertSame('done', $ticket->status);
        $this->assertNotNull($ticket->resolved_at);
    }
}
