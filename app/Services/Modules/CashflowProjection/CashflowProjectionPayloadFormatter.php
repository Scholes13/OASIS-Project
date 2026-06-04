<?php

namespace App\Services\Modules\CashflowProjection;

use App\Models\Core\Department;
use App\Models\Modules\CashflowProjection\CashflowProjectionAuditLog;
use App\Models\Modules\CashflowProjection\CashflowProjectionFinanceInput;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use Illuminate\Support\Collection;

/**
 * Payload formatters for Cashflow Projection responses.
 *
 * Lifted verbatim from CashflowProjectionController:
 *  - buildDepartmentOptions
 *  - buildLineItemPayload
 *  - buildFinanceInputPayload
 *  - resolveAuditMetadata
 *  - dashboard-only line-item payload (index() inline mapping)
 */
class CashflowProjectionPayloadFormatter
{
    public function __construct(
        protected CashflowProjectionTemplateService $templateService
    ) {}

    /**
     * @param  Collection<int, Department>  $departments
     * @return Collection<int, array<string, mixed>>
     */
    public function buildDepartmentOptions(Collection $departments): Collection
    {
        return $departments->map(function (Department $department) {
            return [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
                'business_unit_id' => $department->business_unit_id,
                'business_unit_code' => $department->businessUnit?->code,
                'business_unit_name' => $department->businessUnit?->name,
                'template_type' => $this->templateService->templateTypeForDepartment($department),
                'actions' => $this->templateService->actionOptionsForDepartment($department),
            ];
        });
    }

    /**
     * Compact dashboard-only line item payload (no audit metadata) used by index().
     *
     * @param  Collection<int, CashflowProjectionLineItem>  $lineItems
     * @return Collection<int, array<string, mixed>>
     */
    public function buildDashboardLineItems(Collection $lineItems): Collection
    {
        return $lineItems->map(function (CashflowProjectionLineItem $item) {
            $meta = $this->templateService->metaForActionCode($item->action_code, $item->department);

            return [
                'id' => $item->id,
                'department_id' => $item->department_id,
                'department_code' => $item->department?->code,
                'department_name' => $item->department?->name,
                'business_unit_code' => $item->department?->businessUnit?->code,
                'flow_type' => $item->flow_type,
                'action_code' => $item->action_code,
                'action_label' => $this->templateService->displayLabelForAction($item->action_code, $item->department) ?? $meta['label'] ?? $item->action_code,
                'transaction_date' => optional($item->transaction_date)->format('Y-m-d'),
                'due_date' => optional($item->due_date)->format('Y-m-d'),
                'amount' => (float) $item->amount,
                'description' => $item->description,
                'keterangan' => $item->keterangan,
                'no_dokumen' => $item->no_dokumen,
                'nama_vendor' => $item->nama_vendor,
                'notes' => $item->notes,
                'is_estimated_date' => (bool) $item->is_estimated_date,
            ];
        })->values();
    }

    /**
     * Compact dashboard-only finance input payload used by index().
     *
     * @param  Collection<int, CashflowProjectionFinanceInput>  $financeInputs
     * @return Collection<int, array<string, mixed>>
     */
    public function buildDashboardFinanceInputs(Collection $financeInputs): Collection
    {
        return $financeInputs->map(function (CashflowProjectionFinanceInput $input) {
            return [
                'id' => $input->id,
                'month' => $input->month,
                'cash_on_hand' => (float) $input->cash_on_hand,
                'receivable_estimate' => (float) $input->receivable_estimate,
                'upcoming_event_revenue_estimate' => (float) $input->upcoming_event_revenue_estimate,
                'capital_injection_estimate' => (float) $input->capital_injection_estimate,
                'other_income' => (float) $input->other_income,
            ];
        })->values();
    }

