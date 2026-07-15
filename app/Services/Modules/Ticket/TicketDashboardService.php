<?php

namespace App\Services\Modules\Ticket;

use App\Models\Modules\Ticket\Ticket;
use Illuminate\Support\Facades\DB;

class TicketDashboardService
{
    /**
     * @param  array<int>  $buIds
     * @return array<string, mixed>
     */
    public function getMetrics(array $buIds, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = Ticket::forBusinessUnits($buIds);

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo.' 23:59:59');
        }

        $tickets = $query->get();
        Ticket::preloadSlaSettings($buIds);

        $byStatus = $tickets->groupBy('status')->map->count();
        $byPriority = $tickets->groupBy('priority')->map->count();

        $byCategory = Ticket::forBusinessUnits($buIds)
            ->select('category_id', DB::raw('count(*) as count'))
            ->whereNotNull('category_id')
            ->when($dateFrom, fn ($query) => $query->where('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->where('created_at', '<=', $dateTo.' 23:59:59'))
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

        $byStaff = Ticket::forBusinessUnits($buIds)
            ->select('assigned_to', DB::raw('count(*) as count'))
            ->whereNotNull('assigned_to')
            ->when($dateFrom, fn ($query) => $query->where('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->where('created_at', '<=', $dateTo.' 23:59:59'))
            ->groupBy('assigned_to')
            ->with('assignedUser:id,name')
            ->get()
            ->map(fn ($item): array => [
                'name' => $item->assignedUser?->name ?? 'Unassigned',
                'count' => $item->count,
            ])
            ->values()
            ->all();

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
            'sla_breach_count' => $tickets->filter(fn (Ticket $ticket): bool => $ticket->isSlaBreach())->count(),
            'recent_tickets' => Ticket::forBusinessUnits($buIds)
                ->with(['requester', 'assignedUser', 'category'])
                ->latest()
                ->limit(10)
                ->get(),
        ];
    }
}
