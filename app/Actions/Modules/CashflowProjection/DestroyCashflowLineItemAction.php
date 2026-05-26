<?php

namespace App\Actions\Modules\CashflowProjection;

use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionCycle;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use App\Services\Modules\CashflowProjection\CashflowProjectionAccessService;
use App\Services\Modules\CashflowProjection\CashflowProjectionAuditService;
use App\Services\Modules\CashflowProjection\CashflowProjectionScopeService;

/**
 * Delete a Cashflow Projection line item.
 *
 * Lifted verbatim from CashflowProjectionController::destroyLineItem() to
 * preserve scope checks, audit logging and redirect parameter resolution.
 */
class DestroyCashflowLineItemAction
{
    public function __construct(
        protected CashflowProjectionAccessService $accessService,
        protected CashflowProjectionScopeService $scopeService,
        protected CashflowProjectionAuditService $auditService
    ) {}

    /**
     * @return array{redirect_params: array<string, int>}
     */
    public function execute(CashflowProjectionLineItem $lineItem, User $user, int $businessUnitId, ?int $year, ?int $month): array
    {
        $lineItem->loadMissing('department', 'cycle');
        $department = $lineItem->department;
        $cycle = $lineItem->cycle;

        abort_unless($department instanceof Department, 404);
        abort_unless($cycle instanceof CashflowProjectionCycle, 404);

        $allowedBusinessUnitIds = $this->scopeService->allowedBusinessUnitIds($user, $businessUnitId);
        $allowedDepartments = $this->scopeService->allowedDepartments($user, $businessUnitId);

        abort_unless(in_array((int) $cycle->business_unit_id, $allowedBusinessUnitIds, true), 403);
        abort_unless($allowedDepartments->contains('id', $department->id), 403);

        $this->auditService->logDeletedLineItemAction(
            $lineItem,
            $user,
            $this->scopeService->currentActorDepartment($user, $businessUnitId),
            $this->lineItemAuditValues($lineItem)
        );

        $resolvedYear = $year ?? (int) ($cycle->year ?? now()->format('Y'));
        $resolvedMonth = $month ?? (int) ($lineItem->transaction_date?->format('n') ?? now()->format('n'));

        $lineItem->delete();

        $redirectParams = ['year' => $resolvedYear];
        if ($resolvedMonth >= 1 && $resolvedMonth <= 12) {
            $redirectParams['month'] = $resolvedMonth;
        }

        return ['redirect_params' => $redirectParams];
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
            'notes' => $lineItem->notes,
        ];
    }
}
