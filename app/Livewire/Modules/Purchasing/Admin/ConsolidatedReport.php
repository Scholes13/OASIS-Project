<?php

namespace App\Livewire\Modules\Purchasing\Admin;

use App\Livewire\Traits\HasLazyLoading;
use App\Models\Core\BusinessUnit;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Services\Modules\Purchasing\Admin\PriceEfficiencyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ConsolidatedReport extends Component
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
        $this->dispatch('bu-switch-acknowledge', component: 'consolidated-report');

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
    public function childBusinessUnits()
    {
        if (!$this->readyToLoad) {
            return collect();
        }

        // Get current business unit
        $currentBu = BusinessUnit::find($this->activeBusinessUnitId);
        
        if (!$currentBu) {
            return collect();
        }

        // If this is a parent BU (has no parent_id), get all children
        if ($currentBu->parent_id === null) {
            return BusinessUnit::where('parent_id', $currentBu->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        // If this is a child BU, return empty (consolidated report only for parent BU)
        return collect();
    }

    #[Computed]
    public function businessUnitMetrics()
    {
        if (!$this->readyToLoad) {
            return collect();
        }

        $service = app(PriceEfficiencyService::class);
        
        return $this->childBusinessUnits->map(function ($bu) use ($service) {
            $totalTasks = AdminTask::where('business_unit_id', $bu->id)
                ->where('status', 'done')
                ->count();

            $totalSavings = AdminTask::where('business_unit_id', $bu->id)
                ->where('status', 'done')
                ->sum('savings_amount') ?? 0;

            $avgSavingsPercentage = AdminTask::where('business_unit_id', $bu->id)
                ->where('status', 'done')
                ->whereNotNull('savings_percentage')
                ->avg('savings_percentage') ?? 0;

            $avgFollowupTime = AdminTask::where('business_unit_id', $bu->id)
                ->where('status', 'done')
                ->whereNotNull('followup_time_minutes')
                ->avg('followup_time_minutes') ?? 0;

            $avgCompletionTime = AdminTask::where('business_unit_id', $bu->id)
                ->where('status', 'done')
                ->whereNotNull('completion_time_minutes')
                ->avg('completion_time_minutes') ?? 0;

            return [
                'id' => $bu->id,
                'code' => $bu->code,
                'name' => $bu->name,
                'total_tasks' => $totalTasks,
                'total_savings' => $totalSavings,
                'avg_savings_percentage' => $avgSavingsPercentage,
                'avg_followup_time' => $avgFollowupTime,
                'avg_completion_time' => $avgCompletionTime,
            ];
        })->sortByDesc('total_savings');
    }

    #[Computed]
    public function overallMetrics()
    {
        if (!$this->readyToLoad) {
            return [
                'total_tasks' => 0,
                'total_savings' => 0,
                'avg_savings_percentage' => 0,
                'avg_followup_time' => 0,
                'avg_completion_time' => 0,
            ];
        }

        $buIds = $this->childBusinessUnits->pluck('id')->toArray();

        if (empty($buIds)) {
            return [
                'total_tasks' => 0,
                'total_savings' => 0,
                'avg_savings_percentage' => 0,
                'avg_followup_time' => 0,
                'avg_completion_time' => 0,
            ];
        }

        $totalTasks = AdminTask::whereIn('business_unit_id', $buIds)
            ->where('status', 'done')
            ->count();

        $totalSavings = AdminTask::whereIn('business_unit_id', $buIds)
            ->where('status', 'done')
            ->sum('savings_amount') ?? 0;

        $avgSavingsPercentage = AdminTask::whereIn('business_unit_id', $buIds)
            ->where('status', 'done')
            ->whereNotNull('savings_percentage')
            ->avg('savings_percentage') ?? 0;

        $avgFollowupTime = AdminTask::whereIn('business_unit_id', $buIds)
            ->where('status', 'done')
            ->whereNotNull('followup_time_minutes')
            ->avg('followup_time_minutes') ?? 0;

        $avgCompletionTime = AdminTask::whereIn('business_unit_id', $buIds)
            ->where('status', 'done')
            ->whereNotNull('completion_time_minutes')
            ->avg('completion_time_minutes') ?? 0;

        return [
            'total_tasks' => $totalTasks,
            'total_savings' => $totalSavings,
            'avg_savings_percentage' => $avgSavingsPercentage,
            'avg_followup_time' => $avgFollowupTime,
            'avg_completion_time' => $avgCompletionTime,
        ];
    }

    #[Computed]
    public function comparativeTrendData()
    {
        if (!$this->readyToLoad) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $buIds = $this->childBusinessUnits->pluck('id')->toArray();

        if (empty($buIds)) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        // Get savings by month for each BU for the last 12 months
        $savingsByBuAndMonth = AdminTask::select(
            'business_unit_id',
            DB::raw('DATE_FORMAT(completed_at, "%Y-%m") as month'),
            DB::raw('SUM(savings_amount) as total_savings')
        )
            ->whereIn('business_unit_id', $buIds)
            ->where('status', 'done')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subMonths(12))
            ->groupBy('business_unit_id', 'month')
            ->orderBy('month', 'asc')
            ->get();

        // Get all unique months
        $months = $savingsByBuAndMonth->pluck('month')->unique()->sort()->values();
        
        $labels = [];
        foreach ($months as $month) {
            $date = \Carbon\Carbon::createFromFormat('Y-m', $month);
            $labels[] = $date->format('M Y');
        }

        // Build datasets for each BU
        $datasets = [];
        $colors = [
            ['border' => 'rgb(99, 102, 241)', 'bg' => 'rgba(99, 102, 241, 0.1)'], // Indigo
            ['border' => 'rgb(16, 185, 129)', 'bg' => 'rgba(16, 185, 129, 0.1)'], // Emerald
            ['border' => 'rgb(59, 130, 246)', 'bg' => 'rgba(59, 130, 246, 0.1)'], // Blue
            ['border' => 'rgb(245, 158, 11)', 'bg' => 'rgba(245, 158, 11, 0.1)'], // Amber
            ['border' => 'rgb(239, 68, 68)', 'bg' => 'rgba(239, 68, 68, 0.1)'], // Red
        ];

        foreach ($this->childBusinessUnits as $index => $bu) {
            $data = [];
            
            foreach ($months as $month) {
                $savings = $savingsByBuAndMonth
                    ->where('business_unit_id', $bu->id)
                    ->where('month', $month)
                    ->first();
                
                $data[] = $savings ? round($savings->total_savings, 2) : 0;
            }

            $color = $colors[$index % count($colors)];
            
            $datasets[] = [
                'label' => $bu->name,
                'data' => $data,
                'borderColor' => $color['border'],
                'backgroundColor' => $color['bg'],
                'tension' => 0.4,
                'fill' => true,
                'pointRadius' => 4,
                'pointHoverRadius' => 6,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
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
        return view('livewire.modules.purchasing.admin.consolidated-report')
            ->layout('layouts.app');
    }
}
