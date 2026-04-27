<?php

namespace App\Http\Controllers\Modules\Ticket;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use App\Services\Modules\Ticket\TicketReportingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketReportingController extends Controller
{
    public function __construct(
        private TicketReportingService $reportingService,
    ) {}

    /**
     * Show the reporting page with charts and metrics.
     *
     * GET /it-support/reporting
     */
    public function index(Request $request): Response
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();

        $period = $request->get('period', 'this_month');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $reportData = $this->reportingService->getReportData(
            $scopedBuIds,
            $period,
            $dateFrom,
            $dateTo
        );

        return Inertia::render('Ticket/Reporting', [
            'reportData' => $reportData,
            'filters' => [
                'period' => $period,
                'date_from' => $dateFrom ?? $reportData['period']['from'],
                'date_to' => $dateTo ?? $reportData['period']['to'],
            ],
        ]);
    }

    /**
     * Export ticket report to Excel.
     *
     * GET /it-support/reporting/export/excel
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();

        $dateFrom = Carbon::parse($request->get('date_from', now()->startOfMonth()->format('Y-m-d')));
        $dateTo = Carbon::parse($request->get('date_to', now()->format('Y-m-d')));

        $data = $this->reportingService->prepareExportData($scopedBuIds, $dateFrom, $dateTo);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ticket Report');

        // Headers
        $headers = [
            'Ticket Number',
            'Title',
            'Status',
            'Priority',
            'Category',
            'Requester',
            'Assigned To',
            'Department',
            'Business Unit',
            'Created At',
            'Resolved At',
            'Processing Time',
            'SLA Breached',
        ];

        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        // Style header row
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE2E8F0'],
            ],
        ];
        $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

        // Data rows
        foreach ($data as $rowIndex => $row) {
            $rowNum = $rowIndex + 2;
            $colIndex = 1;

            foreach ($row as $value) {
                $sheet->setCellValueByColumnAndRow($colIndex, $rowNum, $value);
                $colIndex++;
            }
        }

        // Auto-size columns
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'ticket-report-'.$dateFrom->format('Y-m-d').'-to-'.$dateTo->format('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export ticket report to PDF.
     *
     * GET /it-support/reporting/export/pdf
     */
    public function exportPdf(Request $request): \Illuminate\Http\Response
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();

        $period = $request->get('period', 'this_month');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $reportData = $this->reportingService->getReportData(
            $scopedBuIds,
            $period,
            $dateFrom,
            $dateTo
        );

        $html = view('ticket.report-pdf', [
            'reportData' => $reportData,
        ])->render();

        try {
            $browsershot = \Spatie\Browsershot\Browsershot::html($html)
                ->format('A4')
                ->landscape()
                ->margins(10, 10, 10, 10)
                ->timeout(120)
                ->noSandbox();

            if ($remoteUrl = config('pdf.browsershot.remote_url')) {
                $parsed = parse_url($remoteUrl);
                $browsershot->setRemoteInstance($parsed['host'], $parsed['port'] ?? 9222);
            }

            $pdfContent = $browsershot->pdf();

            $filename = 'ticket-report-'.$reportData['period']['from'].'-to-'.$reportData['period']['to'].'.pdf';

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ticket report PDF generation failed: '.$e->getMessage());

            return response('PDF generation failed. Please try Excel export instead.', 500);
        }
    }

    /**
     * Resolve the active BU scope for IT Support admin.
     * Parent or holding BUs include all descendants for roll-up views.
     *
     * @return array<int>
     */
    private function resolveScopedBusinessUnitIds(): array
    {
        $currentBusinessUnitId = (int) session('current_business_unit_id');

        if ($currentBusinessUnitId <= 0) {
            return [];
        }

        $currentBusinessUnit = BusinessUnit::with('descendants')->find($currentBusinessUnitId);

        if (! $currentBusinessUnit) {
            return [$currentBusinessUnitId];
        }

        return $currentBusinessUnit->getAccessibleBusinessUnits();
    }
}
