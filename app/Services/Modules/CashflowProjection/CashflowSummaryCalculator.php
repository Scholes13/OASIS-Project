<?php

namespace App\Services\Modules\CashflowProjection;

use App\Http\Requests\CashflowProjection\CashflowProjectionDashboardFilterRequest;
use App\Models\Modules\CashflowProjection\CashflowProjectionCycle;
use App\Models\Modules\CashflowProjection\CashflowProjectionFinanceInput;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Period filtering, dashboard filters, daily/monthly summaries and
 * dashboard balance snapshots for the Cashflow Projection module.
 *
 * Lifted verbatim from CashflowProjectionController to preserve numbers:
 *  - resolveDashboardFilters / resolveAvailableYears / monthsInPeriod
 *  - filterLineItemsByPeriod
 *  - buildPeriodDailySummary
 *  - buildMonthlySummary
 *  - buildDashboardSummary
 */
class CashflowSummaryCalculator
{
    /**
     * @return array{mode: string, year: int, month: int, start: CarbonImmutable, end: CarbonImmutable, available_years: array<int, int>}
     */
    public function resolveDashboardFilters(CashflowProjectionDashboardFilterRequest $request, int $businessUnitId): array
    {
        $mode = (string) $request->string('filter', 'month');
        $year = (int) $request->integer('year', (int) now()->format('Y'));
        $month = (int) max(1, min(12, $request->integer('month', (int) now()->format('n'))));

        if ($mode === 'year') {
            $startDate = CarbonImmutable::create($year, 1, 1)->startOfDay();
            $endDate = CarbonImmutable::create($year, 12, 31)->endOfDay();
        } elseif ($mode === 'range') {
            $startDate = CarbonImmutable::parse((string) $request->string('start_date'))->startOfDay();
            $endDate = CarbonImmutable::parse((string) $request->string('end_date'))->endOfDay();
        } else {
            $startDate = CarbonImmutable::create($year, $month, 1)->startOfDay();
            $endDate = $startDate->endOfMonth()->endOfDay();
        }

        return [
            'mode' => $mode,
            'year' => $year,
            'month' => $month,
            'start' => $startDate,
            'end' => $endDate,
            'available_years' => $this->resolveAvailableYears($businessUnitId, $year),
        ];
    }

