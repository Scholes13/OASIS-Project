<?php

namespace App\Actions\Modules\CashflowProjection;

use App\Http\Requests\CashflowProjection\UpsertCashflowProjectionFinanceInputRequest;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionFinanceInput;
use App\Services\Modules\CashflowProjection\CashflowProjectionAuditService;
use App\Services\Modules\CashflowProjection\CashflowProjectionScopeService;
use App\Services\Modules\CashflowProjection\LinkedCycleMerger;

/**
 * Upsert finance-only inputs (cash on hand, receivable, capital injection,
 * other income) for a Cashflow Projection cycle/month.
 *
 * Lifted verbatim from CashflowProjectionController::upsertFinanceInput().
 */
class UpsertFinanceInputAction
{
    public function __construct(
        protected CashflowProjectionScopeService $scopeService,
        protected CashflowProjectionAuditService $auditService,
        protected LinkedCycleMerger $linkedCycleMerger
    ) {}

    /**
     * @return array{finance_input: CashflowProjectionFinanceInput, redirect_params: array<string, int>}
     */
    public function execute(UpsertCashflowProjectionFinanceInputRequest $request, User $user, int $businessUnitId): array
    {
        $cycle = $this->linkedCycleMerger->findOrCreateCycle($businessUnitId, (int) $request->integer('year'), $user->id);

        $financeInput = CashflowProjectionFinanceInput::query()->firstOrNew([
            'cycle_id' => $cycle->id,
            'month' => (int) $request->integer('month'),
        ]);

        $wasRecentlyCreated = ! $financeInput->exists;
        $oldValues = $financeInput->exists ? $this->financeInputAuditValues($financeInput) : null;

        if (! $financeInput->exists) {
            $financeInput->created_by = $user->id;
        }

        $financeInput->cash_on_hand = $request->input('cash_on_hand');
        $financeInput->receivable_estimate = $request->input('receivable_estimate');
        $financeInput->upcoming_event_revenue_estimate = $request->input('upcoming_event_revenue_estimate');
        $financeInput->capital_injection_estimate = $request->input('capital_injection_estimate');
        $financeInput->other_income = $request->input('other_income');
        $financeInput->updated_by = $user->id;
        $financeInput->save();
        $financeInput->load('cycle');

        $this->auditService->logFinanceInputAction(
            $wasRecentlyCreated ? 'created' : 'updated',
            $financeInput,
            $user,
            $this->scopeService->currentActorDepartment($user, $businessUnitId),
            $oldValues,
            $this->financeInputAuditValues($financeInput)
        );

        $financeMonth = (int) $request->integer('month');
        $redirectParams = ['year' => $cycle->year];
        if ($financeMonth >= 1 && $financeMonth <= 12) {
            $redirectParams['month'] = $financeMonth;
        }

        return ['finance_input' => $financeInput, 'redirect_params' => $redirectParams];
    }

    /**
     * @return array<string, mixed>
     */
    private function financeInputAuditValues(CashflowProjectionFinanceInput $financeInput): array
    {
        return [
            'cycle_id' => $financeInput->cycle_id,
            'month' => $financeInput->month,
            'cash_on_hand' => (float) $financeInput->cash_on_hand,
            'receivable_estimate' => (float) $financeInput->receivable_estimate,
            'upcoming_event_revenue_estimate' => (float) $financeInput->upcoming_event_revenue_estimate,
            'capital_injection_estimate' => (float) $financeInput->capital_injection_estimate,
            'other_income' => (float) $financeInput->other_income,
        ];
    }
}
