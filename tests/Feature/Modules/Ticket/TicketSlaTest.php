<?php

namespace Tests\Feature\Modules\Ticket;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\Ticket\Ticket;
use App\Models\Modules\Ticket\TicketSlaSettings;
use App\Services\Modules\Ticket\Reporting\SlaComplianceCalculator;
use App\Services\Modules\Ticket\SlaService;
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
            'resolution_hours' => 48,
        ]);

        TicketSlaSettings::create([
            'business_unit_id' => $this->businessUnit->id,
            'priority' => 'high',
            'resolution_hours' => 48,
        ]);

        TicketSlaSettings::create([
            'business_unit_id' => $this->businessUnit->id,
            'priority' => 'critical',
            'resolution_hours' => 48,
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
        // Every priority currently uses the same 48-hour policy.
        $ticket = $this->createTicket('high', now()->subHours(49)->toDateTimeString());

        $this->assertTrue($ticket->isSlaBreach());
    }

    #[Test]
    public function it_returns_no_breach_for_on_time_ticket(): void
    {
        $ticket = $this->createTicket('high', now()->subHours(47)->toDateTimeString());

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
            48,
            TicketSlaSettings::getResolutionHours($this->businessUnit->id, 'medium')
        );

        $this->assertSame(
            48,
            TicketSlaSettings::getResolutionHours($this->businessUnit->id, 'high')
        );

        $this->assertSame(
            48,
            TicketSlaSettings::getResolutionHours($this->businessUnit->id, 'critical')
        );

        // Verify SLA deadline calculation
        $criticalTicket = $this->createTicket('critical');
        $deadline = $criticalTicket->sla_deadline;

        $this->assertNotNull($deadline);

        $expectedDeadline = $criticalTicket->created_at->copy()->addHours(48);
        $this->assertTrue(
            $deadline->diffInMinutes($expectedDeadline) < 1,
            'Critical ticket SLA deadline should be 48 hours from creation'
        );
    }

    #[Test]
    public function it_uses_the_48_hour_default_when_bu_settings_are_missing(): void
    {
        TicketSlaSettings::query()->delete();

        $ticket = $this->createTicket(
            'critical',
            now()->subHours(49)->toDateTimeString(),
            now()->toDateTimeString(),
        );

        $this->assertSame(
            48,
            TicketSlaSettings::getResolutionHours($this->businessUnit->id, 'critical'),
        );
        $this->assertTrue($ticket->isSlaBreach());
        $this->assertTrue($ticket->sla_deadline->equalTo($ticket->created_at->copy()->addHours(48)));
    }

    #[Test]
    public function it_uses_the_same_48_hour_fallback_in_reporting(): void
    {
        TicketSlaSettings::query()->delete();

        $this->createTicket(
            'critical',
            now()->subHours(47)->toDateTimeString(),
            now()->toDateTimeString(),
        );
        $this->createTicket(
            'high',
            now()->subHours(49)->toDateTimeString(),
            now()->toDateTimeString(),
        );

        $compliance = app(SlaComplianceCalculator::class)->compliance(
            [$this->businessUnit->id],
            now()->subDays(3),
            now()->addDay(),
        );

        $this->assertSame(2, $compliance['total_resolved']);
        $this->assertSame(1, $compliance['within_sla']);
        $this->assertSame(1, $compliance['breached']);
        $this->assertSame(
            [48, 48, 48, 48],
            collect($compliance['by_priority'])->pluck('sla_hours')->all(),
        );
    }

    #[Test]
    public function it_enforces_48_hours_even_when_legacy_settings_contain_other_values(): void
    {
        TicketSlaSettings::where('business_unit_id', $this->businessUnit->id)
            ->where('priority', 'critical')
            ->update(['resolution_hours' => 2]);

        $ticket = $this->createTicket(
            'critical',
            now()->subHours(3)->toDateTimeString(),
            now()->toDateTimeString(),
        );

        $this->assertSame(
            48,
            TicketSlaSettings::getResolutionHours($this->businessUnit->id, 'critical'),
        );
        $this->assertFalse($ticket->isSlaBreach());

        $compliance = app(SlaComplianceCalculator::class)->compliance(
            [$this->businessUnit->id],
            now()->subDay(),
            now()->addDay(),
        );

        $this->assertSame(1, $compliance['within_sla']);
        $this->assertSame(0, $compliance['breached']);
        $this->assertSame(48, $compliance['by_priority'][0]['sla_hours']);
    }

    #[Test]
    public function sla_settings_updates_cannot_override_the_uniform_policy(): void
    {
        app(SlaService::class)->updateSettings($this->businessUnit->id, [
            'low' => 720,
            'medium' => 24,
            'high' => 8,
            'critical' => 2,
        ]);

        $this->assertSame(
            [48, 48, 48, 48],
            TicketSlaSettings::where('business_unit_id', $this->businessUnit->id)
                ->orderBy('priority')
                ->pluck('resolution_hours')
                ->all(),
        );
    }

    #[Test]
    public function migration_normalizes_existing_and_missing_sla_rows_for_every_business_unit(): void
    {
        $secondBusinessUnit = BusinessUnit::create([
            'name' => 'Second Test BU',
            'code' => 'TBU2',
            'is_active' => true,
        ]);

        TicketSlaSettings::where('business_unit_id', $this->businessUnit->id)
            ->where('priority', 'critical')
            ->update(['resolution_hours' => 2]);
        TicketSlaSettings::where('business_unit_id', $this->businessUnit->id)
            ->where('priority', 'medium')
            ->delete();
        TicketSlaSettings::create([
            'business_unit_id' => $secondBusinessUnit->id,
            'priority' => 'high',
            'resolution_hours' => 8,
        ]);

        $migration = require database_path('migrations/modules/ticket/2026_07_21_000001_standardize_ticket_sla_to_48_hours.php');
        $migration->up();
        $migration->up();

        foreach ([$this->businessUnit->id, $secondBusinessUnit->id] as $businessUnitId) {
            $settings = TicketSlaSettings::where('business_unit_id', $businessUnitId)
                ->orderBy('priority')
                ->get();

            $this->assertCount(4, $settings);
            $this->assertSame(
                ['critical', 'high', 'low', 'medium'],
                $settings->pluck('priority')->all(),
            );
            $this->assertSame([48, 48, 48, 48], $settings->pluck('resolution_hours')->all());
        }

        $beforeDown = TicketSlaSettings::query()->orderBy('id')->get()->toArray();
        $migration->down();

        $this->assertSame($beforeDown, TicketSlaSettings::query()->orderBy('id')->get()->toArray());
    }
}
