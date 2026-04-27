<?php

namespace App\Exports\Modules\Ticket;

use App\Models\Modules\Ticket\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketExport
{
    protected Collection $tickets;

    /**
     * @param  array<int>  $buIds
     */
    public function __construct(
        protected array $buIds,
        protected Carbon $from,
        protected Carbon $to,
    ) {
        $this->tickets = $this->fetchTickets();
    }

    /**
     * Generate and stream the Excel file.
     */
    public function download(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;

        $this->buildSummarySheet($spreadsheet);
        $this->buildTicketsSheet($spreadsheet);

        $filename = 'ticket-report-'.$this->from->format('Y-m-d').'-to-'.$this->to->format('Y-m-d').'.xlsx';

        return new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Fetch tickets for the given scope and date range.
     */
    protected function fetchTickets(): Collection
    {
        return Ticket::forBusinessUnits($this->buIds)
            ->whereBetween('created_at', [$this->from->copy()->startOfDay(), $this->to->copy()->endOfDay()])
            ->with(['requester', 'assignedUser', 'category', 'department'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // ==================== Sheet 1: Summary ====================

    protected function buildSummarySheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Summary');

        // Title
        $sheet->setCellValue('A1', 'IT Support Report - Summary');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Date range
        $sheet->setCellValue('A3', 'Date Range:');
        $sheet->setCellValue('B3', $this->from->format('Y-m-d').' to '.$this->to->format('Y-m-d'));
        $sheet->setCellValue('A4', 'Generated:');
        $sheet->setCellValue('B4', now()->format('Y-m-d H:i:s'));

        // Total tickets
        $sheet->setCellValue('A6', 'Total Tickets:');
        $sheet->setCellValue('B6', $this->tickets->count());
        $sheet->getStyle('A6:B6')->getFont()->setBold(true);

        // By Status header
        $sheet->setCellValue('A8', 'By Status');
        $sheet->getStyle('A8')->getFont()->setBold(true)->setSize(12);

        $statusRows = [
            ['Waiting', $this->tickets->where('status', 'waiting')->count()],
            ['In Progress', $this->tickets->where('status', 'in_progress')->count()],
            ['Done', $this->tickets->where('status', 'done')->count()],
            ['Cancelled', $this->tickets->where('status', 'cancelled')->count()],
        ];

        $this->writeHeaderRow($sheet, ['Status', 'Count'], 'A9:B9', 9);

        $row = 10;
        foreach ($statusRows as [$label, $count]) {
            $sheet->setCellValue('A'.$row, $label);
            $sheet->setCellValue('B'.$row, $count);

            $statusColor = $this->statusColor(strtolower(str_replace(' ', '_', $label)));
            $sheet->getStyle('A'.$row)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $statusColor]],
            ]);
            $row++;
        }

        if ($row > 10) {
            $this->applyDataBorders($sheet, 'A10:B'.($row - 1));
        }

        // By Priority header
        $priorityStartRow = $row + 1;
        $sheet->setCellValue('A'.$priorityStartRow, 'By Priority');
        $sheet->getStyle('A'.$priorityStartRow)->getFont()->setBold(true)->setSize(12);

        $headerRow = $priorityStartRow + 1;
        $this->writeHeaderRow($sheet, ['Priority', 'Count'], 'A'.$headerRow.':B'.$headerRow, $headerRow);

        $priorityRows = [
            ['Low', $this->tickets->where('priority', 'low')->count()],
            ['Medium', $this->tickets->where('priority', 'medium')->count()],
            ['High', $this->tickets->where('priority', 'high')->count()],
            ['Critical', $this->tickets->where('priority', 'critical')->count()],
        ];

        $row = $headerRow + 1;
        foreach ($priorityRows as [$label, $count]) {
            $sheet->setCellValue('A'.$row, $label);
            $sheet->setCellValue('B'.$row, $count);

            $priorityColor = $this->priorityColor(strtolower($label));
            $sheet->getStyle('A'.$row)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $priorityColor]],
            ]);
            $row++;
        }

        if ($row > $headerRow + 1) {
            $this->applyDataBorders($sheet, 'A'.($headerRow + 1).':B'.($row - 1));
        }

        // Average resolution time
        $avgRow = $row + 1;
        $sheet->setCellValue('A'.$avgRow, 'Average Resolution Time:');
        $sheet->setCellValue('B'.$avgRow, $this->calculateAvgResolutionTime().' hours');
        $sheet->getStyle('A'.$avgRow.':B'.$avgRow)->getFont()->setBold(true);

        $this->autoSizeColumns($sheet, 'A', 'B');
    }

    // ==================== Sheet 2: Tickets ====================

    protected function buildTicketsSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Tickets');

        $headers = [
            'No',
            'Ticket Number',
            'Title',
            'Requester',
            'Department',
            'Category',
            'Priority',
            'Status',
            'Assigned To',
            'Created At',
            'Resolved At',
            'Resolution Time',
        ];

        $this->writeHeaderRow($sheet, $headers, 'A1:L1', 1);

        $row = 2;
        $no = 1;
        foreach ($this->tickets as $ticket) {
            $resolutionTime = $this->formatResolutionTime($ticket);

            $sheet->fromArray([
                $no,
                $ticket->ticket_number,
                $ticket->title,
                $ticket->requester?->name ?? '-',
                $ticket->department?->name ?? '-',
                $ticket->category?->name ?? 'Uncategorized',
                ucfirst((string) $ticket->priority),
                ucfirst(str_replace('_', ' ', (string) $ticket->status)),
                $ticket->assignedUser?->name ?? 'Unassigned',
                $ticket->created_at?->format('Y-m-d H:i:s'),
                $ticket->resolved_at?->format('Y-m-d H:i:s') ?? '-',
                $resolutionTime,
            ], null, 'A'.$row);

            // Color-code status column (H)
            $statusColor = $this->statusColor((string) $ticket->status);
            $sheet->getStyle('H'.$row)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $statusColor]],
            ]);

            // Color-code priority column (G)
            $priorityColor = $this->priorityColor((string) $ticket->priority);
            $sheet->getStyle('G'.$row)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $priorityColor]],
            ]);

            $row++;
            $no++;
        }

        $this->autoSizeColumns($sheet, 'A', 'L');

        if ($row > 2) {
            $this->applyDataBorders($sheet, 'A2:L'.($row - 1));
        }
    }

    // ==================== Helpers ====================

    /**
     * Calculate average resolution time in hours for resolved tickets.
     */
    protected function calculateAvgResolutionTime(): string
    {
        $resolved = $this->tickets->filter(fn (Ticket $t): bool => $t->resolved_at !== null);

        if ($resolved->isEmpty()) {
            return '0.00';
        }

        $totalHours = $resolved->sum(function (Ticket $ticket): float {
            return $ticket->created_at->diffInMinutes($ticket->resolved_at) / 60;
        });

        return number_format($totalHours / $resolved->count(), 2);
    }

    /**
     * Format resolution time for a single ticket.
     */
    protected function formatResolutionTime(Ticket $ticket): string
    {
        if (! $ticket->created_at || ! $ticket->resolved_at) {
            return '-';
        }

        return $ticket->processing_time ?? '-';
    }

    /**
     * @param  array<int, string>  $headers
     */
    protected function writeHeaderRow(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $headers,
        string $range,
        int $rowNumber = 1,
    ): void {
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column.$rowNumber, $header);
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

    protected function autoSizeColumns(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        string $start,
        string $end,
    ): void {
        foreach (range($start, $end) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    protected function applyDataBorders(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        string $range,
    ): void {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);
    }

    protected function statusColor(string $status): string
    {
        return match ($status) {
            'waiting' => 'FEF3C7',
            'in_progress' => 'DBEAFE',
            'done' => 'D1FAE5',
            'cancelled' => 'FEE2E2',
            default => 'F3F4F6',
        };
    }

    protected function priorityColor(string $priority): string
    {
        return match ($priority) {
            'low' => 'D1FAE5',
            'medium' => 'FEF3C7',
            'high' => 'FED7AA',
            'critical' => 'FEE2E2',
            default => 'F3F4F6',
        };
    }
}
