<?php

namespace App\Services\Modules\Activity\Export;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Shared spreadsheet formatting helpers used by every sheet builder in
 * the Activity export. Centralised so the visual style stays consistent
 * (header band, borders, status colours) across detail/summary/category
 * /raw sheets.
 */
class SpreadsheetStyleHelper
{
    /**
     * Write a styled header row at row 1 spanning the given range.
     *
     * @param  array<int, string>  $headers
     */
    public function writeHeaderRow(Worksheet $sheet, array $headers, string $range): void
    {
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column.'1', $header);
            $column++;
        }

        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2596BE'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);
    }

    /**
     * Auto-size every column in the inclusive [$start, $end] range.
     */
    public function autoSizeColumns(Worksheet $sheet, string $start, string $end): void
    {
        foreach (range($start, $end) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Apply thin borders to every cell in the data range.
     */
    public function applyDataBorders(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);
    }

    /**
     * Map a task status to its background colour for the status cell.
     */
    public function statusColor(string $status): string
    {
        return match ($status) {
            'planned' => 'E5E7EB',
            'in_progress' => 'DBEAFE',
            'completed' => 'D1FAE5',
            'cancelled' => 'FEE2E2',
            default => 'FFFFFF',
        };
    }

    /**
     * Format a percentage value as a single-decimal string with the % sign.
     */
    public function formatPercentage(float $value): string
    {
        return number_format($value, 1).'%';
    }
}
