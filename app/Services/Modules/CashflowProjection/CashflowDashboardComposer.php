<?php

namespace App\Services\Modules\CashflowProjection;

use App\Http\Requests\CashflowProjection\CashflowProjectionDashboardFilterRequest;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionFinanceInput;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;

/**
 * Loads/composes the data needed by both the Cashflow dashboard
 * (index) and the export workbook.
 *
 * Lifted verbatim from CashflowProjectionController to dedupe the
 * cycle/department/line-item/finance-input/linked-BU merge logic that
 * was inlined in both index() and export(). Behavior preserved:
 *  - departments resolution + linked BU merge
 *  - line-item ordering preserved per caller (asc for export, desc for dashboard)
 *  - finance input merge by month via LinkedCycleMerger
 *  - scope resolution ("own" vs "consolidated")
 */
class CashflowDashboardComposer
{
    public function __construct(
        protected CashflowProjectionScopePolicy $scopePolicy,
        protected CashflowSummaryCalculator $summaryCalculator,
        protected LinkedCycleMerger $linkedCycleMerger
    ) {}

    /**
     * Compose dashboard/export data for a finance user.
     *
     * @param  array<int, string>  $eager  Eager-load relations on line items
     * @param  bool  $financeEagerAudit  When true, eager-load creator/updater on finance inputs (export path).
     * @return array<string, mixed>
     */
    public function compose(
        CashflowProjectionDashboardFilterRequest $request,
        User $user,
        int $businessUnitId,
        string $sortDirection,
        array $eager,
        bool $financeEagerAudit = false
    ): array {
        $dashboardFilters = $this->summaryCalculator->resolveDashboardFilters($request, $businessUnitId);
        $year = $dashboardFilters['year'];
        $cycle = $this->linkedCycleMerger->findOrCreateCycle($businessUnitId, $year, $user->id);

        $canManageFinance = $this->scopePolicy->userHasFinanceAssignment($user, $businessUnitId);
        $linkedBuIds = $canManageFinance ? $this->linkedCycleMerger->getLinkedBusinessUnitIds($businessUnitId) : [];
        $departments = $this->scopePolicy->resolveDashboardDepartments($user, $businessUnitId, $canManageFinance, $linkedBuIds);
        $departmentIds = $departments->pluck('id');

        $lineItemQuery = CashflowProjectionLineItem::query()
            ->with($eager)
            ->where('cycle_id', $cycle->id)
            ->whereIn('department_id', $departmentIds);

        $lineItemQuery = $sortDirection === 'asc'
            ? $lineItemQuery->orderBy('transaction_date')->orderBy('id')
            : $lineItemQuery->orderByDesc('transaction_date')->orderByDesc('id');

        $lineItems = $lineItemQuery->get();

        $financeInputQuery = CashflowProjectionFinanceInput::query()
            ->where('cycle_id', $cycle->id)
            ->orderBy('month');

        if ($financeEagerAudit) {
            $financeInputQuery->with(['creator', 'updater']);
        }

        $financeInputs = $canManageFinance ? $financeInputQuery->get() : collect();

        $scope = count($linkedBuIds) > 0 ? (string) $request->string('scope', 'consolidated') : 'own';
        $allLineItems = $lineItems;

        if ($scope === 'consolidated' && count($linkedBuIds) > 0) {
            $linkedCycles = $this->linkedCycleMerger->getLinkedCycles($linkedBuIds, $year, $user->id);
            $linkedLineItemQuery = CashflowProjectionLineItem::query()
                ->with($eager)
                ->whereIn('cycle_id', $linkedCycles->pluck('id'));

            $linkedLineItemQuery = $sortDirection === 'asc'
                ? $linkedLineItemQuery->orderBy('transaction_date')->orderBy('id')
                : $linkedLineItemQuery->orderByDesc('transaction_date')->orderByDesc('id');

            $linkedLineItems = $linkedLineItemQuery->get();

            $allLineItems = $sortDirection === 'asc'
                ? $lineItems->merge($linkedLineItems)
                    ->sortBy([['transaction_date', 'asc'], ['id', 'asc']])
                    ->values()
                : $lineItems->merge($linkedLineItems)
                    ->sortByDesc('transaction_date')
                    ->sortByDesc('id')
                    ->values();

            $linkedFinanceInputQuery = CashflowProjectionFinanceInput::query()
                ->whereIn('cycle_id', $linkedCycles->pluck('id'))
                ->orderBy('month');

            if ($financeEagerAudit) {
                $linkedFinanceInputQuery->with(['creator', 'updater']);
            }

            $financeInputs = $this->linkedCycleMerger->mergeFinanceInputs($financeInputs, $linkedFinanceInputQuery->get());
        }

        $linkedBusinessUnits = $this->scopePolicy->buildLinkedBusinessUnitPayload($linkedBuIds);

        $filteredLineItems = $this->summaryCalculator->filterLineItemsByPeriod(
            $allLineItems,
            $dashboardFilters['start'],
            $dashboardFilters['end']
        );

        $dailySummary = $this->summaryCalculator->buildPeriodDailySummary(
            $filteredLineItems,
            $dashboardFilters['start'],
            $dashboardFilters['end']
        );
        $monthlySummary = $this->summaryCalculator->buildMonthlySummary($allLineItems, $financeInputs);
        $summary = $this->summaryCalculator->buildDashboardSummary(
            $filteredLineItems,
            $financeInputs,
            $monthlySummary,
            $dashboardFilters['start'],
            $dashboardFilters['end']
        );

        return [
            'dashboardFilters' => $dashboardFilters,
            'year' => $year,
            'cycle' => $cycle,
            'canManageFinance' => $canManageFinance,
            'linkedBuIds' => $linkedBuIds,
            'departments' => $departments,
            'allLineItems' => $allLineItems,
            'filteredLineItems' => $filteredLineItems,
            'financeInputs' => $financeInputs,
            'scope' => $scope,
            'linkedBusinessUnits' => $linkedBusinessUnits,
            'dailySummary' => $dailySummary,
            'monthlySummary' => $monthlySummary,
            'summary' => $summary,
        ];
    }
}
