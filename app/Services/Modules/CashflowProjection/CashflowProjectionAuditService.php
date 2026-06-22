<?php

namespace App\Services\Modules\CashflowProjection;

use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionAuditLog;
use App\Models\Modules\CashflowProjection\CashflowProjectionFinanceInput;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;

class CashflowProjectionAuditService
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function logLineItemAction(
        string $action,
        CashflowProjectionLineItem $lineItem,
        User $actor,
        ?Department $actorDepartment,
        ?array $oldValues,
        ?array $newValues
    ): void {
        CashflowProjectionAuditLog::query()->create([
            'auditable_type' => 'line_item',
            'auditable_id' => $lineItem->id,
            'action' => $action,
            'business_unit_id' => $lineItem->department?->business_unit_id,
            'department_id' => $lineItem->department_id,
            'actor_user_id' => $actor->id,
            'actor_user_name' => $actor->name,
            'actor_department_id' => $actorDepartment?->id,
            'actor_department_label' => $actorDepartment?->name,
            'summary' => $this->lineItemSummary($action, $lineItem),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     */
    public function logDeletedLineItemAction(
        CashflowProjectionLineItem $lineItem,
        User $actor,
        ?Department $actorDepartment,
        ?array $oldValues
    ): void {
        $this->logLineItemAction('deleted', $lineItem, $actor, $actorDepartment, $oldValues, null);
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function logFinanceInputAction(
        string $action,
        CashflowProjectionFinanceInput $financeInput,
        User $actor,
        ?Department $actorDepartment,
        ?array $oldValues,
        ?array $newValues
    ): void {
        CashflowProjectionAuditLog::query()->create([
            'auditable_type' => 'finance_input',
            'auditable_id' => $financeInput->id,
            'action' => $action,
            'business_unit_id' => $financeInput->cycle?->business_unit_id,
            'department_id' => $actorDepartment?->id,
            'actor_user_id' => $actor->id,
            'actor_user_name' => $actor->name,
            'actor_department_id' => $actorDepartment?->id,
            'actor_department_label' => $actorDepartment?->name,
            'summary' => $this->financeInputSummary($action, $financeInput),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'created_at' => now(),
        ]);
    }

    private function lineItemSummary(string $action, CashflowProjectionLineItem $lineItem): string
    {
        $departmentCode = $lineItem->department?->code ?? 'UNKNOWN';

        return strtoupper($action).' line item '.$departmentCode.' '.$lineItem->action_code;
    }

    private function financeInputSummary(string $action, CashflowProjectionFinanceInput $financeInput): string
    {
        return strtoupper($action).' finance input month '.$financeInput->month;
    }
}
