<?php

namespace App\Services\Modules\CashflowProjection;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Builds the multi-sheet XLS export for Cashflow Projection dashboards.
 *
 * Lifted verbatim from CashflowProjectionController:
 *  - export() workbook composition
 *  - formatExportPeriodLabel
 *  - buildExportFilename
 *  - buildExportWorkbookXml + escapeExportXml
 *
 * Produces 4 sheets (Summary, Daily Movement, Raw Entries, Finance Inputs)
 * with the same Microsoft XML SpreadsheetML output the controller streamed
 * before, so existing import/export round-trips remain compatible.
 */
class CashflowExcelBuilder
{
    /**
     * @param  array<string, mixed>  $dashboardFilters
     * @param  array<string, mixed>  $summary
     * @param  array<int, array<string, mixed>>  $monthlySummary
     * @param  array<int, array<string, mixed>>  $dailySummary
     * @param  array<int, array<string, mixed>>|Collection<int, array<string, mixed>>  $rawEntries
     * @param  array<int, array<string, mixed>>|Collection<int, array<string, mixed>>  $financeInputRows
     */
    public function streamWorkbook(
        int $year,
        array $dashboardFilters,
        string $scope,
        array $summary,
        array $monthlySummary,
        array $dailySummary,
        $rawEntries,
        $financeInputRows
    ): StreamedResponse {
        $periodLabel = $this->formatExportPeriodLabel($dashboardFilters);
        $scopeLabel = $scope === 'consolidated' ? 'Consolidated' : 'BU Only';
        $filename = $this->buildExportFilename($year, $dashboardFilters, $scope);

        $summaryRows = $this->buildSummaryRows($year, $periodLabel, $scopeLabel, $summary, $monthlySummary);
        $dailyRows = $this->buildDailyRows($dailySummary);
        $rawEntryRows = $this->buildRawEntryRows($rawEntries);
        $financeRows = $this->buildFinanceRows($year, $financeInputRows);

        $workbook = $this->buildExportWorkbookXml([
            'Summary' => $summaryRows,
            'Daily Movement' => $dailyRows,
            'Raw Entries' => $rawEntryRows,
            'Finance Inputs' => $financeRows,
        ]);

        return response()->streamDownload(function () use ($workbook) {
            echo $workbook;
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename='.$filename,
        ]);
    }

    /**
     * @param  array<string, mixed>  $dashboardFilters
     */
    public function formatExportPeriodLabel(array $dashboardFilters): string
    {
        if ($dashboardFilters['mode'] === 'year') {
            return 'FY '.$dashboardFilters['year'];
        }

        if ($dashboardFilters['mode'] === 'range') {
            return $dashboardFilters['start']->format('Y-m-d').' to '.$dashboardFilters['end']->format('Y-m-d');
        }

        return CarbonImmutable::create($dashboardFilters['year'], $dashboardFilters['month'], 1)->format('M Y');
    }

    /**
     * @param  array<string, mixed>  $dashboardFilters
     */
    public function buildExportFilename(int $year, array $dashboardFilters, string $scope): string
    {
        $scopeSegment = $scope === 'consolidated' ? 'consolidated' : 'bu-only';

        if ($dashboardFilters['mode'] === 'year') {
            return 'cashflow-projection-'.$scopeSegment.'-'.$year.'.xls';
        }

        if ($dashboardFilters['mode'] === 'range') {
            return 'cashflow-projection-'.$scopeSegment.'-'.$dashboardFilters['start']->format('Ymd').'-to-'.$dashboardFilters['end']->format('Ymd').'.xls';
        }

        return 'cashflow-projection-'.$scopeSegment.'-'.$year.'-'.str_pad((string) $dashboardFilters['month'], 2, '0', STR_PAD_LEFT).'.xls';
    }

