<?php

namespace App\Services\Modules\CashflowProjection;

use Carbon\CarbonImmutable;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Parses Cashflow Projection import workbook rows into normalized arrays.
 *
 * Lifted verbatim from CashflowProjectionEntryImportService:
 *  - collectRows
 *  - parseRow
 *  - isEmptyRow
 *  - parseDateValue / parseBooleanValue
 *  - cellValue / normalizeString / normalizeScalarForError
 */
class CashflowProjectionImportRowParser
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function collectRows(Worksheet $sheet): array
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
     * @return array<string, mixed>
     */
    public function parseRow(Worksheet $sheet, int $rowNumber): array
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
        $keterangan = $this->normalizeString($this->cellValue($sheet->getCell('K'.$rowNumber)));
        $notes = $this->normalizeString($this->cellValue($sheet->getCell('L'.$rowNumber)));

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
            'keterangan' => $keterangan,
            'notes' => $notes,
        ];
    }

    public function isEmptyRow(array $row): bool
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
            $row['keterangan'],
            $row['notes'],
        ])->every(fn ($value) => $value === null || $value === '');
    }

    public function parseDateValue(mixed $value): ?CarbonImmutable
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

    public function parseBooleanValue(mixed $value): ?bool
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

    public function cellValue(Cell $cell): mixed
    {
        return $cell->getValue();
    }

    public function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);

        return $stringValue === '' ? null : $stringValue;
    }

    public function normalizeScalarForError(mixed $value): string|int|float|bool|null
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_scalar($value)) {
            return is_string($value) ? trim($value) : $value;
        }

        return null;
    }
}
