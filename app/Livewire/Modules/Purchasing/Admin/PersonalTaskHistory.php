<?php

namespace App\Livewire\Modules\Purchasing\Admin;

use App\Livewire\Traits\HasLazyLoading;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Models\Modules\Purchasing\Admin\AdminTaskItemRealization;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class PersonalTaskHistory extends Component
{
    use HasLazyLoading, WithPagination;

    public $activeBusinessUnitId;
    public $dateFrom;
    public $dateTo;
    public $statusFilter = 'all';
    public $typeFilter = 'all';

    // Detail modal properties
    public $showDetailModal = false;
    public $detailTaskId = null;
    public $detailItems = [];

    public function mount(): void
    {
        $this->activeBusinessUnitId = session('current_business_unit_id');
        
        // Default to last 30 days
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
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
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
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
            'businessUnit',
            'department',
            'assignedAdmin',
            'itemRealizations'
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

        return $query->paginate(20);
    }

    private function getStatistics()
    {
        $tasks = AdminTask::where('assigned_admin_id', auth()->id())
            ->where('status', 'done');

        if ($this->dateFrom) {
            $tasks->whereDate('entered_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $tasks->whereDate('entered_at', '<=', $this->dateTo);
        }

        $completedTasks = $tasks->get();

        return [
            'total_completed' => $completedTasks->count(),
            'avg_followup_time' => $completedTasks->avg('followup_time_minutes'),
            'avg_completion_time' => $completedTasks->avg('completion_time_minutes'),
            'total_savings' => $completedTasks->sum('savings_amount'),
            'avg_savings_percentage' => $completedTasks->avg('savings_percentage'),
        ];
    }

    public function render()
    {
        if (!$this->readyToLoad) {
            return view('livewire.modules.purchasing.admin.personal-task-history', [
                'tasks' => new LengthAwarePaginator([], 0, 20),
                'statistics' => [
                    'total_completed' => 0,
                    'avg_followup_time' => 0,
                    'avg_completion_time' => 0,
                    'total_savings' => 0,
                    'avg_savings_percentage' => 0,
                ],
            ]);
        }

        return view('livewire.modules.purchasing.admin.personal-task-history', [
            'tasks' => $this->getTasks(),
            'statistics' => $this->getStatistics(),
        ]);
    }
}