    /**
     * @param  array<string, mixed>  $summary
     * @param  array<int, array<string, mixed>>  $monthlySummary
     * @return array<int, array<int, array{type: string, value: mixed, style: string}>>
     */
    private function buildSummaryRows(int $year, string $periodLabel, string $scopeLabel, array $summary, array $monthlySummary): array
    {
        $rows = [
            [['type' => 'String', 'value' => 'Cashflow Projection Export', 'style' => 'section']],
            [
                ['type' => 'String', 'value' => 'Selected Period', 'style' => 'header'],
                ['type' => 'String', 'value' => $periodLabel],
            ],
            [
                ['type' => 'String', 'value' => 'Scope', 'style' => 'header'],
                ['type' => 'String', 'value' => $scopeLabel],
            ],
            [
                ['type' => 'String', 'value' => 'Business Unit', 'style' => 'header'],
                ['type' => 'String', 'value' => (string) session('current_business_unit_name', '')],
            ],
            [
                ['type' => 'String', 'value' => 'Exported At', 'style' => 'header'],
                ['type' => 'String', 'value' => now()->format('Y-m-d H:i:s')],
            ],
            [['type' => 'String', 'value' => '', 'style' => 'text']],
            [
                ['type' => 'String', 'value' => 'Balance Snapshot', 'style' => 'header'],
                ['type' => 'Number', 'value' => $summary['total_balance'], 'style' => 'number'],
            ],
            [
                ['type' => 'String', 'value' => 'Period Inflow', 'style' => 'header'],
                ['type' => 'Number', 'value' => $summary['inflow'], 'style' => 'number'],
            ],
            [
                ['type' => 'String', 'value' => 'Period Outflow', 'style' => 'header'],
                ['type' => 'Number', 'value' => $summary['outflow'], 'style' => 'number'],
            ],
            [
                ['type' => 'String', 'value' => 'Finance Income', 'style' => 'header'],
                ['type' => 'Number', 'value' => $summary['finance_income'], 'style' => 'number'],
            ],
            [
                ['type' => 'String', 'value' => 'Net Cashflow', 'style' => 'header'],
                ['type' => 'Number', 'value' => $summary['net_cashflow'], 'style' => 'number'],
            ],
            [['type' => 'String', 'value' => '', 'style' => 'text']],
            [
                ['type' => 'String', 'value' => 'Month', 'style' => 'header'],
                ['type' => 'String', 'value' => 'Inflow', 'style' => 'header'],
                ['type' => 'String', 'value' => 'Outflow', 'style' => 'header'],
                ['type' => 'String', 'value' => 'Finance Income', 'style' => 'header'],
                ['type' => 'String', 'value' => 'Opening Balance', 'style' => 'header'],
                ['type' => 'String', 'value' => 'Net', 'style' => 'header'],
                ['type' => 'String', 'value' => 'Closing Balance', 'style' => 'header'],
                ['type' => 'String', 'value' => 'Warning', 'style' => 'header'],
            ],
        ];

        foreach ($monthlySummary as $row) {
            $rows[] = [
                ['type' => 'String', 'value' => CarbonImmutable::create($year, (int) $row['month'], 1)->format('M Y'), 'style' => 'text'],
                ['type' => 'Number', 'value' => $row['plus'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['minus'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['finance_income'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['opening_balance'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['net'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['closing_balance'], 'style' => 'number'],
                ['type' => 'String', 'value' => $row['is_warning'] ? 'YES' : 'NO', 'style' => 'text'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $dailySummary
     * @return array<int, array<int, array{type: string, value: mixed, style: string}>>
     */
    private function buildDailyRows(array $dailySummary): array
    {
        $rows = [[
            ['type' => 'String', 'value' => 'Date', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Inflow', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Outflow', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Net', 'style' => 'header'],
        ]];

        foreach ($dailySummary as $row) {
            $rows[] = [
                ['type' => 'String', 'value' => $row['date'], 'style' => 'date'],
                ['type' => 'Number', 'value' => $row['plus'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['minus'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['net'], 'style' => 'number'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>|Collection<int, array<string, mixed>>  $rawEntries
     * @return array<int, array<int, array{type: string, value: mixed, style: string}>>
     */
    private function buildRawEntryRows($rawEntries): array
    {
        $rows = [[
            ['type' => 'String', 'value' => 'Transaction Date', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Due Date', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Business Unit', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Department', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Category', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Flow Type', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Description', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Notes', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Amount', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Estimated Date', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Created By', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Created Department', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Last Edited By', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Last Edited Department', 'style' => 'header'],
        ]];

        foreach ($rawEntries as $row) {
            $rows[] = [
                ['type' => 'String', 'value' => (string) $row['transaction_date'], 'style' => 'date'],
                ['type' => 'String', 'value' => (string) ($row['due_date'] ?? ''), 'style' => 'date'],
                ['type' => 'String', 'value' => (string) ($row['business_unit_code'] ?? ''), 'style' => 'text'],
                ['type' => 'String', 'value' => (string) ($row['department_name'] ?? ''), 'style' => 'text'],
                ['type' => 'String', 'value' => (string) $row['action_label'], 'style' => 'text'],
                ['type' => 'String', 'value' => $row['flow_type'] === 'in' ? 'Inflow' : 'Outflow', 'style' => 'text'],
                ['type' => 'String', 'value' => (string) $row['description'], 'style' => 'text'],
                ['type' => 'String', 'value' => (string) ($row['notes'] ?? ''), 'style' => 'text'],
                ['type' => 'Number', 'value' => $row['amount'], 'style' => 'number'],
                ['type' => 'String', 'value' => $row['is_estimated_date'] ? 'YES' : 'NO', 'style' => 'text'],
                ['type' => 'String', 'value' => (string) ($row['creator_name'] ?? ''), 'style' => 'text'],
                ['type' => 'String', 'value' => (string) ($row['creator_department_label'] ?? ''), 'style' => 'text'],
                ['type' => 'String', 'value' => (string) ($row['updater_name'] ?? ''), 'style' => 'text'],
                ['type' => 'String', 'value' => (string) ($row['updater_department_label'] ?? ''), 'style' => 'text'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>|Collection<int, array<string, mixed>>  $financeInputRows
     * @return array<int, array<int, array{type: string, value: mixed, style: string}>>
     */
    private function buildFinanceRows(int $year, $financeInputRows): array
    {
        $rows = [[
            ['type' => 'String', 'value' => 'Month', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Cash on Hand', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Receivable Estimate', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Upcoming Revenue Estimate', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Capital Injection Estimate', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Other Income', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Finance Income Total', 'style' => 'header'],
        ]];

        foreach ($financeInputRows as $row) {
            $rows[] = [
                ['type' => 'String', 'value' => CarbonImmutable::create($year, (int) $row['month'], 1)->format('M Y'), 'style' => 'text'],
                ['type' => 'Number', 'value' => $row['cash_on_hand'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['receivable_estimate'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['upcoming_event_revenue_estimate'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['capital_injection_estimate'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['other_income'], 'style' => 'number'],
                ['type' => 'Number', 'value' => (float) $row['receivable_estimate'] + (float) $row['upcoming_event_revenue_estimate'] + (float) $row['capital_injection_estimate'] + (float) $row['other_income'], 'style' => 'number'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, array<int, array<int, array{type: string, value: mixed, style: string}>>>  $worksheets
     */
    private function buildExportWorkbookXml(array $worksheets): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">';
        $xml .= '<Styles>';
        $xml .= '<Style ss:ID="header"><Font ss:Bold="1"/><Interior ss:Color="#E8EEF7" ss:Pattern="Solid"/></Style>';
        $xml .= '<Style ss:ID="section"><Font ss:Bold="1" ss:Size="14"/></Style>';
        $xml .= '<Style ss:ID="text"><Alignment ss:Vertical="Center"/></Style>';
        $xml .= '<Style ss:ID="date"><NumberFormat ss:Format="yyyy-mm-dd"/></Style>';
        $xml .= '<Style ss:ID="number"><NumberFormat ss:Format="#,##0"/></Style>';
        $xml .= '</Styles>';

        foreach ($worksheets as $name => $rows) {
            $xml .= '<Worksheet ss:Name="'.$this->escapeExportXml($name).'"><Table>';

            foreach ($rows as $row) {
                $xml .= '<Row>';

                foreach ($row as $cell) {
                    $styleId = (string) ($cell['style'] ?? 'text');
                    $style = $styleId !== '' ? ' ss:StyleID="'.$this->escapeExportXml($styleId).'"' : '';
                    $type = ($cell['type'] ?? 'String') === 'Number' ? 'Number' : 'String';
                    $value = $type === 'Number'
                        ? (string) ((float) ($cell['value'] ?? 0))
                        : $this->escapeExportXml((string) ($cell['value'] ?? ''));

                    $xml .= '<Cell'.$style.'><Data ss:Type="'.$type.'">'.$value.'</Data></Cell>';
                }

                $xml .= '</Row>';
            }

            $xml .= '</Table></Worksheet>';
        }

        $xml .= '</Workbook>';

        return $xml;
    }

    private function escapeExportXml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1);
    }
}
