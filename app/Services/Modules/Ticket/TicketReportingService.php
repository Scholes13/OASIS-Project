<?php

namespace App\Services\Modules\Ticket;

use App\Models\Modules\Ticket\Ticket;
use App\Models\Modules\Ticket\TicketSlaSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TicketReportingService
{
    /**
     * Get report data filtered by period.
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

        $byStatus = $this->getMetricsByStatus($buIds, $from, $to);
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
            'avg_resolution_hours' => $this->getAvgResolutionTime($buIds, $from, $to),
            'by_status' => $byStatus,
            'by_priority' => $this->getMetricsByPriority($buIds, $from, $to),
            'by_category' => $this->getMetricsByCategory($buIds, $from, $to),
            'by_staff' => $this->getMetricsByStaff($buIds, $from, $to),
            'daily_trend' => $this->getTicketTrend($buIds, $from, $to),
            'sla_compliance' => $this->getSlaCompliance($buIds, $from, $to),
        ];
    }

    /**
     * Status display names and colors for chart rendering.
     */
    private const STATUS_META = [
        'waiting' => ['name' => 'Menunggu', 'color' => '#f59e0b'],
        'in_progress' => ['name' => 'Dalam Proses', 'color' => '#3b82f6'],
        'done' => ['name' => 'Done', 'color' => '#10b981'],
        'cancelled' => ['name' => 'Dibatalkan', 'color' => '#6b7280'],
    ];

    /**
     * Priority display names and colors for chart rendering.
     */
    private const PRIORITY_META = [
        'low' => ['name' => 'Rendah', 'color' => '#94a3b8'],
        'medium' => ['name' => 'Sedang', 'color' => '#3b82f6'],
        'high' => ['name' => 'Tinggi', 'color' => '#f59e0b'],
        'critical' => ['name' => 'Kritis', 'color' => '#ef4444'],
    ];

    /**
     * Get ticket counts grouped by status.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{name: string, value: int, color: string}>
     */
    public function getMetricsByStatus(array $buIds, Carbon $from, Carbon $to): array
    {
        $results = Ticket::forBusinessUnits($buIds)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $output = [];

        foreach (self::STATUS_META as $status => $meta) {
            $count = (int) ($results->get($status)?->count ?? 0);

            if ($count > 0) {
                $output[] = [
                    'name' => $meta['name'],
                    'value' => $count,
                    'color' => $meta['color'],
                ];
            }
        }

        return $output;
    }

    /**
     * Get ticket counts grouped by priority.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{name: string, count: int, color: string}>
     */
    public function getMetricsByPriority(array $buIds, Carbon $from, Carbon $to): array
    {
        $results = Ticket::forBusinessUnits($buIds)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->get()
            ->keyBy('priority');

        $output = [];

        foreach (self::PRIORITY_META as $priority => $meta) {
            $output[] = [
                'name' => $meta['name'],
                'count' => (int) ($results->get($priority)?->count ?? 0),
                'color' => $meta['color'],
            ];
        }

        return $output;
    }

    /**
     * Get ticket counts grouped by category.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{name: string, count: int, color: string}>
     */
    public function getMetricsByCategory(array $buIds, Carbon $from, Carbon $to): array
    {
        return Ticket::whereIn('tickets.business_unit_id', $buIds)
            ->whereBetween('tickets.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->leftJoin('ticket_categories', 'tickets.category_id', '=', 'ticket_categories.id')
            ->select(
                'tickets.category_id',
                DB::raw("COALESCE(ticket_categories.name, 'Uncategorized') as name"),
                DB::raw("COALESCE(ticket_categories.color, '#6b7280') as color"),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('tickets.category_id', 'ticket_categories.name', 'ticket_categories.color')
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->name,
                'count' => (int) $row->count,
                'color' => $row->color,
            ])
            ->all();
    }

    /**
     * Get ticket counts grouped by assigned staff.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{name: string, count: int, color: string}>
     */
    public function getMetricsByStaff(array $buIds, Carbon $from, Carbon $to): array
    {
        return Ticket::whereIn('tickets.business_unit_id', $buIds)
            ->whereBetween('tickets.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->whereNotNull('tickets.assigned_to')
            ->leftJoin('users', 'tickets.assigned_to', '=', 'users.id')
            ->select(
                'tickets.assigned_to',
                DB::raw("COALESCE(users.name, 'Unassigned') as name"),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('tickets.assigned_to', 'users.name')
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->name,
                'count' => (int) $row->count,
                'color' => '#6366f1',
            ])
            ->all();
    }

    /**
     * Get average resolution time in hours for resolved tickets.
     *
     * @param  array<int>  $buIds
     */
    public function getAvgResolutionTime(array $buIds, Carbon $from, Carbon $to): float
    {
        $tickets = Ticket::forBusinessUnits($buIds)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->whereNotNull('resolved_at')
            ->select('created_at', 'resolved_at')
            ->get();

        if ($tickets->isEmpty()) {
            return 0.0;
        }

        $totalHours = $tickets->sum(function ($ticket): float {
            return $ticket->created_at->diffInMinutes($ticket->resolved_at) / 60;
        });

        return round($totalHours / $tickets->count(), 2);
    }

    /**
     * Get SLA compliance metrics for resolved tickets.
     *
     * @param  array<int>  $buIds
     * @return array{rate: float, total_resolved: int, within_sla: int, breached: int, by_priority: array}
     */
    public function getSlaCompliance(array $buIds, Carbon $from, Carbon $to): array
    {
        // Get all resolved tickets in the period
        $resolvedTickets = Ticket::forBusinessUnits($buIds)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->whereNotNull('resolved_at')
            ->select('id', 'business_unit_id', 'priority', 'created_at', 'resolved_at')
            ->get();

        if ($resolvedTickets->isEmpty()) {
            return [
                'rate' => 0.0,
                'total_resolved' => 0,
                'within_sla' => 0,
                'breached' => 0,
                'by_priority' => $this->getEmptySlaPriorityBreakdown(),
            ];
        }

        // Load SLA settings for all relevant BUs
        $slaMap = TicketSlaSettings::whereIn('business_unit_id', $buIds)
            ->get()
            ->groupBy('business_unit_id')
            ->map(fn ($settings) => $settings->keyBy('priority'));

        // Default SLA hours if no settings exist
        $defaultSla = ['low' => 48, 'medium' => 24, 'high' => 8, 'critical' => 2];

        $withinSla = 0;
        $breached = 0;
        $byPriority = [
            'low' => ['total' => 0, 'within_sla' => 0, 'breached' => 0, 'avg_hours' => 0, 'sla_hours' => 0, 'total_hours' => 0],
            'medium' => ['total' => 0, 'within_sla' => 0, 'breached' => 0, 'avg_hours' => 0, 'sla_hours' => 0, 'total_hours' => 0],
            'high' => ['total' => 0, 'within_sla' => 0, 'breached' => 0, 'avg_hours' => 0, 'sla_hours' => 0, 'total_hours' => 0],
            'critical' => ['total' => 0, 'within_sla' => 0, 'breached' => 0, 'avg_hours' => 0, 'sla_hours' => 0, 'total_hours' => 0],
        ];

        foreach ($resolvedTickets as $ticket) {
            $priority = $ticket->priority;
            $buSla = $slaMap->get($ticket->business_unit_id);
            $slaHours = $buSla?->get($priority)?->resolution_hours ?? ($defaultSla[$priority] ?? 24);
            $actualHours = $ticket->created_at->diffInMinutes($ticket->resolved_at) / 60;

            $isWithinSla = $actualHours <= $slaHours;

            if ($isWithinSla) {
                $withinSla++;
            } else {
                $breached++;
            }

            if (isset($byPriority[$priority])) {
                $byPriority[$priority]['total']++;
                $byPriority[$priority]['sla_hours'] = $slaHours;
                $byPriority[$priority]['total_hours'] += $actualHours;

                if ($isWithinSla) {
                    $byPriority[$priority]['within_sla']++;
                } else {
                    $byPriority[$priority]['breached']++;
                }
            }
        }

        $totalResolved = $resolvedTickets->count();
        $rate = $totalResolved > 0 ? round(($withinSla / $totalResolved) * 100, 1) : 0.0;

        // Calculate avg hours per priority
        $byPriorityOutput = [];
        $priorityLabels = ['critical' => 'Kritis', 'high' => 'Tinggi', 'medium' => 'Sedang', 'low' => 'Rendah'];

        foreach (['critical', 'high', 'medium', 'low'] as $p) {
            $data = $byPriority[$p];
            $avgHours = $data['total'] > 0 ? round($data['total_hours'] / $data['total'], 1) : 0;
            $complianceRate = $data['total'] > 0 ? round(($data['within_sla'] / $data['total']) * 100, 1) : 0;

            $byPriorityOutput[] = [
                'priority' => $p,
                'label' => $priorityLabels[$p],
                'total' => $data['total'],
                'within_sla' => $data['within_sla'],
                'breached' => $data['breached'],
                'sla_hours' => $data['sla_hours'],
                'avg_hours' => $avgHours,
                'compliance_rate' => $complianceRate,
            ];
        }

        return [
            'rate' => $rate,
            'total_resolved' => $totalResolved,
            'within_sla' => $withinSla,
            'breached' => $breached,
            'by_priority' => $byPriorityOutput,
        ];
    }

    /**
     * Get empty SLA priority breakdown for zero-data state.
     */
    private function getEmptySlaPriorityBreakdown(): array
    {
        $labels = ['critical' => 'Kritis', 'high' => 'Tinggi', 'medium' => 'Sedang', 'low' => 'Rendah'];

        return collect(['critical', 'high', 'medium', 'low'])->map(fn (string $p): array => [
            'priority' => $p,
            'label' => $labels[$p],
            'total' => 0,
            'within_sla' => 0,
            'breached' => 0,
            'sla_hours' => 0,
            'avg_hours' => 0,
            'compliance_rate' => 0,
        ])->all();
    }

    /**
     * Get ticket creation trend (daily or weekly counts).
     *
     * @param  array<int>  $buIds
     * @return array<int, array{date: string, total: int, resolved: int}>
     */
    public function getTicketTrend(array $buIds, Carbon $from, Carbon $to): array
    {
        $diffDays = $from->diffInDays($to);

        // Use weekly grouping for ranges > 60 days, daily otherwise
        if ($diffDays > 60) {
            return $this->getWeeklyTrend($buIds, $from, $to);
        }

        return $this->getDailyTrend($buIds, $from, $to);
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

    /**
     * Get daily ticket creation trend with total and resolved counts.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{date: string, total: int, resolved: int}>
     */
    protected function getDailyTrend(array $buIds, Carbon $from, Carbon $to): array
    {
        $results = Ticket::forBusinessUnits($buIds)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as resolved")
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Fill in missing dates with zero counts
        $trend = [];
        $current = $from->copy();

        while ($current->lte($to)) {
            $dateStr = $current->toDateString();
            $found = $results->firstWhere('date', $dateStr);

            $trend[] = [
                'date' => $dateStr,
                'total' => $found ? (int) $found->total : 0,
                'resolved' => $found ? (int) $found->resolved : 0,
            ];

            $current->addDay();
        }

        return $trend;
    }

    /**
     * Get weekly ticket creation trend with total and resolved counts.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{date: string, total: int, resolved: int}>
     */
    protected function getWeeklyTrend(array $buIds, Carbon $from, Carbon $to): array
    {
        $results = Ticket::forBusinessUnits($buIds)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->select(
                DB::raw('YEARWEEK(created_at, 1) as year_week'),
                DB::raw('MIN(DATE(created_at)) as week_start'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as resolved")
            )
            ->groupBy(DB::raw('YEARWEEK(created_at, 1)'))
            ->orderBy('year_week')
            ->get();

        return $results->map(fn ($row): array => [
            'date' => $row->week_start,
            'total' => (int) $row->total,
            'resolved' => (int) $row->resolved,
        ])->all();
    }
}
