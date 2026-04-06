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

class ActivityAdminExportService
{
    public function exportToXlsx(
        array $businessUnitIds,
        ?int $departmentId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $status = null,
        ?int $activityTypeId = null,
    ): StreamedResponse {
        $tasks = EmployeeTask::query()
            ->whereIn('business_unit_id', $businessUnitIds)
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($dateFrom, fn ($q) => $q->whereDate('task_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('task_date', '<=', $dateTo))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($activityTypeId, fn ($q) => $q->where('activity_type_id', $activityTypeId))
            ->with(['activityType', 'subActivity', 'creator', 'department'])
            ->orderBy('department_id')
            ->orderBy('task_date', 'desc')
            ->get();

        $spreadsheet = new Spreadsheet;
        $this->buildDetailSheet($spreadsheet, $tasks);
        $this->buildDepartmentSummarySheet($spreadsheet, $tasks);
        $this->buildSummarySheet($spreadsheet, $tasks, $dateFrom, $dateTo);

        $buCode = session('current_business_unit_code', 'BU');
        $filename = "activity_admin_report_{$buCode}_".now()->format('Y-m-d_His').'.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    protected function buildDetailSheet(Spreadsheet $spreadsheet, Collection $tasks): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail');

        $headers = ['No', 'Department', 'Tanggal', 'Judul', 'Tipe Aktivitas', 'Sub Aktivitas', 'Status', 'Prioritas', 'Pembuat', 'Due Date', 'Mulai', 'Selesai', 'Durasi (menit)'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.'1', $header);
            $col++;
        }

        $lastCol = chr(ord('A') + count($headers) - 1);
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2596BE']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $row = 2;
        $no = 1;
        foreach ($tasks as $task) {
            $sheet->setCellValue('A'.$row, $no);
            $sheet->setCellValue('B'.$row, $task->department?->name ?? '-');
            $sheet->setCellValue('C'.$row, $task->task_date?->format('Y-m-d'));
            $sheet->setCellValue('D'.$row, $task->task_title);
            $sheet->setCellValue('E'.$row, $task->activityType?->name ?? '-');
            $sheet->setCellValue('F'.$row, $task->subActivity?->name ?? '-');
            $sheet->setCellValue('G'.$row, ucfirst(str_replace('_', ' ', $task->status)));
            $sheet->setCellValue('H'.$row, ucfirst($task->priority ?? 'medium'));
            $sheet->setCellValue('I'.$row, $task->creator?->name ?? '-');
            $sheet->setCellValue('J'.$row, $task->due_date?->format('Y-m-d'));
            $sheet->setCellValue('K'.$row, $task->started_at?->format('Y-m-d H:i'));
            $sheet->setCellValue('L'.$row, $task->completed_at?->format('Y-m-d H:i'));
            $sheet->setCellValue('M'.$row, $task->duration_minutes ?? '-');

            $statusColor = match ($task->status) {
                'completed' => 'D1FAE5', 'in_progress' => 'DBEAFE',
                'cancelled' => 'FEE2E2', default => 'E5E7EB',
            };
            $sheet->getStyle('G'.$row)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $statusColor]],
            ]);

            $row++;
            $no++;
        }

        foreach (range('A', $lastCol) as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        if ($row > 2) {
            $sheet->getStyle("A2:{$lastCol}".($row - 1))->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
        }
    }

    protected function buildDepartmentSummarySheet(Spreadsheet $spreadsheet, Collection $tasks): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Per Department');

        $headers = ['Department', 'Total', 'Completed', 'In Progress', 'Planned', 'Cancelled', 'Completion Rate'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.'1', $header);
            $col++;
        }
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2596BE']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $byDept = $tasks->groupBy(fn ($t) => $t->department?->name ?? 'Unknown');
        $row = 2;
        foreach ($byDept as $deptName => $deptTasks) {
            $total = $deptTasks->count();
            $completed = $deptTasks->where('status', 'completed')->count();
            $sheet->setCellValue('A'.$row, $deptName);
            $sheet->setCellValue('B'.$row, $total);
            $sheet->setCellValue('C'.$row, $completed);
            $sheet->setCellValue('D'.$row, $deptTasks->where('status', 'in_progress')->count());
            $sheet->setCellValue('E'.$row, $deptTasks->where('status', 'planned')->count());
            $sheet->setCellValue('F'.$row, $deptTasks->where('status', 'cancelled')->count());
            $sheet->setCellValue('G'.$row, $total > 0 ? round(($completed / $total) * 100, 1).'%' : '0%');
            $row++;
        }

        foreach (range('A', 'G') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }
    }

    protected function buildSummarySheet(Spreadsheet $spreadsheet, Collection $tasks, ?string $dateFrom, ?string $dateTo): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Summary');

        $sheet->setCellValue('A1', 'Activity Admin Report Summary');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A3', 'Generated:');
        $sheet->setCellValue('B3', now()->format('Y-m-d H:i:s'));
        $sheet->setCellValue('A4', 'Period:');
        $sheet->setCellValue('B4', ($dateFrom ?? '-').' to '.($dateTo ?? '-'));
        $sheet->setCellValue('A5', 'Business Unit:');
        $sheet->setCellValue('B5', session('current_business_unit_name', '-'));

        $sheet->setCellValue('A7', 'Total Activities:');
        $sheet->setCellValue('B7', $tasks->count());
        $sheet->setCellValue('A8', 'Completed:');
        $sheet->setCellValue('B8', $tasks->where('status', 'completed')->count());
        $sheet->setCellValue('A9', 'In Progress:');
        $sheet->setCellValue('B9', $tasks->where('status', 'in_progress')->count());
        $sheet->setCellValue('A10', 'Planned:');
        $sheet->setCellValue('B10', $tasks->where('status', 'planned')->count());
        $sheet->setCellValue('A11', 'Cancelled:');
        $sheet->setCellValue('B11', $tasks->where('status', 'cancelled')->count());

        $total = $tasks->count();
        $completed = $tasks->where('status', 'completed')->count();
        $sheet->setCellValue('A12', 'Completion Rate:');
        $sheet->setCellValue('B12', $total > 0 ? round(($completed / $total) * 100, 1).'%' : '0%');

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }
}
