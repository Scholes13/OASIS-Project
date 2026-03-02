<?php

namespace App\Services\Modules\Activity;

use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityExportService
{
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
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Activity Report');

        // Set headers
        $headers = [
            'No',
            'Tanggal',
            'Judul Aktivitas',
            'Tipe Aktivitas',
            'Sub Aktivitas',
            'Status',
            'Prioritas',
            'Pembuat',
            'Department',
            'Due Date',
            'Mulai',
            'Selesai',
            'Durasi (menit)',
            'Catatan',
        ];

        // Write headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.'1', $header);
            $col++;
        }

        // Style headers
        $headerRange = 'A1:N1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2596BE'], // Brand Royal Blue
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);

        // Write data
        $row = 2;
        $no = 1;
        foreach ($tasks as $task) {
            $sheet->setCellValue('A'.$row, $no);
            $sheet->setCellValue('B'.$row, $task->task_date?->format('Y-m-d'));
            $sheet->setCellValue('C'.$row, $task->task_title);
            $sheet->setCellValue('D'.$row, $task->activityType?->name ?? '-');
            $sheet->setCellValue('E'.$row, $task->subActivity?->name ?? '-');
            $sheet->setCellValue('F'.$row, $this->getStatusLabel($task->status));
            $sheet->setCellValue('G'.$row, ucfirst($task->priority ?? 'medium'));
            $sheet->setCellValue('H'.$row, $task->creator?->name ?? '-');
            $sheet->setCellValue('I'.$row, $task->department?->name ?? '-');
            $sheet->setCellValue('J'.$row, $task->due_date?->format('Y-m-d'));
            $sheet->setCellValue('K'.$row, $task->started_at?->format('Y-m-d H:i'));
            $sheet->setCellValue('L'.$row, $task->completed_at?->format('Y-m-d H:i'));
            $sheet->setCellValue('M'.$row, $task->duration_minutes ?? '-');
            $sheet->setCellValue('N'.$row, $task->notes ?? '-');

            // Apply status color
            $statusColor = $this->getStatusColor($task->status);
            $sheet->getStyle('F'.$row)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $statusColor],
                ],
            ]);

            $row++;
            $no++;
        }

        // Auto-size columns
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add borders to data
        if ($row > 2) {
            $dataRange = 'A2:N'.($row - 1);
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
            ]);
        }

        // Add summary sheet
        $this->addSummarySheet($spreadsheet, $tasks);

        // Generate filename
        $filename = 'activity_report_'.now()->format('Y-m-d_His').'.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Get filtered tasks for export
     */
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
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($userId, fn ($q) => $q->whereHas('participants', fn ($q) => $q->where('user_id', $userId)))
            ->when($dateFrom, fn ($q) => $q->whereDate('task_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('task_date', '<=', $dateTo))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($activityTypeId, fn ($q) => $q->where('activity_type_id', $activityTypeId))
            ->with(['activityType', 'subActivity', 'creator', 'department'])
            ->orderBy('task_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Add summary sheet to spreadsheet
     */
    protected function addSummarySheet(Spreadsheet $spreadsheet, Collection $tasks): void
    {
        $summarySheet = $spreadsheet->createSheet();
        $summarySheet->setTitle('Summary');

        // Title
        $summarySheet->setCellValue('A1', 'Activity Report Summary');
        $summarySheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Date range
        $summarySheet->setCellValue('A3', 'Generated:');
        $summarySheet->setCellValue('B3', now()->format('Y-m-d H:i:s'));

        // Stats
        $summarySheet->setCellValue('A5', 'Total Activities:');
        $summarySheet->setCellValue('B5', $tasks->count());

        $summarySheet->setCellValue('A6', 'Completed:');
        $summarySheet->setCellValue('B6', $tasks->where('status', 'completed')->count());

        $summarySheet->setCellValue('A7', 'In Progress:');
        $summarySheet->setCellValue('B7', $tasks->where('status', 'in_progress')->count());

        $summarySheet->setCellValue('A8', 'Planned:');
        $summarySheet->setCellValue('B8', $tasks->where('status', 'planned')->count());

        $summarySheet->setCellValue('A9', 'Cancelled:');
        $summarySheet->setCellValue('B9', $tasks->where('status', 'cancelled')->count());

        // By Activity Type
        $summarySheet->setCellValue('A11', 'By Activity Type:');
        $summarySheet->getStyle('A11')->getFont()->setBold(true);

        $byType = $tasks->groupBy(fn ($t) => $t->activityType?->name ?? 'Unknown');
        $row = 12;
        foreach ($byType as $typeName => $typeTasks) {
            $summarySheet->setCellValue('A'.$row, $typeName);
            $summarySheet->setCellValue('B'.$row, $typeTasks->count());
            $row++;
        }

        // Auto-size columns
        $summarySheet->getColumnDimension('A')->setAutoSize(true);
        $summarySheet->getColumnDimension('B')->setAutoSize(true);
    }

    /**
     * Get status label
     */
    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            'planned' => 'Planned',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucfirst($status),
        };
    }

    /**
     * Get status color (hex without #)
     */
    protected function getStatusColor(string $status): string
    {
        return match ($status) {
            'planned' => 'E5E7EB',      // Gray-200
            'in_progress' => 'DBEAFE',  // Blue-100
            'completed' => 'D1FAE5',    // Green-100
            'cancelled' => 'FEE2E2',    // Red-100
            default => 'FFFFFF',
        };
    }
}
