<?php

namespace App\Actions\Modules\CashflowProjection;

use App\Http\Requests\CashflowProjection\UpdateCashflowProjectionLineItemRequest;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use App\Services\Modules\CashflowProjection\CashflowProjectionAccessService;
use App\Services\Modules\CashflowProjection\CashflowProjectionAuditService;
use App\Services\Modules\CashflowProjection\CashflowProjectionScopeService;
use App\Services\Modules\CashflowProjection\CashflowProjectionTemplateService;
use App\Services\Modules\CashflowProjection\LinkedCycleMerger;

/**
 * Update an existing Cashflow Projection line item.
 *
 * Lifted verbatim from CashflowProjectionController::updateLineItem() to
 * preserve scope guards, validation messages and audit logging.
 */
class UpdateCashflowLineItemAction
{
    public function __construct(
        protected CashflowProjectionAccessService $accessService,
        protected CashflowProjectionScopeService $scopeService,
        protected CashflowProjectionTemplateService $templateService,
        protected CashflowProjectionAuditService $auditService,
        protected LinkedCycleMerger $linkedCycleMerger
    ) {}

    /**
     * @return array{ok: true, line_item: CashflowProjectionLineItem, redirect_params: array<string, int>}|array{ok: false, errors: array<string, string>}
     */
    public function execute(
        UpdateCashflowProjectionLineItemRequest $request,
        CashflowProjectionLineItem $lineItem,
        User $user,
        int $businessUnitId
    ): array {
        $lineItem->load('department');
        $currentDepartment = $lineItem->department;
        abort_unless($currentDepartment instanceof Department, 404);

        $isFinance = $this->accessService->isFinanceUser($user, $businessUnitId);

        if ($isFinance && ! $this->scopeService->financeCanTargetDepartment($user, $businessUnitId, $currentDepartment)) {
            abort(403);
        }

        if (! $isFinance && ! $this->scopeService->nonFinanceCanTargetDepartment($user, $businessUnitId, $currentDepartment)) {
            abort(403);
        }

        $targetDepartment = Department::query()->with('businessUnit')->findOrFail((int) $request->integer('department_id'));

        if ($isFinance && ! $this->scopeService->financeCanTargetDepartment($user, $businessUnitId, $targetDepartment)) {
            return [
                'ok' => false,
                'errors' => [
                    'department_id' => 'Departemen tidak berada dalam cakupan business unit aktif atau linked business unit.',
                ],
            ];
        }

        if (! $isFinance && ! $this->scopeService->nonFinanceCanTargetDepartment($user, $businessUnitId, $targetDepartment)) {
            abort(403);
        }

        $actionCode = (string) $request->string('action_code');

        if (! $this->templateService->isActionAllowedForDepartment($actionCode, $targetDepartment)) {
            return ['ok' => false, 'errors' => ['action_code' => 'Action tidak sesuai template departemen.']];
        }

        $actionMeta = $this->templateService->metaForActionCode($actionCode, $targetDepartment);

        if (! $actionMeta) {
            return ['ok' => false, 'errors' => ['action_code' => 'Action tidak valid.']];
        }

        $oldValues = $this->lineItemAuditValues($lineItem);
        $targetCycle = $this->linkedCycleMerger->findOrCreateCycle((int) $targetDepartment->business_unit_id, (int) $request->integer('year'), $user->id);

        $lineItem->forceFill([
            'cycle_id' => $targetCycle->id,
            'department_id' => $targetDepartment->id,
            'flow_type' => $actionMeta['flow_type'],
            'action_code' => $actionCode,
            'transaction_date' => $request->date('transaction_date'),
            'due_date' => $request->date('due_date'),
            'is_estimated_date' => (bool) $request->boolean('is_estimated_date'),
            'amount' => $request->input('amount'),
            'description' => (string) $request->string('description'),
            'keterangan' => $request->filled('keterangan') ? (string) $request->string('keterangan') : null,
            'notes' => $request->filled('notes') ? (string) $request->string('notes') : null,
            'updated_by' => $user->id,
        ])->save();

        $lineItem->load('department');
        $this->auditService->logLineItemAction(
            'updated',
            $lineItem,
            $user,
            $this->scopeService->currentActorDepartment($user, $businessUnitId),
            $oldValues,
            $this->lineItemAuditValues($lineItem)
        );

        $transactionMonth = (int) ($request->date('transaction_date')?->format('n') ?? 0);
        $redirectParams = ['year' => $targetCycle->year];
        if ($transactionMonth >= 1 && $transactionMonth <= 12) {
            $redirectParams['month'] = $transactionMonth;
        }

        return ['ok' => true, 'line_item' => $lineItem, 'redirect_params' => $redirectParams];
    }

    /**
     * @return array<string, mixed>
     */
    private function lineItemAuditValues(CashflowProjectionLineItem $lineItem): array
    {
        return [
            'cycle_id' => $lineItem->cycle_id,
            'department_id' => $lineItem->department_id,
            'action_code' => $lineItem->action_code,
            'flow_type' => $lineItem->flow_type,
            'transaction_date' => optional($lineItem->transaction_date)->format('Y-m-d'),
            'due_date' => optional($lineItem->due_date)->format('Y-m-d'),
            'is_estimated_date' => (bool) $lineItem->is_estimated_date,
            'amount' => (float) $lineItem->amount,
            'description' => $lineItem->description,
            'keterangan' => $lineItem->keterangan,
            'notes' => $lineItem->notes,
        ];
    }
}
