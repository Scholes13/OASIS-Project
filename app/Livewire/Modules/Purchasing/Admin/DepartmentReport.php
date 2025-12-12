<?php

namespace App\Livewire\Modules\Purchasing\Admin;

use App\Livewire\Traits\HasLazyLoading;
use App\Models\Core\Department;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Services\Modules\Purchasing\Admin\PriceEfficiencyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class DepartmentReport extends Component
{
    use HasLazyLoading;

    public $activeBusinessUnitId;
    public $departmentId;

    public function mount(): void
    {
        $this->activeBusinessUnitId = session('current_business_unit_id');
        
        // Get the user's department in the current business unit
        $userBu = UserBusinessUnit::where('user_id', Auth::id())
            ->where('business_unit_id', $this->activeBusinessUnitId)
            ->first();
        
        $this->departmentId = $userBu?->department_id;
    }

    public function hydrate(): void
    {
        // Re-check BU after each request
        $sessionBuId = session('current_business_unit_id');
        if ($this->activeBusinessUnitId != $sessionBuId) {
            $this->activeBusinessUnitId = $sessionBuId;
            
            // Update department ID for new BU
            $userBu = UserBusinessUnit::where('user_id', Auth::id())
                ->where('business_unit_id', $this->activeBusinessUnitId)
                ->first();
            
            $this->departmentId = $userBu?->department_id;
            $this->resetLazyLoad();
        }
    }

    #[On('business-unit-switched')]
    public function handleBusinessUnitSwitch($businessUnitId): void
    {
        // Update session FIRST (single source of truth)
        session(['current_business_unit_id' => $businessUnitId]);

        // Update property (for UI binding)
        $this->activeBusinessUnitId = $businessUnitId;

        // Update department ID for new BU
        $userBu = UserBusinessUnit::where('user_id', Auth::id())
            ->where('business_unit_id', $this->activeBusinessUnitId)
            ->first();
        
        $this->departmentId = $userBu?->department_id;

        // Reload data
        $this->resetLazyLoad();

        // Acknowledge completion
        $this->dispatch('bu-switch-acknowledge', component: 'department-report');

        $buName = session('current_business_unit_name', 'new business unit');
        $this->dispatch('toast', type: 'success', message: "Switched to {$buName}");
    }

    #[On('task-completed')]
    #[On('refresh-metrics')]
    public function refreshMetrics(): void
    {
        $this->resetLazyLoad();
    }

    #[Computed]
    public function department()
    {
        if (!$this->readyToLoad || !$this->departmentId) {
            return null;
        }

        return Department::find($this->departmentId);
    }

    #[Computed]
    public function totalSavings()
    {
        if (!$this->readyToLoad || !$this->departmentId) {
            return 0;
        }

        $service = app(PriceEfficiencyService::class);
        return $service->getDepartmentTotalSavings($this->departmentId, $this->activeBusinessUnitId);
    }

    #[Computed]
    public function averageFollowupTime()
    {
        if (!$this->readyToLoad || !$this->departmentId) {
            return 0;
        }

        $service = app(PriceEfficiencyService::class);
        return $service->getDepartmentAverageFollowupTime($this->departmentId, $this->activeBusinessUnitId);
    }

    #[Computed]
    public function averageCompletionTime()
    {
        if (!$this->readyToLoad || !$this->departmentId) {
            return 0;
        }

        $service = app(PriceEfficiencyService::class);
        return $service->getDepartmentAverageCompletionTime($this->departmentId, $this->activeBusinessUnitId);
    }

    #[Computed]
    public function savingsBreakdown()
    {
        if (!$this->readyToLoad || !$this->departmentId) {
            return [
                'purchase_request' => 0,
                'stock_request' => 0,
            ];
        }

        $service = app(PriceEfficiencyService::class);
        return $service->getSavingsBreakdownByType($this->departmentId, $this->activeBusinessUnitId);
    }

    #[Computed]
    public function adminPerformance()
    {
        if (!$this->readyToLoad || !$this->departmentId) {
            return collect();
        }

        // Get all purchasing admins in this department
        $admins = UserBusinessUnit::with('user')
            ->where('business_unit_id', $this->activeBusinessUnitId)
            ->where('department_id', $this->departmentId)
            ->where('is_purchasing_admin', true)
            ->get();

        $service = app(PriceEfficiencyService::class);

        return $admins->map(function ($userBu) use ($service) {
            $userId = $userBu->user_id;
            
            $tasksCompleted = AdminTask::where('business_unit_id', $this->activeBusinessUnitId)
                ->where('assigned_admin_id', $userId)
                ->where('status', 'done')
                ->count();

            return [
                'name' => $userBu->user->name,
                'tasks_completed' => $tasksCompleted,
                'total_savings' => $service->getAdminTotalSavings($userId, $this->activeBusinessUnitId),
                'avg_savings_percentage' => $service->getAdminAverageSavingsPercentage($userId, $this->activeBusinessUnitId),
                'avg_followup_time' => $service->getAdminAverageFollowupTime($userId, $this->activeBusinessUnitId),
                'avg_completion_time' => $service->getAdminAverageCompletionTime($userId, $this->activeBusinessUnitId),
            ];
        })->sortByDesc('total_savings');
    }

    #[Computed]
    public function departmentTrendData()
    {
        if (!$this->readyToLoad || !$this->departmentId) {
            return [
                'labels' => [],
                'data' => [],
            ];
        }

        // Get department savings by month for the last 12 months
        $savingsByMonth = AdminTask::select(
            DB::raw('DATE_FORMAT(completed_at, "%Y-%m") as month'),
            DB::raw('SUM(savings_amount) as total_savings')
        )
            ->where('business_unit_id', $this->activeBusinessUnitId)
            ->where('department_id', $this->departmentId)
            ->where('status', 'done')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $labels = [];
        $data = [];

        foreach ($savingsByMonth as $item) {
            // Format month as "Jan 2024"
            $date = \Carbon\Carbon::createFromFormat('Y-m', $item->month);
            $labels[] = $date->format('M Y');
            $data[] = round($item->total_savings, 2);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    #[Computed]
    public function totalTasksCompleted()
    {
        if (!$this->readyToLoad || !$this->departmentId) {
            return 0;
        }

        return AdminTask::where('business_unit_id', $this->activeBusinessUnitId)
            ->where('department_id', $this->departmentId)
            ->where('status', 'done')
            ->count();
    }

    /**
     * Format minutes to human-readable time
     */
    public function formatTime($minutes): string
    {
        if ($minutes < 60) {
            return round($minutes) . ' min';
        }

        $hours = floor($minutes / 60);
        $mins = round($minutes % 60);

        if ($hours < 24) {
            return $mins > 0 ? "{$hours}h {$mins}m" : "{$hours}h";
        }

        $days = floor($hours / 24);
        $remainingHours = $hours % 24;

        if ($remainingHours > 0) {
            return "{$days}d {$remainingHours}h";
        }

        return "{$days}d";
    }

    /**
     * Format currency
     */
    public function formatCurrency($amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    public function render()
    {
        return view('livewire.modules.purchasing.admin.department-report')
            ->layout('layouts.app');
    }
}
