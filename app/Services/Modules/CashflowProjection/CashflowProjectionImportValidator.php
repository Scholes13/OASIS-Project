<?php

namespace App\Services\Modules\CashflowProjection;

use App\Models\Core\Department;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use Carbon\CarbonImmutable;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Workbook + per-row validation for the Cashflow Projection import flow.
 *
 * Lifted verbatim from CashflowProjectionEntryImportService:
 *  - validateWorkbookSchema (with required sheets + header order check)
 *  - validateRows (line_item_id duplicates, BU/department scope, year/date
 *    formats, amount, description length, action allowed for department,
 *    line_item_id ownership in scope)
 *
 * Mutates $scope to inject 'existing_items' for the importer to reuse.
 */
class CashflowProjectionImportValidator
{
    public const MAX_UI_ERRORS = 100;

    /**
     * @var array<int, string>
     */
    public const TEMPLATE_HEADERS = [
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
        'keterangan',
        'notes',
    ];

    public function __construct(
        protected CashflowProjectionTemplateService $templateService
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function validateWorkbookSchema(Spreadsheet $spreadsheet): ?array
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
        foreach (range('A', 'L') as $index => $column) {
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
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $scope
     * @return array{items: array<int, array<string, mixed>>, count: int, truncated: bool}
     */
    public function validateRows(array $rows, array &$scope): array
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
            } elseif ($department->activeChildren()->exists()) {
                $this->pushError($errors, $errorCount, [
                    'row' => $row['row_number'],
                    'column' => 'department_code',
                    'message' => 'Cashflow line item harus dibuat di sub-department, bukan root department dengan sub-department aktif.',
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
            } elseif (mb_strlen($row['description']) > 5000) {
                $this->pushError($errors, $errorCount, [
                    'row' => $row['row_number'],
                    'column' => 'description',
                    'message' => 'Description maksimal 5000 karakter.',
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
     * @param  array<int, array<string, mixed>>  $errors
     * @param  array<string, mixed>  $error
     */
    protected function pushError(array &$errors, int &$errorCount, array $error): void
    {
        $errorCount++;

        if (count($errors) < self::MAX_UI_ERRORS) {
            $errors[] = $error;
        }
    }
}
