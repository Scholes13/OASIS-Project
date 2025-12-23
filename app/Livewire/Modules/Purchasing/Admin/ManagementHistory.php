<?php

namespace App\Livewire\Modules\Purchasing\Admin;

use App\Livewire\Traits\HasLazyLoading;
use App\Models\Core\BusinessUnit;
use App\Models\Core\User;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Models\Modules\Purchasing\Admin\AdminTaskItemRealization;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

/**
 * Management History - View all admin task history for top management
 * Shows aggregated data from all purchasing admins in current BU (and children if parent BU)
 */
class ManagementHistory extends Component
{
    use WithPagination, HasLazyLoading;

    public $activeBusinessUnitId;
    
    // Filters
    public $dateFrom = '';
    public $dateTo = '';
    public $statusFilter = 'all';
    public $typeFilter = 'all';
    public $adminFilter = 'all';

    // Detail modal properties
    public $showDetailModal = false;
    public $detailTaskId = null;
    public $detailItems = [];

    public function mount(): void
    {
        $this->activeBusinessUnitId = session('current_business_unit_id');
    }
    
    public function hydrate(): void
    {
        // Always sync with session on each request
        $sessionBuId = session('current_business_unit_id');
        if ($this->activeBusinessUnitId != $sessionBuId) {
            $this->activeBusinessUnitId = $sessionBuId;
        }
    }

    /**
     * Get filtered business unit IDs (computed on demand)
     * If current BU is a parent, include all child BUs
     */
    protected function getFilteredBusinessUnitIds(): array
    {
        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        
        if (!$buId) {
            return [];
        }
        
        $businessUnit = BusinessUnit::with('children')->find($buId);
        
        if (!$businessUnit) {
            return [$buId];
        }
        
        $ids = [$businessUnit->id];
        
        // If this is a parent business unit, include all children
        if ($businessUnit->children && $businessUnit->children->isNotEmpty()) {
            $ids = array_merge($ids, $businessUnit->children->pluck('id')->toArray());
        }
        
        return $ids;
    }

    #[On('business-unit-switched')]
    public function handleBusinessUnitSwitch($businessUnitId): void
    {
        // Update session FIRST (single source of truth)
        session(['current_business_unit_id' => $businessUnitId]);
        
        $this->activeBusinessUnitId = $businessUnitId;
        $this->adminFilter = 'all'; // Reset admin filter when BU changes
        $this->resetPage();
        
        // Reload data using lazy loading pattern
        $this->resetLazyLoad();
        
        $this->dispatch('bu-switch-acknowledge', component: 'management-history');
        
        $buName = session('current_business_unit_name', 'new business unit');
        $this->dispatch('toast', type: 'success', message: "Switched to {$buName}");
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

    public function updatedAdminFilter()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->statusFilter = 'all';
        $this->typeFilter = 'all';
        $this->adminFilter = 'all';
        $this->resetPage();
    }

    /**
     * Open detail modal and load item realization records for a task
     */
    public function openDetailModal($taskId): void
    {
        $this->detailTaskId = $taskId;
        
        $this->detailItems = AdminTaskItemRealization::forAdminTask($taskId)
            ->orderBy('id')
            ->get()
            ->toArray();
        
        $this->showDetailModal = true;
    }

    /**
     * Close detail modal and reset state
     */
    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->detailTaskId = null;
        $this->detailItems = [];
    }

    /**
     * Get list of admins who have completed tasks in filtered BUs
     */
    public function getAdminList()
    {
        $buIds = $this->getFilteredBusinessUnitIds();
        
        if (empty($buIds)) {
            return collect();
        }

        // Get unique admin IDs from admin_tasks table
        $adminIds = AdminTask::whereIn('business_unit_id', $buIds)
            ->whereNotNull('assigned_admin_id')
            ->distinct()
            ->pluck('assigned_admin_id');

        return User::whereIn('id', $adminIds)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    private function getTasks()
    {
        if (!$this->readyToLoad) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        }
        
        $buIds = $this->getFilteredBusinessUnitIds();
        
        if (empty($buIds)) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        }

        $query = AdminTask::with([
            'taskable',
            'businessUnit:id,name,code',
            'department:id,name',
            'assignedAdmin:id,name',
        ])
        ->select([
            'id', 'taskable_type', 'taskable_id', 'business_unit_id', 'department_id',
            'assigned_admin_id', 'status', 'estimated_total_price', 'realized_total_price',
            'savings_amount', 'savings_percentage', 'followup_time_minutes', 
            'completion_time_minutes', 'entered_at', 'started_at', 'completed_at'
        ])
        ->whereIn('business_unit_id', $buIds)
        ->whereNotNull('assigned_admin_id')
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

        // Admin filter
        if ($this->adminFilter !== 'all') {
            $query->where('assigned_admin_id', $this->adminFilter);
        }

        return $query->paginate(10);
    }

    private function getStatistics()
    {
        if (!$this->readyToLoad) {
            return [
                'total_completed' => 0,
                'avg_followup_time' => 0,
                'avg_completion_time' => 0,
                'total_savings' => 0,
                'avg_savings_percentage' => 0,
            ];
        }
        
        $buIds = $this->getFilteredBusinessUnitIds();
        
        if (empty($buIds)) {
            return [
                'total_completed' => 0,
                'avg_followup_time' => 0,
                'avg_completion_time' => 0,
                'total_savings' => 0,
                'avg_savings_percentage' => 0,
            ];
        }

        $query = AdminTask::whereIn('business_unit_id', $buIds)
            ->where('status', 'done');

        if ($this->dateFrom) {
            $query->whereDate('entered_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('entered_at', '<=', $this->dateTo);
        }

        if ($this->adminFilter !== 'all') {
            $query->where('assigned_admin_id', $this->adminFilter);
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
        return view('livewire.modules.purchasing.admin.management-history', [
            'tasks' => $this->getTasks(),
            'statistics' => $this->getStatistics(),
            'adminList' => $this->getAdminList(),
        ])->layout('layouts.app');
    }
}
