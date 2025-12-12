<?php

namespace App\Livewire\Modules\Purchasing\Admin;

use App\Livewire\Traits\HasLazyLoading;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Services\Modules\Purchasing\Admin\PriceEfficiencyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class PerformanceMetrics extends Component
{
    use HasLazyLoading;

    public $activeBusinessUnitId;

    public function mount(): void
    {
        $this->activeBusinessUnitId = session('current_business_unit_id');
    }

    public function hydrate(): void
    {
        // Re-check BU after each request
        $sessionBuId = session('current_business_unit_id');
        if ($this->activeBusinessUnitId != $sessionBuId) {
            $this->activeBusinessUnitId = $sessionBuId;
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

        // Reload data
        $this->resetLazyLoad();

        // Acknowledge completion
        $this->dispatch('bu-switch-acknowledge', component: 'performance-metrics');

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
    public function totalTasksCompleted()
    {
        if (!$this->readyToLoad) {
            return 0;
        }

        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        $userId = Auth::id();

        return AdminTask::where('business_unit_id', $buId)
            ->where('status', 'done')
            ->where('assigned_admin_id', $userId)
            ->count();
    }

    #[Computed]
    public function averageFollowupTime()
    {
        if (!$this->readyToLoad) {
            return 0;
        }

        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        $userId = Auth::id();

        $service = app(PriceEfficiencyService::class);
        return $service->getAdminAverageFollowupTime($userId, $buId);
    }

    #[Computed]
    public function averageCompletionTime()
    {
        if (!$this->readyToLoad) {
            return 0;
        }

        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        $userId = Auth::id();

        $service = app(PriceEfficiencyService::class);
        return $service->getAdminAverageCompletionTime($userId, $buId);
    }

    #[Computed]
    public function totalSavings()
    {
        if (!$this->readyToLoad) {
            return 0;
        }

        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        $userId = Auth::id();

        $service = app(PriceEfficiencyService::class);
        return $service->getAdminTotalSavings($userId, $buId);
    }

    #[Computed]
    public function averageSavingsPercentage()
    {
        if (!$this->readyToLoad) {
            return 0;
        }

        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        $userId = Auth::id();

        $service = app(PriceEfficiencyService::class);
        return $service->getAdminAverageSavingsPercentage($userId, $buId);
    }

    #[Computed]
    public function savingsTrendData()
    {
        if (!$this->readyToLoad) {
            return [
                'labels' => [],
                'data' => [],
            ];
        }

        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        $userId = Auth::id();

        // Get savings by month for the last 12 months
        $savingsByMonth = AdminTask::select(
            DB::raw('DATE_FORMAT(completed_at, "%Y-%m") as month'),
            DB::raw('AVG(savings_percentage) as avg_savings')
        )
            ->where('business_unit_id', $buId)
            ->where('assigned_admin_id', $userId)
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
            $data[] = round($item->avg_savings, 2);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    #[Computed]
    public function tasksPerMonthData()
    {
        if (!$this->readyToLoad) {
            return [
                'labels' => [],
                'data' => [],
            ];
        }

        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        $userId = Auth::id();

        // Get task count by month for the last 12 months
        $tasksByMonth = AdminTask::select(
            DB::raw('DATE_FORMAT(completed_at, "%Y-%m") as month'),
            DB::raw('COUNT(*) as task_count')
        )
            ->where('business_unit_id', $buId)
            ->where('assigned_admin_id', $userId)
            ->where('status', 'done')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $labels = [];
        $data = [];

        foreach ($tasksByMonth as $item) {
            // Format month as "Jan 2024"
            $date = \Carbon\Carbon::createFromFormat('Y-m', $item->month);
            $labels[] = $date->format('M Y');
            $data[] = $item->task_count;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
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
        return view('livewire.modules.purchasing.admin.performance-metrics')
            ->layout('layouts.app');
    }
}