    /**
     * @return array<int, int>
     */
    public function resolveAvailableYears(int $businessUnitId, int $selectedYear): array
    {
        return CashflowProjectionCycle::query()
            ->where('business_unit_id', $businessUnitId)
            ->pluck('year')
            ->map(fn ($year) => (int) $year)
            ->push($selectedYear)
            ->push((int) now()->format('Y'))
            ->push((int) now()->subYear()->format('Y'))
            ->push((int) now()->addYear()->format('Y'))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function monthsInPeriod(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $months = [];
        $cursor = $startDate->startOfMonth();
        $lastMonth = $endDate->startOfMonth();

        while ($cursor->lessThanOrEqualTo($lastMonth)) {
            $months[] = (int) $cursor->format('n');
            $cursor = $cursor->addMonth();
        }

        return array_values(array_unique($months));
    }

    /**
     * @param  Collection<int, CashflowProjectionLineItem>  $lineItems
     * @return Collection<int, CashflowProjectionLineItem>
     */
    public function filterLineItemsByPeriod(Collection $lineItems, CarbonImmutable $startDate, CarbonImmutable $endDate): Collection
    {
        return $lineItems
            ->filter(function (CashflowProjectionLineItem $item) use ($startDate, $endDate) {
                if (! $item->transaction_date) {
                    return false;
                }

                $transactionDate = CarbonImmutable::instance($item->transaction_date);

                return $transactionDate->betweenIncluded($startDate, $endDate);
            })
            ->values();
    }

    /**
     * @param  Collection<int, CashflowProjectionLineItem>  $filteredLineItems
     * @return array<int, array{date: string, plus: string, minus: string, net: string}>
     */
    public function buildPeriodDailySummary(Collection $filteredLineItems, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $daily = [];
        $cursor = $startDate->startOfDay();
        $lastDate = $endDate->startOfDay();

        while ($cursor->lessThanOrEqualTo($lastDate)) {
            $dateKey = $cursor->format('Y-m-d');
            $daily[$dateKey] = [
                'date' => $dateKey,
                'plus' => 0,
                'minus' => 0,
                'net' => 0,
            ];
            $cursor = $cursor->addDay();
        }

        foreach ($filteredLineItems as $item) {
            $dateKey = $item->transaction_date?->format('Y-m-d');

            if (! $dateKey || ! isset($daily[$dateKey])) {
                continue;
            }

            $amount = CashflowMoney::toMinor($item->amount);

            if ($item->flow_type === 'in') {
                $daily[$dateKey]['plus'] = CashflowMoney::addMinor($daily[$dateKey]['plus'], $amount);
            } else {
                $daily[$dateKey]['minus'] = CashflowMoney::addMinor($daily[$dateKey]['minus'], $amount);
            }

            $daily[$dateKey]['net'] = CashflowMoney::addMinor($daily[$dateKey]['plus'], -$daily[$dateKey]['minus']);
        }

        return collect($daily)->map(function (array $row): array {
            $row['plus'] = CashflowMoney::fromMinor($row['plus']);
            $row['minus'] = CashflowMoney::fromMinor($row['minus']);
            $row['net'] = CashflowMoney::fromMinor($row['net']);

            return $row;
        })->values()->all();
    }

    /**
     * @return array<int, array{month: int, plus: string, minus: string, finance_income: string, opening_balance: string, net: string, closing_balance: string, is_warning: bool}>
     */
    public function buildMonthlySummary($lineItems, $financeInputs): array
    {
        $plusByMonth = array_fill(1, 12, 0);
        $minusByMonth = array_fill(1, 12, 0);

        foreach ($lineItems as $item) {
            $month = (int) $item->transaction_date?->format('n');
            if ($month < 1 || $month > 12) {
                continue;
            }

            $amount = CashflowMoney::toMinor($item->amount);
            if ($item->flow_type === 'in') {
                $plusByMonth[$month] = CashflowMoney::addMinor($plusByMonth[$month], $amount);
            } else {
                $minusByMonth[$month] = CashflowMoney::addMinor($minusByMonth[$month], $amount);
            }
        }

        $financeByMonth = [];
        foreach ($financeInputs as $input) {
            $month = (int) $input->month;
            $financeByMonth[$month] = [
                'opening_balance' => CashflowMoney::toMinor($input->cash_on_hand),
                'finance_income' => CashflowMoney::toMinor(CashflowMoney::add(
                    $input->receivable_estimate,
                    $input->upcoming_event_revenue_estimate,
                    $input->capital_injection_estimate,
                    $input->other_income,
                )),
            ];
        }

        $rows = [];
        $previousClosingBalance = 0;
        for ($month = 1; $month <= 12; $month++) {
            $plus = $plusByMonth[$month];
            $minus = $minusByMonth[$month];
            $openingBalance = array_key_exists($month, $financeByMonth)
                ? $financeByMonth[$month]['opening_balance']
                : $previousClosingBalance;
            $financeIncome = $financeByMonth[$month]['finance_income'] ?? 0;
            $net = CashflowMoney::addMinor($plus, -$minus, $financeIncome);
            $closingBalance = CashflowMoney::addMinor($openingBalance, $net);
            $previousClosingBalance = $closingBalance;

            $rows[] = [
                'month' => $month,
                'plus' => CashflowMoney::fromMinor($plus),
                'minus' => CashflowMoney::fromMinor($minus),
                'finance_income' => CashflowMoney::fromMinor($financeIncome),
                'opening_balance' => CashflowMoney::fromMinor($openingBalance),
                'net' => CashflowMoney::fromMinor($net),
                'closing_balance' => CashflowMoney::fromMinor($closingBalance),
                'is_warning' => $closingBalance < CashflowMoney::toMinor((string) config('features.cashflow.minimum_balance_global', 200000000)),
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, CashflowProjectionLineItem>  $filteredLineItems
     * @param  Collection<int, CashflowProjectionFinanceInput>  $financeInputs
     * @param  array<int, array<string, mixed>>  $monthlySummary
     * @return array{total_balance: string, inflow: string, outflow: string, finance_income: string, net_cashflow: string}
     */
    public function buildDashboardSummary(
        Collection $filteredLineItems,
        Collection $financeInputs,
        array $monthlySummary,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate
    ): array {
        $monthsInScope = $this->monthsInPeriod($startDate, $endDate);

        $inflow = CashflowMoney::sumMinor(
            $filteredLineItems->where('flow_type', 'in')->map(fn (CashflowProjectionLineItem $item) => $item->amount)
        );

        $outflow = CashflowMoney::sumMinor(
            $filteredLineItems->where('flow_type', 'out')->map(fn (CashflowProjectionLineItem $item) => $item->amount)
        );

        $financeIncome = CashflowMoney::sumMinor(
            $financeInputs
                ->filter(fn (CashflowProjectionFinanceInput $input) => in_array((int) $input->month, $monthsInScope, true))
                ->flatMap(fn (CashflowProjectionFinanceInput $input) => [
                    $input->receivable_estimate,
                    $input->upcoming_event_revenue_estimate,
                    $input->capital_injection_estimate,
                    $input->other_income,
                ])
        );

        $snapshot = collect($monthlySummary)
            ->filter(fn (array $row) => in_array((int) $row['month'], $monthsInScope, true))
            ->reverse()
            ->first(function (array $row) {
                return $row['plus'] > 0
                    || $row['minus'] > 0
                    || $row['finance_income'] > 0
                    || $row['opening_balance'] > 0;
            });

        if (! $snapshot) {
            $snapshot = collect($monthlySummary)
                ->filter(fn (array $row) => in_array((int) $row['month'], $monthsInScope, true))
                ->last();
        }

        return [
            'total_balance' => CashflowMoney::normalize($snapshot['closing_balance'] ?? '0'),
            'inflow' => CashflowMoney::fromMinor($inflow),
            'outflow' => CashflowMoney::fromMinor($outflow),
            'finance_income' => CashflowMoney::fromMinor($financeIncome),
            'net_cashflow' => CashflowMoney::fromMinor(CashflowMoney::addMinor($inflow, -$outflow, $financeIncome)),
        ];
    }
}