    /**
     * @param  Collection<int, CashflowProjectionLineItem>  $lineItems
     * @return Collection<int, array<string, mixed>>
     */
    public function buildLineItemPayload(Collection $lineItems): Collection
    {
        $auditMetaById = $this->resolveAuditMetadata('line_item', $lineItems->pluck('id')->all());

        return $lineItems->map(function (CashflowProjectionLineItem $item) use ($auditMetaById) {
            $meta = $this->templateService->metaForActionCode($item->action_code, $item->department);
            $auditMeta = $auditMetaById[$item->id] ?? [];

            return [
                'id' => $item->id,
                'department_id' => $item->department_id,
                'department_code' => $item->department?->code,
                'department_name' => $item->department?->name,
                'business_unit_id' => $item->department?->business_unit_id,
                'business_unit_code' => $item->department?->businessUnit?->code,
                'business_unit_name' => $item->department?->businessUnit?->name,
                'flow_type' => $item->flow_type,
                'action_code' => $item->action_code,
                'action_label' => $this->templateService->displayLabelForAction($item->action_code, $item->department) ?? $meta['label'] ?? $item->action_code,
                'transaction_date' => optional($item->transaction_date)->format('Y-m-d'),
                'due_date' => optional($item->due_date)->format('Y-m-d'),
                'amount' => (float) $item->amount,
                'description' => $item->description,
                'keterangan' => $item->keterangan,
                'no_dokumen' => $item->no_dokumen,
                'nama_vendor' => $item->nama_vendor,
                'notes' => $item->notes,
                'is_estimated_date' => (bool) $item->is_estimated_date,
                'creator_name' => $auditMeta['creator_name'] ?? $item->creator?->name,
                'creator_department_label' => $auditMeta['creator_department_label'] ?? $item->creator?->primaryDepartment?->name,
                'has_edit_history' => (bool) ($auditMeta['has_edit_history'] ?? false),
                'updater_name' => $auditMeta['updater_name'] ?? $item->updater?->name,
                'updater_department_label' => $auditMeta['updater_department_label'] ?? $item->updater?->primaryDepartment?->name,
            ];
        });
    }

    /**
     * @param  Collection<int, CashflowProjectionFinanceInput>  $financeInputs
     * @return Collection<int, array<string, mixed>>
     */
    public function buildFinanceInputPayload(Collection $financeInputs): Collection
    {
        $auditMetaById = $this->resolveAuditMetadata('finance_input', $financeInputs->pluck('id')->all());

        return $financeInputs->map(function (CashflowProjectionFinanceInput $input) use ($auditMetaById) {
            $auditMeta = $auditMetaById[$input->id] ?? [];

            return [
                'id' => $input->id,
                'month' => $input->month,
                'cash_on_hand' => (float) $input->cash_on_hand,
                'receivable_estimate' => (float) $input->receivable_estimate,
                'upcoming_event_revenue_estimate' => (float) $input->upcoming_event_revenue_estimate,
                'capital_injection_estimate' => (float) $input->capital_injection_estimate,
                'other_income' => (float) $input->other_income,
                'creator_name' => $auditMeta['creator_name'] ?? $input->creator?->name,
                'creator_department_label' => $auditMeta['creator_department_label'] ?? $input->creator?->primaryDepartment?->name,
                'updater_name' => $auditMeta['updater_name'] ?? $input->updater?->name,
                'updater_department_label' => $auditMeta['updater_department_label'] ?? $input->updater?->primaryDepartment?->name,
            ];
        });
    }

    /**
     * @param  array<int, int>  $auditableIds
     * @return array<int, array<string, mixed>>
     */
    public function resolveAuditMetadata(string $auditableType, array $auditableIds): array
    {
        if ($auditableIds === []) {
            return [];
        }

        $auditLogs = CashflowProjectionAuditLog::query()
            ->where('auditable_type', $auditableType)
            ->whereIn('auditable_id', $auditableIds)
            ->orderBy('created_at')
            ->get()
            ->groupBy('auditable_id');

        $metadata = [];

        foreach ($auditLogs as $auditableId => $logs) {
            $createdLog = $logs->firstWhere('action', 'created');
            $latestLog = $logs->last();

            $metadata[(int) $auditableId] = [
                'creator_name' => $createdLog?->actor_user_name,
                'creator_department_label' => $createdLog?->actor_department_label,
                'has_edit_history' => $logs->contains(
                    fn (CashflowProjectionAuditLog $log) => $log->action === 'updated'
                ),
                'updater_name' => $latestLog?->actor_user_name,
                'updater_department_label' => $latestLog?->actor_department_label,
            ];
        }

        return $metadata;
    }
}
