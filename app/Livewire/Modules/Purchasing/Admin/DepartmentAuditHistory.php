<?php

namespace App\Livewire\Modules\Purchasing\Admin;

use App\Livewire\Traits\HasLazyLoading;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class DepartmentAuditHistory extends Component
{
    use HasLazyLoading, WithPagination;

    public $activeBusinessUnitId;
    public $dateFrom;
    public $dateTo;
    public $statusFilter = 'all';
    public $typeFilter = 'all';
    public $adminFilter = 'all';

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

    public function updatedAdminFilter()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->statusFilter = 'all';
        $this->typeFilter = 'all';
        $this->adminFilter = 'all';
        $this->resetPage();
    }

    private function getUserDepartmentId()
    {
        $user = auth()->user();
        
        // Get the user's department in the current business unit
        $userBusinessUnit = $user->businessUnits()
            ->where('business_unit_id', $this->activeBusinessUnitId)
            ->first();
        
        return $userBusinessUnit?->department_id;
    }

    private function getTasks()
    {
        $departmentId = $this->getUserDepartmentId();
        
        if (!$departmentId) {
            return new LengthAwarePaginator([], 0, 20);
        }

        $query = AdminTask::with([
            'taskable',
            'businessUnit',
            'department',
            'assignedAdmin'
        ])
        ->where('department_id', $departmentId)
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

        return $query->paginate(20);
    }

    private function getAdmins()
    {
        $departmentId = $this->getUserDepartmentId();
        
        if (!$departmentId) {
            return collect();
        }

        return \App\Models\Core\User::whereHas('businessUnits', function ($query) use ($departmentId) {
            $query->where('is_purchasing_admin', true)
                ->where('department_id', $departmentId);
        })->orderBy('name')->get();
    }

    public function render()
    {
        if (!$this->readyToLoad) {
            return view('livewire.modules.purchasing.admin.department-audit-history', [
                'tasks' => new LengthAwarePaginator([], 0, 20),
                'admins' => collect(),
            ]);
        }

        return view('livewire.modules.purchasing.admin.department-audit-history', [
            'tasks' => $this->getTasks(),
            'admins' => $this->getAdmins(),
        ]);
    }
}
