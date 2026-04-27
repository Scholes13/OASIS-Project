<?php

namespace App\Services\Modules\Ticket;

use App\Models\Modules\Ticket\Ticket;
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

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'label' => $period,
            ],
            'by_status' => $this->getMetricsByStatus($buIds, $from, $to),
            'by_priority' => $this->getMetricsByPriority($buIds, $from, $to),
            'by_category' => $this->getMetricsByCategory($buIds, $from, $to),
            'by_staff' => $this->getMetricsByStaff($buIds, $from, $to),
            'avg_resolution_time' => $this->getAvgResolutionTime($buIds, $from, $to),
            'trend' => $this->getTicketTrend($buIds, $from, $to),
        ];
    }

    /**
     * Get ticket counts grouped by status.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{status: string, count: int}>
     */
    public function getMetricsByStatus(array $buIds, Carbon $from, Carbon $to): array
    {
        return Ticket::forBusinessUnits($buIds)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(fn ($row): array => [
                'status' => $row->status,
                'count' => (int) $row->count,
            ])
            ->all();
    }

    /**
     * Get ticket counts grouped by priority.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{priority: string, count: int}>
     */
    public function getMetricsByPriority(array $buIds, Carbon $from, Carbon $to): array
    {
        return Ticket::forBusinessUnits($buIds)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->get()
            ->map(fn ($row): array => [
                'priority' => $row->priority,
                'count' => (int) $row->count,
            ])
            ->all();
    }

    /**
     * Get ticket counts grouped by category.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{category_id: int|null, category_name: string, count: int}>
     */
    public function getMetricsByCategory(array $buIds, Carbon $from, Carbon $to): array
    {
        return Ticket::forBusinessUnits($buIds)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->leftJoin('ticket_categories', 'tickets.category_id', '=', 'ticket_categories.id')
            ->select(
                'tickets.category_id',
                DB::raw("COALESCE(ticket_categories.name, 'Uncategorized') as category_name"),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('tickets.category_id', 'ticket_categories.name')
            ->get()
            ->map(fn ($row): array => [
                'category_id' => $row->category_id,
                'category_name' => $row->category_name,
                'count' => (int) $row->count,
            ])
            ->all();
    }

    /**
     * Get ticket counts grouped by assigned staff.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{user_id: int|null, user_name: string, count: int}>
     */
    public function getMetricsByStaff(array $buIds, Carbon $from, Carbon $to): array
    {
        return Ticket::forBusinessUnits($buIds)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->leftJoin('users', 'tickets.assigned_to', '=', 'users.id')
            ->select(
                'tickets.assigned_to as user_id',
                DB::raw("COALESCE(users.name, 'Unassigned') as user_name"),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('tickets.assigned_to', 'users.name')
            ->get()
            ->map(fn ($row): array => [
                'user_id' => $row->user_id,
                'user_name' => $row->user_name,
                'count' => (int) $row->count,
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
     * Get ticket creation trend (daily or weekly counts).
     *
     * @param  array<int>  $buIds
     * @return array<int, array{date: string, count: int}>
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
     * Get daily ticket creation trend.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{date: string, count: int}>
     */
    protected function getDailyTrend(array $buIds, Carbon $from, Carbon $to): array
    {
        $results = Ticket::forBusinessUnits($buIds)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
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
                'count' => $found ? (int) $found->count : 0,
            ];

            $current->addDay();
        }

        return $trend;
    }

    /**
     * Get weekly ticket creation trend.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{date: string, count: int}>
     */
    protected function getWeeklyTrend(array $buIds, Carbon $from, Carbon $to): array
    {
        $results = Ticket::forBusinessUnits($buIds)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->select(
                DB::raw('YEARWEEK(created_at, 1) as year_week'),
                DB::raw('MIN(DATE(created_at)) as week_start'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('YEARWEEK(created_at, 1)'))
            ->orderBy('year_week')
            ->get();

        return $results->map(fn ($row): array => [
            'date' => $row->week_start,
            'count' => (int) $row->count,
        ])->all();
    }
}
