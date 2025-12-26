<?php

namespace App\Livewire\Modules\Purchasing\Admin;

use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Models\Modules\Purchasing\Admin\AdminTaskItemRealization;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonalTaskHistory extends Component
{
    use WithPagination;

    public $activeBusinessUnitId;
    public $dateFrom = '';
    public $dateTo = '';
    public $statusFilter = 'all';
    public $typeFilter = 'all';

    // Detail modal properties
    public $showDetailModal = false;
    public $detailTaskId = null;
    public $detailItems = [];

    // Export loading state
    public $isExporting = false;

    public function mount(): void
    {
        $this->activeBusinessUnitId = session('current_business_unit_id');
        // No default date filter - show all data initially
    }

    #[On('business-unit-switched')]
    public function handleBusinessUnitSwitch($businessUnitId): void
    {
        $this->activeBusinessUnitId = $businessUnitId;
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedTypeFilter()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->statusFilter = 'all';
        $this->typeFilter = 'all';
        $this->resetPage();
    }

    /**
     * Open detail modal and load item realization records for a task
     * Requirements: 3.3, 3.4
     */
    public function openDetailModal($taskId): void
    {
        $this->detailTaskId = $taskId;
        
        // Load AdminTaskItemRealization records for this task
        $this->detailItems = AdminTaskItemRealization::forAdminTask($taskId)
            ->orderBy('id')
            ->get()
            ->toArray();
        
        $this->showDetailModal = true;
    }

    /**
     * Close detail modal and reset state
     * Requirements: 3.3
     */
    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->detailTaskId = null;
        $this->detailItems = [];
    }

    private function getTasks()
    {
        $query = AdminTask::with([
            'taskable',
            'businessUnit:id,name,code',
            'department:id,name',
        ])
        ->select([
            'id', 'taskable_type', 'taskable_id', 'business_unit_id', 'department_id',
            'assigned_admin_id', 'status', 'estimated_total_price', 'realized_total_price',
            'savings_amount', 'savings_percentage', 'followup_time_minutes', 
            'completion_time_minutes', 'entered_at', 'started_at', 'completed_at'
        ])
        ->where('assigned_admin_id', auth()->id())
        ->orderBy('entered_at', 'desc');

        // Date range filter
        if ($this->dateFrom) {
            $query->whereDate('entered_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('entered_at', '<=', $this->dateTo);
        }

        // Status filter
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        // Type filter
        if ($this->typeFilter !== 'all') {
            $query->where('taskable_type', $this->typeFilter);
        }

        return $query->paginate(10);
    }

    private function getStatistics()
    {
        // Use aggregate query instead of loading all records
        $query = AdminTask::where('assigned_admin_id', auth()->id())
            ->where('status', 'done');

        if ($this->dateFrom) {
            $query->whereDate('entered_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('entered_at', '<=', $this->dateTo);
        }

        return $query->selectRaw('
            COUNT(*) as total_completed,
            AVG(followup_time_minutes) as avg_followup_time,
            AVG(completion_time_minutes) as avg_completion_time,
            SUM(savings_amount) as total_savings,
            AVG(savings_percentage) as avg_savings_percentage
        ')->first()->toArray();
    }

    public function render()
    {
        return view('livewire.modules.purchasing.admin.personal-task-history', [
            'tasks' => $this->getTasks(),
            'statistics' => $this->getStatistics(),
        ]);
    }

    /**
     * Export task history to CSV
     */
    public function exportCsv(): StreamedResponse
    {
        $tasks = $this->getTasksForExport();
        $filename = 'task-history-' . auth()->user()->name . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($tasks) {
            $handle = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Header
            fputcsv($handle, [
                'Document Number',
                'Type',
                'Business Unit',
                'Department',
                'Status',
                'Entered At',
                'Started At',
                'Completed At',
                'Follow-up Time (min)',
                'Completion Time (min)',
                'Estimated Price',
                'Realized Price',
                'Savings Amount',
                'Savings %',
            ]);

            // CSV Data
            foreach ($tasks as $task) {
                $docNumber = $task->taskable->pr_number ?? $task->taskable->st_number ?? 'N/A';
                $type = str_contains($task->taskable_type, 'PurchaseRequest') ? 'PR' : 'ST';
                
                fputcsv($handle, [
                    $docNumber,
                    $type,
                    $task->businessUnit->name ?? 'N/A',
                    $task->department->name ?? 'N/A',
                    $this->formatStatus($task->status),
                    $task->entered_at?->format('Y-m-d H:i:s'),
                    $task->started_at?->format('Y-m-d H:i:s'),
                    $task->completed_at?->format('Y-m-d H:i:s'),
                    $task->followup_time_minutes,
                    $task->completion_time_minutes,
                    $task->estimated_total_price,
                    $task->realized_total_price,
                    $task->savings_amount,
                    $task->savings_percentage,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export task history to Excel (XLSX format using CSV with Excel-friendly formatting)
     */
    public function exportExcel(): StreamedResponse
    {
        $tasks = $this->getTasksForExport();
        $statistics = $this->getStatistics();
        $filename = 'task-history-' . auth()->user()->name . '-' . now()->format('Y-m-d') . '.xls';

        return response()->streamDownload(function () use ($tasks, $statistics) {
            // Generate HTML table that Excel can open
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
            echo '<head><meta charset="UTF-8"></head>';
            echo '<body>';
            
            // Summary Section
            echo '<table border="1">';
            echo '<tr><td colspan="4" style="font-weight:bold;font-size:14pt;">Task History Export - ' . auth()->user()->name . '</td></tr>';
            echo '<tr><td colspan="4">Generated: ' . now()->format('Y-m-d H:i:s') . '</td></tr>';
            echo '<tr><td colspan="4"></td></tr>';
            
            // Statistics
            echo '<tr style="background-color:#f0f0f0;font-weight:bold;">';
            echo '<td>Total Completed</td><td>Avg Follow-up Time</td><td>Total Savings</td><td>Avg Savings %</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>' . $statistics['total_completed'] . '</td>';
            echo '<td>' . round($statistics['avg_followup_time'] ?? 0, 1) . ' min</td>';
            echo '<td>Rp ' . number_format($statistics['total_savings'] ?? 0, 0, ',', '.') . '</td>';
            echo '<td>' . number_format($statistics['avg_savings_percentage'] ?? 0, 1) . '%</td>';
            echo '</tr>';
            echo '<tr><td colspan="4"></td></tr>';
            echo '</table>';
            
            // Data Table
            echo '<table border="1">';
            echo '<tr style="background-color:#4472C4;color:white;font-weight:bold;">';
            echo '<td>Document Number</td>';
            echo '<td>Type</td>';
            echo '<td>Business Unit</td>';
            echo '<td>Department</td>';
            echo '<td>Status</td>';
            echo '<td>Entered At</td>';
            echo '<td>Started At</td>';
            echo '<td>Completed At</td>';
            echo '<td>Follow-up Time</td>';
            echo '<td>Completion Time</td>';
            echo '<td>Estimated Price</td>';
            echo '<td>Realized Price</td>';
            echo '<td>Savings Amount</td>';
            echo '<td>Savings %</td>';
            echo '</tr>';

            foreach ($tasks as $index => $task) {
                $docNumber = $task->taskable->pr_number ?? $task->taskable->st_number ?? 'N/A';
                $type = str_contains($task->taskable_type, 'PurchaseRequest') ? 'PR' : 'ST';
                $bgColor = $index % 2 === 0 ? '#ffffff' : '#f8f9fa';
                
                echo '<tr style="background-color:' . $bgColor . ';">';
                echo '<td>' . htmlspecialchars($docNumber) . '</td>';
                echo '<td>' . $type . '</td>';
                echo '<td>' . htmlspecialchars($task->businessUnit->name ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($task->department->name ?? 'N/A') . '</td>';
                echo '<td>' . $this->formatStatus($task->status) . '</td>';
                echo '<td>' . ($task->entered_at?->format('Y-m-d H:i') ?? '-') . '</td>';
                echo '<td>' . ($task->started_at?->format('Y-m-d H:i') ?? '-') . '</td>';
                echo '<td>' . ($task->completed_at?->format('Y-m-d H:i') ?? '-') . '</td>';
                echo '<td>' . $this->formatTime($task->followup_time_minutes) . '</td>';
                echo '<td>' . $this->formatTime($task->completion_time_minutes) . '</td>';
                echo '<td style="text-align:right;">Rp ' . number_format($task->estimated_total_price ?? 0, 0, ',', '.') . '</td>';
                echo '<td style="text-align:right;">' . ($task->realized_total_price ? 'Rp ' . number_format($task->realized_total_price, 0, ',', '.') : '-') . '</td>';
                
                $savingsColor = ($task->savings_amount ?? 0) >= 0 ? '#28a745' : '#dc3545';
                echo '<td style="text-align:right;color:' . $savingsColor . ';">Rp ' . number_format($task->savings_amount ?? 0, 0, ',', '.') . '</td>';
                echo '<td style="text-align:right;color:' . $savingsColor . ';">' . number_format($task->savings_percentage ?? 0, 1) . '%</td>';
                echo '</tr>';
            }

            echo '</table>';
            echo '</body></html>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get all tasks for export (without pagination)
     */
    private function getTasksForExport()
    {
        $query = AdminTask::with([
            'taskable',
            'businessUnit:id,name,code',
            'department:id,name',
        ])
        ->where('assigned_admin_id', auth()->id())
        ->orderBy('entered_at', 'desc');

        // Apply same filters as getTasks()
        if ($this->dateFrom) {
            $query->whereDate('entered_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('entered_at', '<=', $this->dateTo);
        }
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }
        if ($this->typeFilter !== 'all') {
            $query->where('taskable_type', $this->typeFilter);
        }

        return $query->get();
    }

    /**
     * Format status for export
     */
    private function formatStatus(string $status): string
    {
        return match ($status) {
            'pending_followup' => 'Pending',
            'in_progress' => 'In Progress',
            'done' => 'Completed',
            default => ucfirst($status),
        };
    }

    /**
     * Format time in minutes to human readable
     */
    private function formatTime(?float $minutes): string
    {
        if ($minutes === null) {
            return '-';
        }
        
        if ($minutes >= 60) {
            return round($minutes / 60, 1) . ' hrs';
        } elseif ($minutes >= 1) {
            return round($minutes) . ' min';
        } else {
            return max(1, round($minutes * 60)) . ' sec';
        }
    }
}
