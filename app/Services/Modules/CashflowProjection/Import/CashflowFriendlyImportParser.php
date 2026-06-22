<?php

namespace App\Services\Modules\CashflowProjection\Import;

use Carbon\CarbonImmutable;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashflowFriendlyImportParser
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('Data CFC');

        if ($sheet instanceof Worksheet) {
            return $this->parseDataCfcSheet($sheet);
        }

        $templateSheet = $spreadsheet->getSheetByName('Template');
        if ($templateSheet instanceof Worksheet) {
            return $this->parseTemplateSheet($templateSheet);
        }

        throw new \InvalidArgumentException('Sheet Data CFC atau Template tidak ditemukan.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseDataCfcSheet(Worksheet $sheet): array
    {
        $rows = [];
        $highestRow = $sheet->getHighestDataRow();

        for ($rowNumber = 4; $rowNumber <= $highestRow; $rowNumber++) {
            $row = $this->parseRow($sheet, $rowNumber);

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseTemplateSheet(Worksheet $sheet): array
    {
        $rows = [];
        $highestRow = $sheet->getHighestDataRow();

        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            $row = [
                'row_number' => $rowNumber,
                'line_item_id' => $this->parseInteger($sheet->getCell('A'.$rowNumber)->getValue()),
                'year' => $this->parseInteger($sheet->getCell('B'.$rowNumber)->getValue()),
                'business_unit_code' => $this->normalizeString($sheet->getCell('C'.$rowNumber)->getValue()),
                'department_code' => $this->normalizeString($sheet->getCell('D'.$rowNumber)->getValue()),
                'action_code' => $this->normalizeString($sheet->getCell('E'.$rowNumber)->getValue()),
                'transaction_date' => $this->parseDate($sheet->getCell('F'.$rowNumber)->getValue()),
                'due_date' => $this->parseDate($sheet->getCell('G'.$rowNumber)->getValue()),
                'is_estimated_date' => $this->parseBoolean($sheet->getCell('H'.$rowNumber)->getValue()),
                'amount' => $this->parseAmount($sheet->getCell('I'.$rowNumber)->getFormattedValue()),
                'description' => $this->normalizeString($sheet->getCell('J'.$rowNumber)->getValue()),
                'keterangan' => $this->normalizeString($sheet->getCell('K'.$rowNumber)->getValue()),
                'notes' => $this->normalizeString($sheet->getCell('L'.$rowNumber)->getValue()),
            ];

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseRow(Worksheet $sheet, int $rowNumber): array
    {
        $noDokumen = $this->normalizeString($sheet->getCell('C'.$rowNumber)->getValue());
        $namaVendor = $this->normalizeString($sheet->getCell('D'.$rowNumber)->getValue());

        return [
            'row_number' => $rowNumber,
            'bulan' => $this->normalizeString($sheet->getCell('A'.$rowNumber)->getFormattedValue()),
            'transaction_date' => $this->parseDate($sheet->getCell('B'.$rowNumber)->getValue()),
            'no_dokumen' => $noDokumen,
            'nama_vendor' => $namaVendor,
            'description' => $this->normalizeString($sheet->getCell('E'.$rowNumber)->getValue()),
            'amount' => $this->parseAmount($sheet->getCell('F'.$rowNumber)->getFormattedValue()),
            'due_date' => $this->parseDate($sheet->getCell('G'.$rowNumber)->getValue()),
            'keterangan' => $this->normalizeString($sheet->getCell('H'.$rowNumber)->getValue()),
            'business_unit_code' => $this->normalizeString($sheet->getCell('I'.$rowNumber)->getValue()),
            'notes' => $this->buildNotes($noDokumen, $namaVendor),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        return collect($row)
            ->except(['row_number', 'notes'])
            ->every(fn ($value): bool => $value === null || $value === '');
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value)->format('Y-m-d');
        }

        if (is_numeric($value)) {
            return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
        }

        $stringValue = $this->normalizeString($value);
        if ($stringValue === null) {
            return null;
        }

        foreach (['d-M-y', 'd-M-Y', 'Y-m-d'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat('!'.$format, $stringValue);
                if ($date instanceof CarbonImmutable) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function parseAmount(mixed $value): float|string|null
    {
        $stringValue = $this->normalizeString($value);
        if ($stringValue === null) {
            return null;
        }

        $normalized = str_replace([',', ' '], '', $stringValue);

        return is_numeric($normalized) ? (float) $normalized : $stringValue;
    }

    private function parseInteger(mixed $value): ?int
    {
        $stringValue = $this->normalizeString($value);

        return $stringValue !== null && ctype_digit($stringValue) ? (int) $stringValue : null;
    }

    private function parseBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtoupper((string) $value), ['TRUE', '1', 'YES', 'Y'], true);
    }

    private function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);

        return $stringValue === '' ? null : $stringValue;
    }

    private function buildNotes(?string $noDokumen, ?string $namaVendor): ?string
    {
        $parts = [];

        if ($noDokumen) {
            $parts[] = 'No Dokumen: '.$noDokumen;
        }

        if ($namaVendor) {
            $parts[] = 'Vendor: '.$namaVendor;
        }

        return $parts === [] ? null : implode("\n", $parts);
    }
}
