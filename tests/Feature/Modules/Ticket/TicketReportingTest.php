<?php

namespace Tests\Feature\Modules\Ticket;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\Ticket\Ticket;
use App\Models\Modules\Ticket\TicketCategory;
use App\Services\Modules\Ticket\TicketReportingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketReportingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected BusinessUnit $businessUnit;

    protected BusinessUnit $otherBusinessUnit;

    protected Department $department;

    protected Position $position;

    protected TicketCategory $category;

    protected TicketReportingService $reportingService;

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

        $this->category = TicketCategory::create([
            'business_unit_id' => $this->businessUnit->id,
            'name' => 'Hardware',
            'is_active' => true,
        ]);

        $this->reportingService = app(TicketReportingService::class);
    }

    protected function createTicket(array $overrides = []): Ticket
    {
        static $counter = 0;
        $counter++;

        $ticket = Ticket::create(array_merge([
            'business_unit_id' => $this->businessUnit->id,
            'ticket_number' => 'IT.TBU/'.now()->format('Ym').'/'.str_pad($counter, 3, '0', STR_PAD_LEFT),
            'title' => 'Report Test Ticket '.$counter,
            'description' => 'Test description',
            'requester_id' => $this->admin->id,
            'department_id' => $this->department->id,
            'status' => 'waiting',
            'priority' => 'medium',
            'category_id' => $this->category->id,
            'created_by' => $this->admin->id,
        ], $overrides));

        return $ticket;
    }

    #[Test]
    public function it_returns_correct_metrics_by_status(): void
    {
        // Create tickets with different statuses
        $this->createTicket(['status' => 'waiting']);
        $this->createTicket(['status' => 'waiting']);
        $this->createTicket(['status' => 'in_progress']);
        $this->createTicket(['status' => 'done', 'resolved_at' => now()]);
        $this->createTicket(['status' => 'cancelled']);

        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        $metrics = $this->reportingService->getMetricsByStatus(
            [$this->businessUnit->id],
            $from,
            $to
        );

        // Convert to keyed array for easier assertion
        $byStatus = collect($metrics)->pluck('count', 'status');

        $this->assertSame(2, $byStatus->get('waiting'));
        $this->assertSame(1, $byStatus->get('in_progress'));
        $this->assertSame(1, $byStatus->get('done'));
        $this->assertSame(1, $byStatus->get('cancelled'));
    }

    #[Test]
    public function it_returns_correct_avg_resolution_time(): void
    {
        // Create resolved tickets with known resolution times
        $ticket1 = $this->createTicket([
            'status' => 'done',
            'resolved_at' => now(),
        ]);
        // Set created_at to 4 hours ago
        $ticket1->forceFill(['created_at' => now()->subHours(4)])->saveQuietly();

        $ticket2 = $this->createTicket([
            'status' => 'done',
            'resolved_at' => now(),
        ]);
        // Set created_at to 8 hours ago
        $ticket2->forceFill(['created_at' => now()->subHours(8)])->saveQuietly();

        // Create an unresolved ticket (should not affect avg)
        $this->createTicket(['status' => 'waiting']);

        $from = Carbon::now()->subDay();
        $to = Carbon::now()->addDay();

        $avgHours = $this->reportingService->getAvgResolutionTime(
            [$this->businessUnit->id],
            $from,
            $to
        );

        // Average should be approximately 6 hours ((4 + 8) / 2)
        $this->assertGreaterThan(5.0, $avgHours);
        $this->assertLessThan(7.0, $avgHours);
    }

    #[Test]
    public function it_scopes_report_to_business_unit(): void
    {
        // Create tickets in main BU
        $this->createTicket(['status' => 'waiting']);
        $this->createTicket(['status' => 'done', 'resolved_at' => now()]);

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
            'title' => 'Other BU Ticket',
            'description' => 'Should not appear in report',
            'requester_id' => $this->admin->id,
            'department_id' => $otherDept->id,
            'status' => 'waiting',
            'priority' => 'high',
            'created_by' => $this->admin->id,
        ]);

        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        // Report for main BU only
        $metrics = $this->reportingService->getMetricsByStatus(
            [$this->businessUnit->id],
            $from,
            $to
        );

        $totalCount = collect($metrics)->sum('count');
        $this->assertSame(2, $totalCount);

        // Report for other BU only
        $otherMetrics = $this->reportingService->getMetricsByStatus(
            [$this->otherBusinessUnit->id],
            $from,
            $to
        );

        $otherTotalCount = collect($otherMetrics)->sum('count');
        $this->assertSame(1, $otherTotalCount);
    }
}
