<?php

namespace Tests\Feature\Modules\Ticket;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\Ticket\Ticket;
use App\Models\Modules\Ticket\TicketCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $requester;

    protected User $otherUser;

    protected User $admin;

    protected BusinessUnit $businessUnit;

    protected BusinessUnit $otherBusinessUnit;

    protected Department $department;

    protected Position $position;

    protected TicketCategory $category;

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

        $this->category = TicketCategory::create([
            'business_unit_id' => $this->businessUnit->id,
            'name' => 'Hardware',
            'is_active' => true,
        ]);

        $this->requester = $this->createUser('requester');
        $this->otherUser = $this->createUser('other');
        $this->admin = $this->createUser('admin', 'super_admin', true);
    }

    protected function createUser(string $prefix, string $globalRole = 'user', bool $isItAdmin = false): User
    {
        $user = User::create([
            'name' => ucfirst($prefix).' User',
            'email' => $prefix.'@example.com',
            'phone_number' => '08'.rand(1000000000, 9999999999),
            'password' => bcrypt('password'),
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
            'is_it_support_admin' => $isItAdmin,
        ]);

        return $user;
    }

    protected function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'business_unit_id' => $this->businessUnit->id,
            'ticket_number' => 'IT.TBU/'.now()->format('Ym').'/'.str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
            'title' => 'Test Ticket',
            'description' => 'Test description',
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'waiting',
            'priority' => 'medium',
            'category_id' => $this->category->id,
            'created_by' => $this->requester->id,
        ], $overrides));
    }

    #[Test]
    public function it_allows_authenticated_user_to_create_ticket(): void
    {
        $response = $this->actingAs($this->requester)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->post(route('it-support.submit.store'), [
                'title' => 'My laptop is broken',
                'description' => 'Screen flickering issue',
                'priority' => 'high',
                'category_id' => $this->category->id,
                'form_token' => 'unique-token-123',
            ]);

        $response->assertRedirect(route('it-support.my-tickets'));

        $this->assertDatabaseHas('tickets', [
            'title' => 'My laptop is broken',
            'requester_id' => $this->requester->id,
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'priority' => 'high',
            'status' => 'waiting',
        ]);
    }

    #[Test]
    public function it_rejects_category_outside_current_business_unit(): void
    {
        $otherCategory = TicketCategory::create([
            'business_unit_id' => $this->otherBusinessUnit->id,
            'name' => 'Other Hardware',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->requester)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->post(route('it-support.submit.store'), [
                'title' => 'Wrong BU category',
                'description' => 'Should fail validation',
                'priority' => 'high',
                'category_id' => $otherCategory->id,
            ]);

        $response->assertSessionHasErrors('category_id');
    }

    #[Test]
    public function it_scopes_tickets_to_business_unit(): void
    {
        // Create ticket in main BU
        $this->createTicket(['title' => 'BU1 Ticket']);

        // Create ticket in other BU
        $otherDept = Department::create([
            'name' => 'Other Dept',
            'code' => 'ODP',
            'business_unit_id' => $this->otherBusinessUnit->id,
            'is_active' => true,
        ]);

        Ticket::create([
            'business_unit_id' => $this->otherBusinessUnit->id,
            'ticket_number' => 'IT.OBU/'.now()->format('Ym').'/001',
            'title' => 'BU2 Ticket',
            'description' => 'Other BU ticket',
            'requester_id' => $this->requester->id,
            'department_id' => $otherDept->id,
            'status' => 'waiting',
            'priority' => 'medium',
            'created_by' => $this->requester->id,
        ]);

        // My tickets should only show tickets for the current BU
        $response = $this->actingAs($this->requester)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->get(route('it-support.my-tickets'));

        $response->assertOk();

        // Verify BU scoping via database query
        $myTickets = Ticket::where('requester_id', $this->requester->id)
            ->where('business_unit_id', $this->businessUnit->id)
            ->get();

        $this->assertCount(1, $myTickets);
        $this->assertSame('BU1 Ticket', $myTickets->first()->title);
    }

    #[Test]
    public function it_shows_my_tickets_only_for_requester(): void
    {
        $this->createTicket(['title' => 'My Ticket', 'requester_id' => $this->requester->id]);
        $this->createTicket([
            'title' => 'Other Ticket',
            'requester_id' => $this->otherUser->id,
            'ticket_number' => 'IT.TBU/'.now()->format('Ym').'/002',
            'created_by' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->requester)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->get(route('it-support.my-tickets'));

        $response->assertOk();

        // Verify only requester's tickets are returned
        $myTickets = Ticket::where('requester_id', $this->requester->id)
            ->where('business_unit_id', $this->businessUnit->id)
            ->get();

        $this->assertCount(1, $myTickets);
        $this->assertSame('My Ticket', $myTickets->first()->title);
    }

    #[Test]
    public function it_allows_admin_to_view_all_tickets(): void
    {
        $this->createTicket(['title' => 'Ticket A']);
        $this->createTicket([
            'title' => 'Ticket B',
            'requester_id' => $this->otherUser->id,
            'ticket_number' => 'IT.TBU/'.now()->format('Ym').'/002',
            'created_by' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->get(route('it-support.admin.tickets.index'));

        $response->assertOk();
    }

    #[Test]
    public function it_prevents_duplicate_submission_via_form_token(): void
    {
        $formToken = 'unique-form-token-abc';

        // First submission
        $response1 = $this->actingAs($this->requester)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->post(route('it-support.submit.store'), [
                'title' => 'Duplicate test ticket',
                'description' => 'Testing duplicate prevention',
                'priority' => 'medium',
                'form_token' => $formToken,
            ]);

        $response1->assertRedirect();

        $ticketCount = Ticket::where('form_token', $formToken)->count();
        $this->assertSame(1, $ticketCount);

        // Second submission with same token — should return existing ticket, not create new
        $response2 = $this->actingAs($this->requester)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->post(route('it-support.submit.store'), [
                'title' => 'Duplicate test ticket',
                'description' => 'Testing duplicate prevention',
                'priority' => 'medium',
                'form_token' => $formToken,
            ]);

        $response2->assertRedirect();

        // Still only 1 ticket with this form_token
        $ticketCount = Ticket::where('form_token', $formToken)->count();
        $this->assertSame(1, $ticketCount);
    }
}
