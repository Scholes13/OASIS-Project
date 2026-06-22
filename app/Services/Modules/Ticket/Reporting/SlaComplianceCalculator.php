<?php

namespace App\Services\Modules\Ticket\Reporting;

use App\Models\Modules\Ticket\Ticket;
use App\Models\Modules\Ticket\TicketSlaSettings;
use Carbon\Carbon;

/**
 * Compute SLA compliance metrics (overall rate plus per-priority breakdown)
 * for resolved tickets in a date range. Falls back to default SLA hours
 * when a business unit has no per-priority SlaSettings configured.
 */
class SlaComplianceCalculator
{
    /**
     * Default SLA hours per priority when no per-BU settings exist.
     */
    protected const DEFAULT_SLA_HOURS = [
        'low' => 48,
        'medium' => 24,
        'high' => 8,
        'critical' => 2,
    ];

    /**
     * Display labels for the priority breakdown ordered from most to
     * least urgent.
     */
    protected const PRIORITY_LABELS = [
        'critical' => 'Kritis',
        'high' => 'Tinggi',
        'medium' => 'Sedang',
        'low' => 'Rendah',
    ];

    /**
     * Compute SLA compliance metrics for resolved tickets in the range.
     *
     * @param  array<int>  $buIds
     * @return array{rate: float, total_resolved: int, within_sla: int, breached: int, by_priority: array<int, array<string, mixed>>}
     */
    public function compliance(array $buIds, Carbon $from, Carbon $to): array
    {
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
                'by_priority' => $this->emptyPriorityBreakdown(),
            ];
        }

        $slaMap = TicketSlaSettings::whereIn('business_unit_id', $buIds)
            ->get()
            ->groupBy('business_unit_id')
            ->map(fn ($settings) => $settings->keyBy('priority'));

        $withinSla = 0;
        $breached = 0;
        $byPriority = $this->initialisePriorityAccumulator();

        foreach ($resolvedTickets as $ticket) {
            $priority = $ticket->priority;
            $buSla = $slaMap->get($ticket->business_unit_id);
            $slaHours = $buSla?->get($priority)?->resolution_hours
                ?? (self::DEFAULT_SLA_HOURS[$priority] ?? 24);
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

        return [
            'rate' => $rate,
            'total_resolved' => $totalResolved,
            'within_sla' => $withinSla,
            'breached' => $breached,
            'by_priority' => $this->formatPriorityBreakdown($byPriority),
        ];
    }

    /**
     * Empty SLA priority breakdown for the zero-data state.
     *
     * @return array<int, array<string, mixed>>
     */
    public function emptyPriorityBreakdown(): array
    {
        return collect(['critical', 'high', 'medium', 'low'])->map(fn (string $p): array => [
            'priority' => $p,
            'label' => self::PRIORITY_LABELS[$p],
            'total' => 0,
            'within_sla' => 0,
            'breached' => 0,
            'sla_hours' => 0,
            'avg_hours' => 0,
            'compliance_rate' => 0,
        ])->all();
    }

    /**
     * Per-priority accumulator with zeroed counters and totals.
     *
     * @return array<string, array<string, int|float>>
     */
    protected function initialisePriorityAccumulator(): array
    {
        $template = [
            'total' => 0,
            'within_sla' => 0,
            'breached' => 0,
            'avg_hours' => 0,
            'sla_hours' => 0,
            'total_hours' => 0,
        ];

        return [
            'low' => $template,
            'medium' => $template,
            'high' => $template,
            'critical' => $template,
        ];
    }

    /**
     * Project per-priority accumulator into the API output shape with
     * computed averages and compliance rates.
     *
     * @param  array<string, array<string, int|float>>  $byPriority
     * @return array<int, array<string, mixed>>
     */
    protected function formatPriorityBreakdown(array $byPriority): array
    {
        $output = [];

        foreach (['critical', 'high', 'medium', 'low'] as $p) {
            $data = $byPriority[$p];
            $avgHours = $data['total'] > 0 ? round($data['total_hours'] / $data['total'], 1) : 0;
            $complianceRate = $data['total'] > 0
                ? round(($data['within_sla'] / $data['total']) * 100, 1)
                : 0;

            $output[] = [
                'priority' => $p,
                'label' => self::PRIORITY_LABELS[$p],
                'total' => $data['total'],
                'within_sla' => $data['within_sla'],
                'breached' => $data['breached'],
                'sla_hours' => $data['sla_hours'],
                'avg_hours' => $avgHours,
                'compliance_rate' => $complianceRate,
            ];
        }

        return $output;
    }
}
