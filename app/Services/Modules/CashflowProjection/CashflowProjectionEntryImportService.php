<?php

namespace App\Services\Modules\CashflowProjection;

use App\Models\Core\BusinessUnit;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionCycle;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashflowProjectionEntryImportService
{
    public const MAX_ROWS = 500;

    public const MAX_UI_ERRORS = 100;

    public const MAX_LOGGED_ERRORS = 20;

    public function __construct(
        protected CashflowProjectionScopeService $scopeService,
        protected CashflowProjectionTemplateService $templateService,
        protected CashflowProjectionAuditService $auditService,
        protected CashflowProjectionImportRowParser $rowParser,
        protected CashflowProjectionImportValidator $importValidator,
        protected CashflowProjectionImportErrorAggregator $errorAggregator
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function import(string $filePath, string $fileName, User $user, int $activeBusinessUnitId): array
    {
        $scope = $this->buildScope($user, $activeBusinessUnitId);

        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Throwable $exception) {
            $this->errorAggregator->logUnexpectedFailure($exception, $user, $activeBusinessUnitId, $fileName, 0);

            return $this->errorAggregator->failurePayload(
                'Import gagal. Perbaiki file lalu coba lagi.',
                $fileName,
                0,
                []
            );
        }

        $workbookError = $this->importValidator->validateWorkbookSchema($spreadsheet);
        if ($workbookError !== null) {
            return $this->respondFailure($user, $activeBusinessUnitId, $fileName, 0, [$workbookError]);
        }

        $templateSheet = $spreadsheet->getSheetByName('Template');
        if (! $templateSheet instanceof Worksheet) {
            return $this->respondFailure($user, $activeBusinessUnitId, $fileName, 0, [[
                'row' => 1,
                'column' => 'template',
                'message' => 'Sheet Template tidak ditemukan.',
                'value' => null,
            ]]);
        }

        $rows = $this->rowParser->collectRows($templateSheet);
        $totalRows = count($rows);

        if ($totalRows === 0) {
            return $this->respondFailure($user, $activeBusinessUnitId, $fileName, 0, [[
                'row' => null,
                'column' => 'template',
                'message' => 'Template tidak memiliki baris data untuk diproses.',
                'value' => null,
            ]]);
        }

        if ($totalRows > self::MAX_ROWS) {
            return $this->respondFailure($user, $activeBusinessUnitId, $fileName, $totalRows, [[
                'row' => null,
                'column' => 'template',
                'message' => 'Jumlah baris melebihi batas maksimum 500 baris.',
                'value' => $totalRows,
            ]]);
        }

        $errors = $this->importValidator->validateRows($rows, $scope);
        if ($errors['count'] > 0) {
            $payload = $this->errorAggregator->failurePayload(
                'Import gagal. Perbaiki file lalu coba lagi.',
                $fileName,
                $totalRows,
                $errors['items'],
                $errors['count'],
                $errors['truncated']
            );

            $this->errorAggregator->logValidationFailure($user, $activeBusinessUnitId, $fileName, $totalRows, $payload);

            return $payload;
        }

        return $this->persistRows($rows, $scope, $user, $activeBusinessUnitId, $fileName, $totalRows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $errors
     * @return array<string, mixed>
     */
    private function respondFailure(User $user, int $activeBusinessUnitId, string $fileName, int $totalRows, array $errors): array
    {
        $payload = $this->errorAggregator->failurePayload(
            'Import gagal. Perbaiki file lalu coba lagi.',
            $fileName,
            $totalRows,
            $errors
        );

        $this->errorAggregator->logValidationFailure($user, $activeBusinessUnitId, $fileName, $totalRows, $payload);

        return $payload;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $scope
     * @return array<string, mixed>
     */
    private function persistRows(array $rows, array $scope, User $user, int $activeBusinessUnitId, string $fileName, int $totalRows): array
    {
        $actorDepartment = $this->scopeService->currentActorDepartment($user, $activeBusinessUnitId);
        $createdRows = 0;
        $updatedRows = 0;

        try {
            DB::transaction(function () use ($rows, $scope, $user, $actorDepartment, &$createdRows, &$updatedRows): void {
                foreach ($rows as $row) {
                    $department = $scope['departments_by_bu'][$row['business_unit_code']][$row['department_code']];
                    $actionMeta = $this->templateService->metaForActionCode($row['action_code'], $department);

                    if (! is_array($actionMeta)) {
                        continue;
                    }

                    $targetCycle = $this->findOrCreateCycle((int) $department->business_unit_id, (int) $row['year'], $user->id);

                    if ($row['line_item_id'] !== null) {
                        /** @var CashflowProjectionLineItem $lineItem */
                        $lineItem = $scope['existing_items'][$row['line_item_id']];
                        $oldValues = $this->lineItemAuditValues($lineItem);

                        $lineItem->forceFill([
                            'cycle_id' => $targetCycle->id,
                            'department_id' => $department->id,
                            'flow_type' => $actionMeta['flow_type'],
                            'action_code' => $row['action_code'],
                            'transaction_date' => $row['transaction_date'],
                            'due_date' => $row['due_date'],
                            'is_estimated_date' => $row['is_estimated_date'],
                            'amount' => $row['amount'],
                            'description' => $row['description'],
                            'notes' => $row['notes'],
                            'source_type' => 'import',
                            'updated_by' => $user->id,
                        ])->save();

                        $lineItem->load('department');
                        $this->auditService->logLineItemAction(
                            'updated',
                            $lineItem,
                            $user,
                            $actorDepartment,
                            $oldValues,
                            $this->lineItemAuditValues($lineItem)
                        );

                        $updatedRows++;

                        continue;
                    }

                    $lineItem = CashflowProjectionLineItem::query()->create([
                        'cycle_id' => $targetCycle->id,
                        'department_id' => $department->id,
                        'flow_type' => $actionMeta['flow_type'],
                        'action_code' => $row['action_code'],
                        'transaction_date' => $row['transaction_date'],
                        'due_date' => $row['due_date'],
                        'is_estimated_date' => $row['is_estimated_date'],
                        'amount' => $row['amount'],
                        'description' => $row['description'],
                        'notes' => $row['notes'],
                        'source_type' => 'import',
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);

                    $lineItem->load('department');
                    $this->auditService->logLineItemAction(
                        'created',
                        $lineItem,
                        $user,
                        $actorDepartment,
                        null,
                        $this->lineItemAuditValues($lineItem)
                    );

                    $createdRows++;
                }
            });
        } catch (\Throwable $exception) {
            $this->errorAggregator->logUnexpectedFailure($exception, $user, $activeBusinessUnitId, $fileName, $totalRows);

            return $this->errorAggregator->failurePayload(
                'Import gagal. Perbaiki file lalu coba lagi.',
                $fileName,
                $totalRows,
                []
            );
        }

        Log::info('cashflow_entries_import_succeeded', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'active_business_unit_id' => $activeBusinessUnitId,
            'file_name' => $fileName,
            'parsed_rows' => $totalRows,
            'created_rows' => $createdRows,
            'updated_rows' => $updatedRows,
        ]);

        return [
            'status' => 'success',
            'summary' => 'Import berhasil diproses.',
            'file_name' => $fileName,
            'total_rows' => $totalRows,
            'processed_rows' => $totalRows,
            'created_rows' => $createdRows,
            'updated_rows' => $updatedRows,
            'failed_rows' => 0,
            'truncated' => false,
            'errors' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildScope(User $user, int $activeBusinessUnitId): array
    {
        $allowedBusinessUnitIds = $this->scopeService->allowedBusinessUnitIds($user, $activeBusinessUnitId);
        $departments = $this->scopeService->allowedDepartments($user, $activeBusinessUnitId)->values();

        $businessUnits = BusinessUnit::query()
            ->whereIn('id', $allowedBusinessUnitIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('code');

        $departmentsByBu = [];
        foreach ($departments as $department) {
            $businessUnitCode = (string) $department->businessUnit?->code;
            if ($businessUnitCode === '') {
                continue;
            }

            $departmentsByBu[$businessUnitCode][$department->code] = $department;
        }

        return [
            'business_units' => $businessUnits,
            'departments_by_bu' => $departmentsByBu,
            'department_ids' => $departments->pluck('id')->all(),
            'existing_items' => [],
        ];
    }

    protected function findOrCreateCycle(int $businessUnitId, int $year, int $userId): CashflowProjectionCycle
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

    /**
     * @return array<string, mixed>
     */
    protected function lineItemAuditValues(CashflowProjectionLineItem $lineItem): array
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
