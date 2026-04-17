<?php

namespace App\Services\Modules\CashflowProjection;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionCycle;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashflowProjectionEntryImportService
{
    public const MAX_ROWS = 500;

    public const MAX_UI_ERRORS = 100;

    public const MAX_LOGGED_ERRORS = 20;

    /**
     * @var array<int, string>
     */
    private const TEMPLATE_HEADERS = [
        'line_item_id',
        'year',
        'business_unit_code',
        'department_code',
        'action_code',
        'transaction_date',
        'due_date',
        'is_estimated_date',
        'amount',
        'description',
        'notes',
    ];

    public function __construct(
        protected CashflowProjectionScopeService $scopeService,
        protected CashflowProjectionTemplateService $templateService,
        protected CashflowProjectionAuditService $auditService
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
            $this->logUnexpectedFailure($exception, $user, $activeBusinessUnitId, $fileName, 0);

            return $this->failurePayload(
                'Import gagal. Perbaiki file lalu coba lagi.',
                $fileName,
                0,
                []
            );
        }

        $workbookError = $this->validateWorkbookSchema($spreadsheet);
        if ($workbookError !== null) {
            $payload = $this->failurePayload(
                'Import gagal. Perbaiki file lalu coba lagi.',
                $fileName,
                0,
                [$workbookError]
            );

            $this->logValidationFailure($user, $activeBusinessUnitId, $fileName, 0, $payload);

            return $payload;
        }

        $templateSheet = $spreadsheet->getSheetByName('Template');
        if (! $templateSheet instanceof Worksheet) {
            $payload = $this->failurePayload(
                'Import gagal. Perbaiki file lalu coba lagi.',
                $fileName,
                0,
                [[
                    'row' => 1,
                    'column' => 'template',
                    'message' => 'Sheet Template tidak ditemukan.',
                    'value' => null,
                ]]
            );

            $this->logValidationFailure($user, $activeBusinessUnitId, $fileName, 0, $payload);

            return $payload;
        }

        $rows = $this->collectRows($templateSheet);
        $totalRows = count($rows);

        if ($totalRows === 0) {
            $payload = $this->failurePayload(
                'Import gagal. Perbaiki file lalu coba lagi.',
                $fileName,
                0,
                [[
                    'row' => null,
                    'column' => 'template',
                    'message' => 'Template tidak memiliki baris data untuk diproses.',
                    'value' => null,
                ]]
            );

            $this->logValidationFailure($user, $activeBusinessUnitId, $fileName, 0, $payload);

            return $payload;
        }

        if ($totalRows > self::MAX_ROWS) {
            $payload = $this->failurePayload(
                'Import gagal. Perbaiki file lalu coba lagi.',
                $fileName,
                $totalRows,
                [[
                    'row' => null,
                    'column' => 'template',
                    'message' => 'Jumlah baris melebihi batas maksimum 500 baris.',
                    'value' => $totalRows,
                ]]
            );

            $this->logValidationFailure($user, $activeBusinessUnitId, $fileName, $totalRows, $payload);

            return $payload;
        }

        $errors = $this->validateRows($rows, $scope);
        if ($errors['count'] > 0) {
            $payload = $this->failurePayload(
                'Import gagal. Perbaiki file lalu coba lagi.',
                $fileName,
                $totalRows,
                $errors['items'],
                $errors['count'],
                $errors['truncated']
            );

            $this->logValidationFailure($user, $activeBusinessUnitId, $fileName, $totalRows, $payload);

            return $payload;
        }

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
            $this->logUnexpectedFailure($exception, $user, $activeBusinessUnitId, $fileName, $totalRows);

