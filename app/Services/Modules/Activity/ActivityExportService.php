<?php

namespace App\Services\Modules\Activity;

use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityExportService
{
    public function __construct(
        protected ActivityReportAggregationService $aggregationService
    ) {}

    /**
     * Export activities to XLSX
     */
    public function exportToXlsx(
        int $businessUnitId,
        ?int $departmentId = null,
        ?int $userId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $status = null,
        ?int $activityTypeId = null
    ): StreamedResponse {
        $tasks = $this->getFilteredTasks(
            $businessUnitId,
            $departmentId,
            $userId,
            $dateFrom,
            $dateTo,
            $status,
            $activityTypeId
        );

        $spreadsheet = new Spreadsheet;

        $this->buildDetailSheet($spreadsheet, $tasks);
        $this->buildSummarySheet($spreadsheet, $tasks);
        $this->buildCategoryBreakdownSheet($spreadsheet, $tasks);
        $this->buildRawDataSheet($spreadsheet, $tasks);

        $filename = 'activity_report_'.now()->format('Y-m-d_His').'.xlsx';

        return new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    protected function getFilteredTasks(
        int $businessUnitId,
        ?int $departmentId,
        ?int $userId,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $status,
        ?int $activityTypeId
    ): Collection {
        return EmployeeTask::query()
            ->where('business_unit_id', $businessUnitId)
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->when($userId, fn ($query) => $query->whereHas('participants', fn ($participantQuery) => $participantQuery->where('user_id', $userId)))
            ->when($dateFrom, fn ($query) => $query->whereDate('task_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('task_date', '<=', $dateTo))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($activityTypeId, fn ($query) => $query->where('activity_type_id', $activityTypeId))
            ->with(['activityType', 'subActivity', 'creator', 'department'])
            ->orderBy('task_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    protected function buildDetailSheet(Spreadsheet $spreadsheet, Collection $tasks): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail');

        $headers = [
            'No',
            'Tanggal',
            'Judul Aktivitas',
            'Deskripsi',
            'Ringkasan Aktivitas',
            'Kategori',
            'Sub Kategori',
            'Status',
            'Prioritas',
            'Pembuat',
            'Departemen',
            'Jatuh Tempo',
            'Mulai',
            'Selesai',
            'Durasi (menit)',
            'Catatan',
        ];

        $this->writeHeaderRow($sheet, $headers, 'A1:P1');

        $row = 2;
        $no = 1;
        foreach ($tasks as $task) {
            $sheet->fromArray([
                $no,
                $task->task_date?->format('Y-m-d') ?? '-',
                $task->task_title ?: 'Aktivitas tanpa judul',
                $task->task_description ?? '-',
                $this->aggregationService->buildTaskSummary($task),
                $this->aggregationService->categoryName($task),
                $this->aggregationService->subCategoryName($task) ?? '-',
                $this->aggregationService->statusLabel((string) $task->status),
                ucfirst((string) ($task->priority ?? 'medium')),
                $task->creator?->name ?? '-',
                $task->department?->name ?? '-',
                $task->due_date?->format('Y-m-d') ?? '-',
                $task->started_at?->format('Y-m-d H:i') ?? '-',
                $task->completed_at?->format('Y-m-d H:i') ?? '-',
                $task->duration_minutes ?? '-',
                $task->notes ?? '-',
            ], null, 'A'.$row);

            $sheet->getStyle('H'.$row)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $this->statusColor((string) $task->status)],
                ],
            ]);

            $row++;
            $no++;
        }

        $this->autoSizeColumns($sheet, 'A', 'P');
        if ($row > 2) {
            $this->applyDataBorders($sheet, 'A2:P'.($row - 1));
        }
    }

    protected function buildSummarySheet(Spreadsheet $spreadsheet, Collection $tasks): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Ringkasan');

        $summary = $this->aggregationService->buildExportSummary($tasks);

        $sheet->setCellValue('A1', 'Ringkasan Laporan Aktivitas');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $rows = [
            ['Generated:', $summary['generated_at'], false],
            ['Total Activities:', $summary['total_activities'], true],
            ['Completed ('.$summary['status_rows'][0]['count'].')', $this->formatPercentage($summary['status_rows'][0]['percentage']), false],
            ['In Progress ('.$summary['status_rows'][1]['count'].')', $this->formatPercentage($summary['status_rows'][1]['percentage']), false],
            ['Planned ('.$summary['status_rows'][2]['count'].')', $this->formatPercentage($summary['status_rows'][2]['percentage']), false],
            ['Cancelled ('.$summary['status_rows'][3]['count'].')', $this->formatPercentage($summary['status_rows'][3]['percentage']), false],
            ['Completion Rate:', $this->formatPercentage($summary['completion_rate']), false],
            ['Top Category:', $summary['top_category']['name'].' ('.$summary['top_category']['count'].')', false],
            ['Top Subcategory:', $summary['top_subcategory']['name'].' ('.$summary['top_subcategory']['count'].')', false],
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

        $this->autoSizeColumns($sheet, 'A', 'B');
    }

    protected function buildCategoryBreakdownSheet(Spreadsheet $spreadsheet, Collection $tasks): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Breakdown Kategori');

        $headers = ['Category', 'Subcategory', 'Count', '% of Category', '% of Report'];
        $this->writeHeaderRow($sheet, $headers, 'A1:E1');

        $row = 2;
        foreach ($this->aggregationService->buildCategoryBreakdown($tasks) as $item) {
            $sheet->setCellValue('A'.$row, $item['category']);
            $sheet->setCellValue('B'.$row, $item['subcategory']);
            $sheet->setCellValueExplicit('C'.$row, (int) $item['count'], DataType::TYPE_NUMERIC);
            $sheet->setCellValue('D'.$row, $this->formatPercentage($item['percentage_of_category']));
            $sheet->setCellValue('E'.$row, $this->formatPercentage($item['percentage_of_report']));
            $row++;
        }

        $this->autoSizeColumns($sheet, 'A', 'E');
        if ($row > 2) {
            $this->applyDataBorders($sheet, 'A2:E'.($row - 1));
        }
    }

    protected function buildRawDataSheet(Spreadsheet $spreadsheet, Collection $tasks): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Data Mentah');

        $headers = [
            'id_tugas',
            'tanggal_tugas',
            'judul_aktivitas',
            'deskripsi_aktivitas',
            'ringkasan_aktivitas',
            'kategori',
            'sub_kategori',
            'status',
            'prioritas',
            'nama_pembuat',
            'nama_departemen',
            'jatuh_tempo',
            'waktu_mulai',
            'waktu_selesai',
            'durasi_menit',
            'catatan',
        ];

        $this->writeHeaderRow($sheet, $headers, 'A1:P1');

        $row = 2;
        foreach ($tasks as $task) {
            $sheet->fromArray([
                $task->id,
                $task->task_date?->format('Y-m-d') ?? '',
                $task->task_title ?: 'Aktivitas tanpa judul',
                $task->task_description ?? '',
                $this->aggregationService->buildTaskSummary($task),
                $this->aggregationService->categoryName($task),
                $this->aggregationService->subCategoryName($task) ?? '',
                (string) $task->status,
                (string) ($task->priority ?? ''),
                $task->creator?->name ?? '',
                $task->department?->name ?? '',
                $task->due_date?->format('Y-m-d') ?? '',
                $task->started_at?->format('Y-m-d H:i') ?? '',
                $task->completed_at?->format('Y-m-d H:i') ?? '',
                $task->duration_minutes ?? '',
                $task->notes ?? '',
            ], null, 'A'.$row);
            $row++;
        }

        $this->autoSizeColumns($sheet, 'A', 'P');
        if ($row > 2) {
            $this->applyDataBorders($sheet, 'A2:P'.($row - 1));
        }
    }

    /**
     * @param  array<int, string>  $headers
     */
    protected function writeHeaderRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $headers, string $range): void
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

    protected function autoSizeColumns(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $start, string $end): void
    {
        foreach (range($start, $end) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    protected function applyDataBorders(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);
    }

    protected function statusColor(string $status): string
    {
        return match ($status) {
            'planned' => 'E5E7EB',
            'in_progress' => 'DBEAFE',
            'completed' => 'D1FAE5',
            'cancelled' => 'FEE2E2',
            default => 'FFFFFF',
        };
    }

    protected function formatPercentage(float $value): string
    {
        return number_format($value, 1).'%';
    }
}

