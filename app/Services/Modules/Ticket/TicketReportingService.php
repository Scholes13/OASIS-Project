<?php

namespace App\Services\Modules\Ticket;

use App\Models\Modules\Ticket\Ticket;
use App\Services\Modules\Ticket\Reporting\SlaComplianceCalculator;
use App\Services\Modules\Ticket\Reporting\TicketMetricsCalculator;
use App\Services\Modules\Ticket\Reporting\TicketTrendCalculator;
use Carbon\Carbon;

/**
 * Assemble the ticket reporting payload consumed by the reporting Inertia
 * page and Excel export.
 *
 * The heavy lifting (per-status/priority/category/staff aggregation, trend
 * analysis, SLA compliance) lives in dedicated calculators under the
 * {@see \App\Services\Modules\Ticket\Reporting} namespace; this service
 * stitches their output together and resolves the requested date range.
 */
class TicketReportingService
{
    public function __construct(
        protected TicketMetricsCalculator $metricsCalculator,
        protected TicketTrendCalculator $trendCalculator,
        protected SlaComplianceCalculator $slaCalculator,
    ) {}

    /**
     * Build the full report payload for the given filters.
     *
     * @param  array<int>  $buIds
     * @return array<string, mixed>
     */
    public function getReportData(
        array $buIds,
        string $period,
        ?string $dateFrom,
        ?string $dateTo
    ): array {
        [$from, $to] = $this->resolveDateRange($period, $dateFrom, $dateTo);

        $byStatus = $this->metricsCalculator->byStatus($buIds, $from, $to);
        $byStatusCollection = collect($byStatus);

        $totalTickets = $byStatusCollection->sum('value');
        $resolvedTickets = $byStatusCollection->firstWhere('name', 'Done')['value'] ?? 0;

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'label' => $period,
            ],
            'total_tickets' => $totalTickets,
            'resolved_tickets' => $resolvedTickets,
            'avg_resolution_hours' => $this->metricsCalculator->avgResolutionHours($buIds, $from, $to),
            'by_status' => $byStatus,
            'by_priority' => $this->metricsCalculator->byPriority($buIds, $from, $to),
            'by_category' => $this->metricsCalculator->byCategory($buIds, $from, $to),
            'by_staff' => $this->metricsCalculator->byStaff($buIds, $from, $to),
            'daily_trend' => $this->trendCalculator->trend($buIds, $from, $to),
            'sla_compliance' => $this->slaCalculator->compliance($buIds, $from, $to),
        ];
    }

    /**
     * Get ticket counts grouped by status.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{name: string, value: int, color: string}>
     */
    public function getMetricsByStatus(array $buIds, Carbon $from, Carbon $to): array
    {
        return $this->metricsCalculator->byStatus($buIds, $from, $to);
    }

    /**
     * Get ticket counts grouped by priority.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{name: string, count: int, color: string}>
     */
    public function getMetricsByPriority(array $buIds, Carbon $from, Carbon $to): array
    {
        return $this->metricsCalculator->byPriority($buIds, $from, $to);
    }

    /**
     * Get ticket counts grouped by category.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{name: string, count: int, color: string}>
     */
    public function getMetricsByCategory(array $buIds, Carbon $from, Carbon $to): array
    {
        return $this->metricsCalculator->byCategory($buIds, $from, $to);
    }

    /**
     * Get ticket counts grouped by assigned staff.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{name: string, count: int, color: string}>
     */
    public function getMetricsByStaff(array $buIds, Carbon $from, Carbon $to): array
    {
        return $this->metricsCalculator->byStaff($buIds, $from, $to);
    }

    /**
     * Average resolution time in hours for resolved tickets.
     *
     * @param  array<int>  $buIds
     */
    public function getAvgResolutionTime(array $buIds, Carbon $from, Carbon $to): float
    {
        return $this->metricsCalculator->avgResolutionHours($buIds, $from, $to);
    }

    /**
     * Get SLA compliance metrics for resolved tickets.
     *
     * @param  array<int>  $buIds
     * @return array{rate: float, total_resolved: int, within_sla: int, breached: int, by_priority: array}
     */
    public function getSlaCompliance(array $buIds, Carbon $from, Carbon $to): array
    {
        return $this->slaCalculator->compliance($buIds, $from, $to);
    }

    /**
     * Get ticket creation trend (daily or weekly counts).
     *
     * @param  array<int>  $buIds
     * @return array<int, array{date: string, total: int, resolved: int}>
     */
    public function getTicketTrend(array $buIds, Carbon $from, Carbon $to): array
    {
        return $this->trendCalculator->trend($buIds, $from, $to);
    }

    /**
     * Prepare data for Excel export.
     *
     * @param  array<int>  $buIds
     * @return array<int, array<string, mixed>>
     */
    public function prepareExportData(array $buIds, Carbon $from, Carbon $to): array
    {
        return Ticket::forBusinessUnits($buIds)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->with(['requester', 'assignedUser', 'category', 'department', 'businessUnit'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Ticket $ticket): array => [
                'ticket_number' => $ticket->ticket_number,
                'title' => $ticket->title,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'category' => $ticket->category?->name ?? 'Uncategorized',
                'requester' => $ticket->requester?->name ?? '-',
                'assigned_to' => $ticket->assignedUser?->name ?? 'Unassigned',
                'department' => $ticket->department?->name ?? '-',
                'business_unit' => $ticket->businessUnit?->name ?? '-',
                'created_at' => $ticket->created_at?->format('Y-m-d H:i:s'),
                'resolved_at' => $ticket->resolved_at?->format('Y-m-d H:i:s') ?? '-',
                'processing_time' => $ticket->processing_time ?? '-',
                'sla_breached' => $ticket->isSlaBreach() ? 'Yes' : 'No',
            ])
            ->all();
    }

    /**
     * Resolve date range from period string or explicit dates.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolveDateRange(string $period, ?string $dateFrom, ?string $dateTo): array
    {
        if ($dateFrom && $dateTo) {
            return [Carbon::parse($dateFrom), Carbon::parse($dateTo)];
        }

        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'this_quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }
}
