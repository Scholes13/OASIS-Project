<?php

namespace App\Services\Modules\Ticket\Reporting;

use App\Models\Modules\Ticket\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Compute ticket metric breakdowns (by status, priority, category, staff)
 * for a given business unit scope and date range. Returns chart-ready
 * payloads expected by the ticket reporting Inertia page.
 */
class TicketMetricsCalculator
{
    /**
     * Status display names and colors for chart rendering.
     */
    public const STATUS_META = [
        'waiting' => ['name' => 'Menunggu', 'color' => '#f59e0b'],
        'in_progress' => ['name' => 'Dalam Proses', 'color' => '#3b82f6'],
        'done' => ['name' => 'Done', 'color' => '#10b981'],
        'cancelled' => ['name' => 'Dibatalkan', 'color' => '#6b7280'],
    ];

    /**
     * Priority display names and colors for chart rendering.
     */
    public const PRIORITY_META = [
        'low' => ['name' => 'Rendah', 'color' => '#94a3b8'],
        'medium' => ['name' => 'Sedang', 'color' => '#3b82f6'],
        'high' => ['name' => 'Tinggi', 'color' => '#f59e0b'],
        'critical' => ['name' => 'Kritis', 'color' => '#ef4444'],
    ];

    /**
     * Get ticket counts grouped by status (only non-zero buckets).
     *
     * @param  array<int>  $buIds
     * @return array<int, array{name: string, value: int, color: string}>
     */
    public function byStatus(array $buIds, Carbon $from, Carbon $to): array
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
     * Get ticket counts grouped by priority (always returns all buckets).
     *
     * @param  array<int>  $buIds
     * @return array<int, array{name: string, count: int, color: string}>
     */
    public function byPriority(array $buIds, Carbon $from, Carbon $to): array
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
     * Get ticket counts grouped by category, falling back to "Uncategorized"
     * for tickets with no category.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{name: string, count: int, color: string}>
     */
    public function byCategory(array $buIds, Carbon $from, Carbon $to): array
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
     * Get ticket counts grouped by assigned staff. Unassigned tickets are
     * excluded so the staff workload chart focuses on real assignees.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{name: string, count: int, color: string}>
     */
    public function byStaff(array $buIds, Carbon $from, Carbon $to): array
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
     * Average resolution time in hours for resolved tickets in the range.
     *
     * @param  array<int>  $buIds
     */
    public function avgResolutionHours(array $buIds, Carbon $from, Carbon $to): float
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
}
