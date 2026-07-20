<?php

namespace Tests\Feature\Modules\Ticket;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Services\Modules\Ticket\TicketDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TicketDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_period_metrics_volume_recent_and_workload_share_the_same_date_scope(): void
    {
        $businessUnit = BusinessUnit::factory()->create(['code' => 'WNS']);
        $department = Department::factory()->create(['business_unit_id' => $businessUnit->id]);
        $requester = User::factory()->create();
        $pramuji = User::factory()->create(['name' => 'Pramuji', 'email' => 'pramuji@werkudara.com']);

        DB::table('tickets')->insert([
            $this->ticketPayload($businessUnit->id, $department->id, $requester->id, $pramuji->id, 'IN-RANGE', '2026-06-17 09:00:00'),
            $this->ticketPayload($businessUnit->id, $department->id, $requester->id, $pramuji->id, 'OUTSIDE', '2026-03-01 09:00:00'),
        ]);

        $metrics = app(TicketDashboardService::class)->getMetrics(
            [$businessUnit->id],
            '2026-04-21',
            '2026-07-20',
        );

        $this->assertSame(1, $metrics['total']);
        $this->assertSame(1, $metrics['by_status']['done']);
        $this->assertSame([['name' => 'Pramuji', 'count' => 1]], $metrics['by_staff']);
        $this->assertSame([['date' => '2026-06-17', 'count' => 1]], $metrics['volume_by_day']);
        $this->assertCount(1, $metrics['recent_tickets']);
        $this->assertSame('IN-RANGE', $metrics['recent_tickets']->first()->ticket_number);
    }

    /** @return array<string, mixed> */
    private function ticketPayload(
        int $businessUnitId,
        int $departmentId,
        int $requesterId,
        int $assigneeId,
        string $number,
        string $createdAt,
    ): array {
        return [
            'business_unit_id' => $businessUnitId,
            'ticket_number' => $number,
            'title' => $number,
            'description' => 'Dashboard regression ticket',
            'requester_id' => $requesterId,
            'department_id' => $departmentId,
            'status' => 'done',
            'priority' => 'high',
            'category_id' => null,
            'assigned_to' => $assigneeId,
            'created_by' => $requesterId,
            'follow_up_at' => null,
            'resolved_at' => $createdAt,
            'form_token' => null,
            'import_source' => 'request.werkudara.com',
            'import_id' => $number,
            'created_at' => $createdAt,
            'updated_at' => '2026-07-20 09:00:00',
        ];
    }
}
