<?php

namespace App\Services\Modules\Activity\Export;

use App\Models\Modules\Activity\EmployeeTask;
use App\Services\Modules\Activity\ActivityReportAggregationService;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Build the "Ringkasan" summary sheet for the Activity export.
 *
 * The summary surfaces the same KPI numbers shown on the report page so
 * a downloaded spreadsheet matches what the user saw on screen.
 */
class ActivitySummarySheetBuilder
{
    public function __construct(
        protected ActivityReportAggregationService $aggregationService,
        protected SpreadsheetStyleHelper $styleHelper,
    ) {}

    /**
     * Append the summary sheet to the spreadsheet.
     *
     * @param  Collection<int, EmployeeTask>  $tasks
     */
    public function build(Spreadsheet $spreadsheet, Collection $tasks): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Ringkasan');

        $summary = $this->aggregationService->buildExportSummary($tasks);

        $sheet->setCellValue('A1', 'Ringkasan Laporan Aktivitas');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $rows = [
            ['Generated:', $summary['generated_at'], false],
            ['Total Activities:', $summary['total_activities'], true],
            [
                'Completed ('.$summary['status_rows'][0]['count'].')',
                $this->styleHelper->formatPercentage($summary['status_rows'][0]['percentage']),
                false,
            ],
            [
                'In Progress ('.$summary['status_rows'][1]['count'].')',
                $this->styleHelper->formatPercentage($summary['status_rows'][1]['percentage']),
                false,
            ],
            [
                'Planned ('.$summary['status_rows'][2]['count'].')',
                $this->styleHelper->formatPercentage($summary['status_rows'][2]['percentage']),
                false,
            ],
            [
                'Cancelled ('.$summary['status_rows'][3]['count'].')',
                $this->styleHelper->formatPercentage($summary['status_rows'][3]['percentage']),
                false,
            ],
            [
                'Completion Rate:',
                $this->styleHelper->formatPercentage($summary['completion_rate']),
                false,
            ],
            [
                'Top Category:',
                $summary['top_category']['name'].' ('.$summary['top_category']['count'].')',
                false,
            ],
            [
                'Top Subcategory:',
                $summary['top_subcategory']['name'].' ('.$summary['top_subcategory']['count'].')',
                false,
            ],
        ];

        $rowIndex = 3;
        foreach ($rows as [$label, $value, $isNumericValue]) {
            $sheet->setCellValue('A'.$rowIndex, $label);
            if ($isNumericValue) {
                $sheet->setCellValueExplicit('B'.$rowIndex, (int) $value, DataType::TYPE_NUMERIC);
            } else {
                $sheet->setCellValue('B'.$rowIndex, $value);
            }
            $rowIndex++;
        }

        $this->styleHelper->autoSizeColumns($sheet, 'A', 'B');
    }
}
