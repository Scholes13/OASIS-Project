<?php

namespace App\Services\Modules\CashflowProjection;

use App\Models\Modules\CashflowProjection\CashflowProjectionCycle;
use App\Models\Modules\CashflowProjection\CashflowProjectionFinanceInput;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use App\Models\Modules\CashflowProjection\CashflowProjectionLinkedUnit;
use Illuminate\Support\Collection;

/**
 * Linked-business-unit support for the Cashflow Projection module.
 *
 * Lifted verbatim from CashflowProjectionController:
 *  - getLinkedBusinessUnitIds
 *  - getLinkedCycles
 *  - getLinkedLineItems
 *  - merging linked finance inputs into the host collection
 *  - findOrCreateCycle (shared between dashboard, settings, store, update)
 */
class LinkedCycleMerger
{
    /**
     * @return array<int, int>
     */
    public function getLinkedBusinessUnitIds(int $hostBusinessUnitId): array
    {
        return CashflowProjectionLinkedUnit::query()
            ->where('host_business_unit_id', $hostBusinessUnitId)
            ->pluck('linked_business_unit_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  array<int, int>  $linkedBuIds
     * @return Collection<int, CashflowProjectionCycle>
     */
    public function getLinkedCycles(array $linkedBuIds, int $year, int $userId): Collection
    {
        $cycles = collect();

        foreach ($linkedBuIds as $buId) {
            $cycles->push($this->findOrCreateCycle($buId, $year, $userId));
        }

        return $cycles;
    }

    /**
     * @param  Collection<int, CashflowProjectionCycle>  $linkedCycles
     * @return Collection<int, CashflowProjectionLineItem>
     */
    public function getLinkedLineItems(Collection $linkedCycles): Collection
    {
        if ($linkedCycles->isEmpty()) {
            return collect();
        }

        $cycleIds = $linkedCycles->pluck('id');

        return CashflowProjectionLineItem::query()
            ->with('department')
            ->whereIn('cycle_id', $cycleIds)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Sum-merge linked finance inputs into the host finance input collection
     * by month. Preserves the in-place mutation semantics of the original
     * controller code.
     *
     * @param  Collection<int, CashflowProjectionFinanceInput>  $financeInputs
     * @param  Collection<int, CashflowProjectionFinanceInput>  $linkedFinanceInputs
     * @return Collection<int, CashflowProjectionFinanceInput>
     */
    public function mergeFinanceInputs(Collection $financeInputs, Collection $linkedFinanceInputs): Collection
    {
        foreach ($linkedFinanceInputs as $linkedInput) {
            $existing = $financeInputs->firstWhere('month', $linkedInput->month);
            if ($existing) {
                $existing->cash_on_hand = CashflowMoney::add($existing->cash_on_hand, $linkedInput->cash_on_hand);
                $existing->receivable_estimate = CashflowMoney::add($existing->receivable_estimate, $linkedInput->receivable_estimate);
                $existing->upcoming_event_revenue_estimate = CashflowMoney::add($existing->upcoming_event_revenue_estimate, $linkedInput->upcoming_event_revenue_estimate);
                $existing->capital_injection_estimate = CashflowMoney::add($existing->capital_injection_estimate, $linkedInput->capital_injection_estimate);
                $existing->other_income = CashflowMoney::add($existing->other_income, $linkedInput->other_income);
            } else {
                $financeInputs->push($linkedInput);
            }
        }

        return $financeInputs->sortBy('month')->values();
    }

    public function findOrCreateCycle(int $businessUnitId, int $year, int $userId): CashflowProjectionCycle
    {
        $cycle = CashflowProjectionCycle::query()->firstOrCreate(
            [
                'business_unit_id' => $businessUnitId,
                'year' => $year,
            ],
            [
                'status' => 'draft',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );

        if ($cycle->updated_by === null) {
            $cycle->forceFill(['updated_by' => $userId])->save();
        }

        return $cycle;
    }
}
