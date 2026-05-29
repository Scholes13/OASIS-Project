<?php

namespace App\Services\Modules\Ticket\Reporting;

use App\Models\Modules\Ticket\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Compute the ticket creation/resolution trend over a date range. Switches
 * to weekly aggregation for ranges greater than 60 days so the chart stays
 * readable for long horizons.
 */
class TicketTrendCalculator
{
    /**
     * Get ticket creation trend, choosing daily or weekly bucketing
     * automatically based on the range length.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{date: string, total: int, resolved: int}>
     */
    public function trend(array $buIds, Carbon $from, Carbon $to): array
    {
        $diffDays = $from->diffInDays($to);

        if ($diffDays > 60) {
            return $this->weeklyTrend($buIds, $from, $to);
        }

        return $this->dailyTrend($buIds, $from, $to);
    }

    /**
     * Daily ticket creation trend with total and resolved counts.
     * Missing dates are zero-filled so the chart has no gaps.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{date: string, total: int, resolved: int}>
     */
    public function dailyTrend(array $buIds, Carbon $from, Carbon $to): array
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
     * Weekly ticket creation trend with total and resolved counts.
     *
     * @param  array<int>  $buIds
     * @return array<int, array{date: string, total: int, resolved: int}>
     */
    public function weeklyTrend(array $buIds, Carbon $from, Carbon $to): array
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
