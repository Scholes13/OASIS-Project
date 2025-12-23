<?php

namespace App\Livewire\Modules\Purchasing\Admin;

use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Models\Modules\Purchasing\Admin\AdminTaskItemRealization;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

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
}