            return $this->failurePayload(
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

    protected function validateWorkbookSchema(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): ?array
    {
        foreach (['Template', 'Reference', 'Existing Entries'] as $sheetName) {
            if (! $spreadsheet->sheetNameExists($sheetName)) {
                return [
                    'row' => null,
                    'column' => 'template',
                    'message' => 'Workbook wajib memiliki sheet Template, Reference, dan Existing Entries.',
                    'value' => $sheetName,
                ];
            }
        }

        $templateSheet = $spreadsheet->getSheetByName('Template');
        if (! $templateSheet instanceof Worksheet) {
            return [
                'row' => null,
                'column' => 'template',
                'message' => 'Sheet Template tidak ditemukan.',
                'value' => null,
            ];
        }

        $headers = [];
        foreach (range('A', 'K') as $index => $column) {
            $headers[] = trim((string) $templateSheet->getCell($column.'1')->getFormattedValue());
        }

        if ($headers !== self::TEMPLATE_HEADERS) {
            return [
                'row' => null,
                'column' => 'template',
                'message' => 'Header sheet Template harus sesuai urutan template resmi.',
                'value' => implode(', ', $headers),
            ];
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function collectRows(Worksheet $sheet): array
    {
        $rows = [];
        $highestRow = $sheet->getHighestDataRow();

        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            $row = $this->parseRow($sheet, $rowNumber);

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $rows[] = $row + ['row_number' => $rowNumber];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $scope
     * @return array{items: array<int, array<string, mixed>>, count: int, truncated: bool}
     */
    protected function validateRows(array $rows, array &$scope): array
    {
        $errors = [];
        $errorCount = 0;
        $seenLineItemIds = [];

        $existingIds = array_values(array_filter(array_map(
            fn (array $row): ?int => $row['line_item_id'],
            $rows
        )));

        $existingItems = CashflowProjectionLineItem::query()
            ->with(['department.businessUnit', 'cycle'])
            ->whereIn('id', $existingIds)
            ->whereIn('department_id', $scope['department_ids'])
            ->get()
            ->keyBy('id');

        $scope['existing_items'] = $existingItems->all();

        foreach ($rows as $row) {
            if ($row['line_item_id'] !== null) {
                if (isset($seenLineItemIds[$row['line_item_id']])) {
                    $this->pushError($errors, $errorCount, [
                        'row' => $row['row_number'],
                        'column' => 'line_item_id',
                        'message' => 'line_item_id duplikat ditemukan dalam file import.',
                        'value' => $row['line_item_id'],
                    ]);
                }

                $seenLineItemIds[$row['line_item_id']] = true;
            }

            $businessUnit = $scope['business_units']->get($row['business_unit_code']);
            if ($row['business_unit_code'] === null || $businessUnit === null) {
                $this->pushError($errors, $errorCount, [
                    'row' => $row['row_number'],
                    'column' => 'business_unit_code',
                    'message' => 'Kode business unit tidak valid atau tidak berada dalam scope Anda.',
                    'value' => $row['business_unit_code'],
                ]);
            }

            $department = $scope['departments_by_bu'][$row['business_unit_code']][$row['department_code']] ?? null;
            if ($row['department_code'] === null || ! $department instanceof Department) {
                $this->pushError($errors, $errorCount, [
                    'row' => $row['row_number'],
                    'column' => 'department_code',
                    'message' => 'Kode department tidak valid untuk business unit yang dipilih.',
                    'value' => $row['department_code'],
                ]);
            }

            if (! is_int($row['year']) || $row['year'] < 2000 || $row['year'] > 2100) {
                $this->pushError($errors, $errorCount, [
                    'row' => $row['row_number'],
                    'column' => 'year',
                    'message' => 'Year wajib antara 2000 sampai 2100.',
                    'value' => $row['year'],
                ]);
            }

            if (! $row['transaction_date'] instanceof CarbonImmutable) {
                $this->pushError($errors, $errorCount, [
                    'row' => $row['row_number'],
                    'column' => 'transaction_date',
                    'message' => 'Format harus YYYY-MM-DD.',
                    'value' => $row['transaction_date_raw'],
                ]);
            }

            if ($row['due_date_raw'] !== null && ! $row['due_date'] instanceof CarbonImmutable) {
                $this->pushError($errors, $errorCount, [
                    'row' => $row['row_number'],
                    'column' => 'due_date',
                    'message' => 'Format harus YYYY-MM-DD.',
                    'value' => $row['due_date_raw'],
                ]);
            }

            if (! is_bool($row['is_estimated_date'])) {
                $this->pushError($errors, $errorCount, [
                    'row' => $row['row_number'],
                    'column' => 'is_estimated_date',
                    'message' => 'Nilai harus TRUE atau FALSE.',
                    'value' => $row['is_estimated_date_raw'],
                ]);
            }

            if (! is_numeric($row['amount']) || (float) $row['amount'] < 0) {
                $this->pushError($errors, $errorCount, [
                    'row' => $row['row_number'],
                    'column' => 'amount',
                    'message' => 'Amount wajib berupa angka dan tidak boleh negatif.',
                    'value' => $row['amount'],
                ]);
            }

            if ($row['description'] === null || $row['description'] === '') {
                $this->pushError($errors, $errorCount, [
                    'row' => $row['row_number'],
                    'column' => 'description',
                    'message' => 'Description wajib diisi.',
                    'value' => null,
                ]);
            } elseif (mb_strlen($row['description']) > 255) {
                $this->pushError($errors, $errorCount, [
                    'row' => $row['row_number'],
                    'column' => 'description',
                    'message' => 'Description maksimal 255 karakter.',
                    'value' => mb_substr($row['description'], 0, 50),
                ]);
            }

            if ($department instanceof Department && ! $this->templateService->isActionAllowedForDepartment($row['action_code'] ?? '', $department)) {
                $this->pushError($errors, $errorCount, [
                    'row' => $row['row_number'],
                    'column' => 'action_code',
                    'message' => 'Kode tidak valid untuk department '.$department->code.'.',
                    'value' => $row['action_code'],
                ]);
            }

            if ($row['line_item_id'] !== null && ! $existingItems->has($row['line_item_id'])) {
                $this->pushError($errors, $errorCount, [
                    'row' => $row['row_number'],
                    'column' => 'line_item_id',
                    'message' => 'line_item_id tidak ditemukan atau tidak berada dalam scope Anda.',
                    'value' => $row['line_item_id'],
                ]);
            }
        }

        return [
            'items' => $errors,
            'count' => $errorCount,
            'truncated' => $errorCount > self::MAX_UI_ERRORS,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseRow(Worksheet $sheet, int $rowNumber): array
    {
        $lineItemIdRaw = $this->normalizeString($this->cellValue($sheet->getCell('A'.$rowNumber)));
        $yearRaw = $this->normalizeString($this->cellValue($sheet->getCell('B'.$rowNumber)));
        $businessUnitCode = $this->normalizeString($this->cellValue($sheet->getCell('C'.$rowNumber)));
        $departmentCode = $this->normalizeString($this->cellValue($sheet->getCell('D'.$rowNumber)));
        $actionCode = $this->normalizeString($this->cellValue($sheet->getCell('E'.$rowNumber)));
        $transactionDateRaw = $this->cellValue($sheet->getCell('F'.$rowNumber));
        $dueDateRaw = $this->cellValue($sheet->getCell('G'.$rowNumber));
        $isEstimatedDateRaw = $this->cellValue($sheet->getCell('H'.$rowNumber));
        $amountRaw = $this->cellValue($sheet->getCell('I'.$rowNumber));
        $description = $this->normalizeString($this->cellValue($sheet->getCell('J'.$rowNumber)));
        $notes = $this->normalizeString($this->cellValue($sheet->getCell('K'.$rowNumber)));

        return [
            'line_item_id' => $lineItemIdRaw !== null && ctype_digit($lineItemIdRaw) ? (int) $lineItemIdRaw : null,
            'line_item_id_raw' => $lineItemIdRaw,
            'year' => $yearRaw !== null && ctype_digit($yearRaw) ? (int) $yearRaw : null,
            'business_unit_code' => $businessUnitCode,
            'department_code' => $departmentCode,
            'action_code' => $actionCode,
            'transaction_date' => $this->parseDateValue($transactionDateRaw),
            'transaction_date_raw' => $this->normalizeScalarForError($transactionDateRaw),
            'due_date' => $this->parseDateValue($dueDateRaw),
            'due_date_raw' => $this->normalizeScalarForError($dueDateRaw),
            'is_estimated_date' => $this->parseBooleanValue($isEstimatedDateRaw),
            'is_estimated_date_raw' => $this->normalizeScalarForError($isEstimatedDateRaw),
            'amount' => is_numeric($amountRaw) ? (float) $amountRaw : $this->normalizeScalarForError($amountRaw),
            'description' => $description,
            'notes' => $notes,
        ];
    }

    protected function isEmptyRow(array $row): bool
    {
        return collect([
            $row['line_item_id_raw'],
            $row['year'],
            $row['business_unit_code'],
            $row['department_code'],
            $row['action_code'],
            $row['transaction_date_raw'],
            $row['due_date_raw'],
            $row['is_estimated_date_raw'],
            $row['amount'],
            $row['description'],
            $row['notes'],
        ])->every(fn ($value) => $value === null || $value === '');
    }

    protected function pushError(array &$errors, int &$errorCount, array $error): void
    {
        $errorCount++;

        if (count($errors) < self::MAX_UI_ERRORS) {
            $errors[] = $error;
        }
    }

    protected function parseDateValue(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        if (is_numeric($value)) {
            return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $value));
        }

        $stringValue = $this->normalizeString($value);
        if ($stringValue === null || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $stringValue)) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $stringValue);

            return $date && $date->format('Y-m-d') === $stringValue ? $date : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function parseBooleanValue(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $stringValue = strtoupper((string) $this->normalizeString($value));

        return match ($stringValue) {
            'TRUE' => true,
            'FALSE' => false,
            default => null,
        };
    }

    protected function cellValue(Cell $cell): mixed
    {
        return $cell->getValue();
    }

    protected function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);

        return $stringValue === '' ? null : $stringValue;
    }

    protected function normalizeScalarForError(mixed $value): string|int|float|bool|null
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_scalar($value)) {
            return is_string($value) ? trim($value) : $value;
        }

