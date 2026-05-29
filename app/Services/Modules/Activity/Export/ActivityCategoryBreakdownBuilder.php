<?php

namespace App\Services\Modules\Activity\Export;

use App\Models\Modules\Activity\EmployeeTask;
use App\Services\Modules\Activity\ActivityReportAggregationService;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Build the "Breakdown Kategori" sheet showing per-category and
 * per-subcategory counts plus their relative share of the report.
 */
class ActivityCategoryBreakdownBuilder
{
    public function __construct(
        protected ActivityReportAggregationService $aggregationService,
        protected SpreadsheetStyleHelper $styleHelper,
    ) {}

    /**
     * Append the breakdown sheet to the spreadsheet.
     *
     * @param  Collection<int, EmployeeTask>  $tasks
     */
    public function build(Spreadsheet $spreadsheet, Collection $tasks): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Breakdown Kategori');

        $headers = ['Category', 'Subcategory', 'Count', '% of Category', '% of Report'];
        $this->styleHelper->writeHeaderRow($sheet, $headers, 'A1:E1');

        $row = 2;
        foreach ($this->aggregationService->buildCategoryBreakdown($tasks) as $item) {
            $sheet->setCellValue('A'.$row, $item['category']);
            $sheet->setCellValue('B'.$row, $item['subcategory']);
            $sheet->setCellValueExplicit('C'.$row, (int) $item['count'], DataType::TYPE_NUMERIC);
            $sheet->setCellValue('D'.$row, $this->styleHelper->formatPercentage($item['percentage_of_category']));
            $sheet->setCellValue('E'.$row, $this->styleHelper->formatPercentage($item['percentage_of_report']));
            $row++;
        }

        $this->styleHelper->autoSizeColumns($sheet, 'A', 'E');
        if ($row > 2) {
            $this->styleHelper->applyDataBorders($sheet, 'A2:E'.($row - 1));
        }
    }
}
