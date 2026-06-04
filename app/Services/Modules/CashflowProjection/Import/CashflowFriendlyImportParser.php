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

        if (! $sheet instanceof Worksheet) {
            throw new \InvalidArgumentException('Sheet Data CFC tidak ditemukan.');
        }

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
