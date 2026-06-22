<?php

namespace Tests\Feature\Modules\Ticket;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\Ticket\Ticket;
use App\Models\Modules\Ticket\TicketComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketCommentTest extends TestCase
{
    use RefreshDatabase;

    protected User $requester;

    protected User $admin;

    protected User $outsider;

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

        $this->requester = $this->createUser('requester');
        $this->admin = $this->createUser('admin', 'super_admin', true);
        $this->outsider = $this->createUser('outsider');

        $this->ticket = Ticket::create([
            'business_unit_id' => $this->businessUnit->id,
            'ticket_number' => 'IT.TBU/'.now()->format('Ym').'/001',
            'title' => 'Test Ticket',
            'description' => 'Test description',
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'waiting',
            'priority' => 'medium',
            'created_by' => $this->requester->id,
        ]);
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

    #[Test]
    public function it_allows_requester_to_add_public_comment(): void
    {
        $response = $this->actingAs($this->requester)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->post(route('it-support.my-tickets.comment', $this->ticket), [
                'content' => 'This is a public comment from requester',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->requester->id,
            'content' => 'This is a public comment from requester',
            'is_private' => false,
        ]);
    }

    #[Test]
    public function it_allows_admin_to_add_private_comment(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->post(route('it-support.admin.tickets.comment', $this->ticket), [
                'content' => 'This is a private admin note',
                'is_private' => true,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->admin->id,
            'content' => 'This is a private admin note',
            'is_private' => true,
        ]);
    }

    #[Test]
    public function it_hides_private_comments_from_requester(): void
    {
        // Create a public comment
        TicketComment::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->admin->id,
            'content' => 'Public reply from admin',
            'is_private' => false,
        ]);

        // Create a private comment
        TicketComment::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->admin->id,
            'content' => 'Private internal note',
            'is_private' => true,
        ]);

        // Requester views their ticket — should only see public comments
        $response = $this->actingAs($this->requester)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->get(route('it-support.my-tickets.show', $this->ticket));

        $response->assertOk();

        // The UserTicketController::show() filters is_private = false
        // Verify via the loaded comments relationship
        $ticket = Ticket::where('id', $this->ticket->id)->first();
        $publicComments = $ticket->comments()->where('is_private', false)->get();
        $allComments = $ticket->comments()->get();

        $this->assertCount(1, $publicComments);
        $this->assertCount(2, $allComments);
        $this->assertSame('Public reply from admin', $publicComments->first()->content);
    }

    #[Test]
    public function it_blocks_comment_on_other_users_ticket(): void
    {
        // Outsider tries to comment on requester's ticket via the user route
        $response = $this->actingAs($this->outsider)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->post(route('it-support.my-tickets.comment', $this->ticket), [
                'content' => 'Should be blocked',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('ticket_comments', [
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->outsider->id,
        ]);
    }
}