        return null;
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

    /**
     * @param  array<int, array<string, mixed>>  $errors
     * @return array<string, mixed>
     */
    protected function failurePayload(
        string $summary,
        string $fileName,
        int $totalRows,
        array $errors,
        ?int $errorCount = null,
        bool $truncated = false
    ): array {
        $failedRows = collect($errors)
            ->pluck('row')
            ->filter(fn ($row) => is_int($row) && $row > 1)
            ->unique()
            ->count();

        return [
            'status' => 'failed',
            'summary' => $summary,
            'file_name' => $fileName,
            'total_rows' => $totalRows,
            'processed_rows' => $totalRows,
            'created_rows' => 0,
            'updated_rows' => 0,
            'failed_rows' => $failedRows,
            'truncated' => $truncated || (($errorCount ?? count($errors)) > self::MAX_UI_ERRORS),
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function logValidationFailure(User $user, int $activeBusinessUnitId, string $fileName, int $parsedRows, array $payload): void
    {
        Log::warning('cashflow_entries_import_failed', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'active_business_unit_id' => $activeBusinessUnitId,
            'file_name' => $fileName,
            'parsed_rows' => $parsedRows,
            'failed_rows' => $payload['failed_rows'] ?? 0,
            'errors' => array_slice($payload['errors'] ?? [], 0, self::MAX_LOGGED_ERRORS),
        ]);
    }

    protected function logUnexpectedFailure(\Throwable $exception, User $user, int $activeBusinessUnitId, string $fileName, int $parsedRows): void
    {
        Log::error('cashflow_entries_import_exception', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'active_business_unit_id' => $activeBusinessUnitId,
            'file_name' => $fileName,
            'parsed_rows' => $parsedRows,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
