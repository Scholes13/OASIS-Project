<?php

namespace App\Services\Modules\CashflowProjection\Import;

use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use App\Services\Modules\CashflowProjection\CashflowProjectionAuditService;
use App\Services\Modules\CashflowProjection\CashflowProjectionScopeService;
use App\Services\Modules\CashflowProjection\CashflowProjectionTemplateService;
use App\Services\Modules\CashflowProjection\LinkedCycleMerger;
use Illuminate\Support\Facades\DB;

class CashflowImportConfirmService
{
    public function __construct(
        protected CashflowProjectionScopeService $scopeService,
        protected CashflowProjectionTemplateService $templateService,
        protected CashflowProjectionAuditService $auditService,
        protected LinkedCycleMerger $linkedCycleMerger
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{summary: array<string, int>}
     */
    public function confirm(array $rows, User $user, int $activeBusinessUnitId, int $contextYear, int $contextMonth): array
    {
        if (collect($rows)->contains(fn (array $row): bool => in_array($row['status'], ['need_review', 'invalid'], true))) {
            abort(response()->json(['message' => 'Import masih memiliki row yang perlu review atau invalid.'], 422));
        }

        $allowedDepartmentIds = $this->scopeService->allowedDepartments($user, $activeBusinessUnitId)->pluck('id')->all();
        $actorDepartment = $this->scopeService->currentActorDepartment($user, $activeBusinessUnitId);
        $summary = ['created_rows' => 0, 'updated_rows' => 0, 'skipped_rows' => 0];

        DB::transaction(function () use ($rows, $user, $allowedDepartmentIds, $actorDepartment, &$summary): void {
            foreach ($rows as $row) {
                if ($row['status'] === 'no_change') {
                    $summary['skipped_rows']++;

                    continue;
                }

                $department = $this->resolveDepartment($row);
                abort_unless($department && in_array($department->id, $allowedDepartmentIds, true), 403);
                abort_if($department->activeChildren()->exists(), 422, 'Cashflow line item harus dibuat di sub-department.');
                abort_unless($this->templateService->isActionAllowedForDepartment((string) $row['action_code'], $department), 422, 'Action tidak sesuai template departemen.');

                if ($row['status'] === 'update') {
                    $lineItem = CashflowProjectionLineItem::query()->findOrFail((int) data_get($row, 'match.line_item_id'));
                    abort_unless(in_array((int) $lineItem->department_id, $allowedDepartmentIds, true), 403);
                    $oldValues = $this->lineItemAuditValues($lineItem);
                    $this->fillLineItem($lineItem, $row, $department, $user)->save();
                    $lineItem->load('department');
                    $this->auditService->logLineItemAction('updated', $lineItem, $user, $actorDepartment, $oldValues, $this->lineItemAuditValues($lineItem));
                    $summary['updated_rows']++;

                    continue;
                }

                $cycle = $this->linkedCycleMerger->findOrCreateCycle((int) $department->business_unit_id, (int) substr((string) $row['transaction_date'], 0, 4), $user->id);
                $lineItem = new CashflowProjectionLineItem(['cycle_id' => $cycle->id, 'created_by' => $user->id]);
                $this->fillLineItem($lineItem, $row, $department, $user)->save();
                $lineItem->load('department');
                $this->auditService->logLineItemAction('created', $lineItem, $user, $actorDepartment, null, $this->lineItemAuditValues($lineItem));
                $summary['created_rows']++;
            }
        });

        return ['summary' => $summary];
    }

    private function resolveDepartment(array $row): ?Department
    {
        return Department::query()
            ->whereHas('businessUnit', fn ($query) => $query->where('code', strtoupper((string) $row['business_unit_code'])))
            ->where('code', (string) $row['department_code'])
            ->where('is_active', true)
            ->first();
    }

    private function fillLineItem(CashflowProjectionLineItem $lineItem, array $row, Department $department, User $user): CashflowProjectionLineItem
    {
        $lineItem->forceFill([
            'department_id' => $department->id,
            'flow_type' => $row['flow_type'],
            'action_code' => $row['action_code'],
            'transaction_date' => $row['transaction_date'],
            'due_date' => $row['due_date'] ?? null,
            'is_estimated_date' => (bool) ($row['is_estimated_date'] ?? false),
            'amount' => $row['amount'],
            'description' => $row['description'],
            'keterangan' => $row['keterangan'] ?? null,
            'no_dokumen' => $row['no_dokumen'] ?? null,
            'nama_vendor' => $row['nama_vendor'] ?? null,
            'notes' => $row['notes'] ?? null,
            'source_type' => 'import',
            'updated_by' => $user->id,
        ]);

        return $lineItem;
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
            'no_dokumen' => $lineItem->no_dokumen,
            'nama_vendor' => $lineItem->nama_vendor,
            'notes' => $lineItem->notes,
        ];
    }
}
