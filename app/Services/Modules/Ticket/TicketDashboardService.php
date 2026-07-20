<?php

namespace App\Services\Modules\Ticket;

use App\Models\Modules\Ticket\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TicketDashboardService
{
    /**
     * @param  array<int>  $buIds
     * @return array<string, mixed>
     */
    public function getMetrics(array $buIds, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = $this->dateScopedQuery($buIds, $dateFrom, $dateTo);
        $tickets = (clone $query)->get();
        Ticket::preloadSlaSettings($buIds);

        $byStatus = $tickets->groupBy('status')->map->count();
        $byPriority = $tickets->groupBy('priority')->map->count();

        $byCategory = (clone $query)
            ->select('category_id', DB::raw('count(*) as count'))
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->with('category:id,name,color')
            ->get()
            ->map(fn ($item): array => [
                'name' => $item->category?->name ?? 'Uncategorized',
                'count' => $item->count,
                'color' => $item->category?->color ?? '#6b7280',
            ])
            ->values()
            ->all();

        $byStaff = (clone $query)
            ->select('assigned_to', DB::raw('count(*) as count'))
            ->whereNotNull('assigned_to')
            ->groupBy('assigned_to')
            ->with('assignedUser:id,name')
            ->get()
            ->map(fn ($item): array => [
                'name' => $item->assignedUser?->name ?? 'Unassigned',
                'count' => $item->count,
            ])
            ->values()
            ->all();

        $volumeByDay = (clone $query)
            ->selectRaw('DATE(created_at) as volume_date, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderByDesc('volume_date')
            ->limit(7)
            ->get()
            ->reverse()
            ->map(fn ($item): array => [
                'date' => $item->volume_date,
                'count' => (int) $item->count,
            ])
            ->values()
            ->all();

        $resolvedTickets = $tickets->filter(fn (Ticket $ticket): bool => $ticket->resolved_at !== null);
        $averageResolutionHours = $resolvedTickets->isEmpty()
            ? 0
            : round((float) $resolvedTickets->average(
                fn (Ticket $ticket): float => $ticket->created_at->diffInMinutes($ticket->resolved_at) / 60
            ), 1);

        return [
            'total' => $tickets->count(),
            'by_status' => [
                'waiting' => $byStatus->get('waiting', 0),
                'in_progress' => $byStatus->get('in_progress', 0),
                'done' => $byStatus->get('done', 0),
                'cancelled' => $byStatus->get('cancelled', 0),
            ],
            'by_priority' => [
                'low' => $byPriority->get('low', 0),
                'medium' => $byPriority->get('medium', 0),
                'high' => $byPriority->get('high', 0),
                'critical' => $byPriority->get('critical', 0),
            ],
            'by_category' => $byCategory,
            'by_staff' => $byStaff,
            'volume_by_day' => $volumeByDay,
            'avg_resolution_hours' => $averageResolutionHours,
            'sla_breach_count' => $tickets->filter(fn (Ticket $ticket): bool => $ticket->isSlaBreach())->count(),
            'recent_tickets' => (clone $query)
                ->with(['requester', 'assignedUser', 'category'])
                ->latest()
                ->limit(10)
                ->get(),
        ];
    }

    /** @param array<int> $buIds */
    private function dateScopedQuery(array $buIds, ?string $dateFrom, ?string $dateTo): Builder
    {
        return Ticket::forBusinessUnits($buIds)
            ->when($dateFrom, fn (Builder $query) => $query->where('created_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->where('created_at', '<=', $dateTo.' 23:59:59'));
    }
}
