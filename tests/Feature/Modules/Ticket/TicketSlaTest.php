<?php

namespace Tests\Feature\Modules\Ticket;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\Ticket\Ticket;
use App\Models\Modules\Ticket\TicketSlaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketSlaTest extends TestCase
{
    use RefreshDatabase;

    protected BusinessUnit $businessUnit;

    protected Department $department;

    protected Position $position;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone_number' => '081234567800',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->position->id,
            'global_role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Seed SLA settings
        TicketSlaSettings::create([
            'business_unit_id' => $this->businessUnit->id,
            'priority' => 'low',
            'resolution_hours' => 48,
        ]);

        TicketSlaSettings::create([
            'business_unit_id' => $this->businessUnit->id,
            'priority' => 'medium',
            'resolution_hours' => 24,
        ]);

        TicketSlaSettings::create([
            'business_unit_id' => $this->businessUnit->id,
            'priority' => 'high',
            'resolution_hours' => 8,
        ]);

        TicketSlaSettings::create([
            'business_unit_id' => $this->businessUnit->id,
            'priority' => 'critical',
            'resolution_hours' => 2,
        ]);
    }

    protected function createTicket(string $priority, ?string $createdAt = null, ?string $resolvedAt = null): Ticket
    {
        $ticket = Ticket::create([
            'business_unit_id' => $this->businessUnit->id,
            'ticket_number' => 'IT.TBU/'.now()->format('Ym').'/'.str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
            'title' => 'SLA Test Ticket',
            'description' => 'Testing SLA',
            'requester_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => $resolvedAt ? 'done' : 'waiting',
            'priority' => $priority,
            'created_by' => $this->user->id,
            'resolved_at' => $resolvedAt,
        ]);

        if ($createdAt) {
            // Update created_at directly to simulate past creation
            $ticket->forceFill(['created_at' => $createdAt])->saveQuietly();
        }

        return $ticket->fresh();
    }

    #[Test]
    public function it_detects_sla_breach_for_overdue_ticket(): void
    {
        // Create a high-priority ticket from 10 hours ago (SLA = 8 hours)
        $ticket = $this->createTicket('high', now()->subHours(10)->toDateTimeString());

        $this->assertTrue($ticket->isSlaBreach());
    }

    #[Test]
    public function it_returns_no_breach_for_on_time_ticket(): void
    {
        // Create a high-priority ticket from 2 hours ago (SLA = 8 hours)
        $ticket = $this->createTicket('high', now()->subHours(2)->toDateTimeString());

        $this->assertFalse($ticket->isSlaBreach());
    }

    #[Test]
    public function it_uses_correct_sla_hours_per_priority(): void
    {
        // Verify SLA resolution hours per priority
        $this->assertSame(
            48,
            TicketSlaSettings::getResolutionHours($this->businessUnit->id, 'low')
        );

        $this->assertSame(
            24,
            TicketSlaSettings::getResolutionHours($this->businessUnit->id, 'medium')
        );

        $this->assertSame(
            8,
            TicketSlaSettings::getResolutionHours($this->businessUnit->id, 'high')
        );

        $this->assertSame(
            2,
            TicketSlaSettings::getResolutionHours($this->businessUnit->id, 'critical')
        );

        // Verify SLA deadline calculation
        $criticalTicket = $this->createTicket('critical');
        $deadline = $criticalTicket->sla_deadline;

        $this->assertNotNull($deadline);

        // Deadline should be ~2 hours from creation
        $expectedDeadline = $criticalTicket->created_at->copy()->addHours(2);
        $this->assertTrue(
            $deadline->diffInMinutes($expectedDeadline) < 1,
            'Critical ticket SLA deadline should be 2 hours from creation'
        );
    }
}
