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

class AdminDashboard extends Component
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
        $this->dispatch('bu-switch-acknowledge', component: 'admin-dashboard');

        $buName = session('current_business_unit_name', 'new business unit');
        $this->dispatch('toast', type: 'success', message: "Switched to {$buName}");
    }

    #[On('task-claimed')]
    #[On('task-started')]
    #[On('task-completed')]
    public function refreshDashboard(): void
    {
        $this->resetLazyLoad();
    }

    #[Computed]
    public function taskCounts()
    {
        if (!$this->readyToLoad) {
            return [
                'pending' => 0,
                'in_progress' => 0,
                'done' => 0,
            ];
        }

        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        $userId = Auth::id();

        // Get counts for each status
        // Pending: unassigned tasks + tasks assigned to current user with pending status
        $pendingCount = AdminTask::where('business_unit_id', $buId)
            ->where('status', 'pending_followup')
            ->where(function ($query) use ($userId) {
                $query->whereNull('assigned_admin_id')
                    ->orWhere('assigned_admin_id', $userId);
            })
            ->count();

        // In Progress: only tasks assigned to current user
        $inProgressCount = AdminTask::where('business_unit_id', $buId)
            ->where('status', 'in_progress')
            ->where('assigned_admin_id', $userId)
            ->count();

        // Done: only tasks completed by current user
        $doneCount = AdminTask::where('business_unit_id', $buId)
            ->where('status', 'done')
            ->where('assigned_admin_id', $userId)
            ->count();

        return [
            'pending' => $pendingCount,
            'in_progress' => $inProgressCount,
            'done' => $doneCount,
        ];
    }

    #[Computed]
    public function recentTasks()
    {
        if (!$this->readyToLoad) {
            return collect();
        }

        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        $userId = Auth::id();

        // Get recent tasks (pending + in progress) for quick actions
        return AdminTask::where('business_unit_id', $buId)
            ->whereIn('status', ['pending_followup', 'in_progress'])
            ->where(function ($query) use ($userId) {
                $query->whereNull('assigned_admin_id')
                    ->orWhere('assigned_admin_id', $userId);
            })
            ->with(['taskable', 'assignedAdmin'])
            ->orderBy('entered_at', 'desc')
            ->limit(5)
            ->get();
    }

    public function claimTask($taskId): void
    {
        $task = AdminTask::find($taskId);

        if (!$task) {
            $this->dispatch('toast', type: 'error', message: 'Task not found');
            return;
        }

        // Check if task is already assigned
        if ($task->assigned_admin_id !== null) {
            $this->dispatch('toast', type: 'error', message: 'Task is already assigned');
            return;
        }

        // Assign task to current user
        $task->update([
            'assigned_admin_id' => Auth::id(),
        ]);

        $this->dispatch('toast', type: 'success', message: 'Task claimed successfully');
        $this->dispatch('task-claimed', taskId: $taskId);
        $this->resetLazyLoad();
    }

    public function startTask($taskId): void
    {
        $task = AdminTask::find($taskId);

        if (!$task) {
            $this->dispatch('toast', type: 'error', message: 'Task not found');
            return;
        }

        // Check if task is assigned to current user
        if ($task->assigned_admin_id !== Auth::id()) {
            $this->dispatch('toast', type: 'error', message: 'Task is not assigned to you');
            return;
        }

        // Check if task is in pending status
        if ($task->status !== 'pending_followup') {
            $this->dispatch('toast', type: 'error', message: 'Task is not in pending status');
            return;
        }

        // Start the task
        $startedAt = now();
        $followupTimeMinutes = $startedAt->diffInMinutes($task->entered_at);

        $task->update([
            'status' => 'in_progress',
            'started_at' => $startedAt,
            'followup_time_minutes' => $followupTimeMinutes,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Task started successfully');
        $this->dispatch('task-started', taskId: $taskId);
        $this->resetLazyLoad();
    }

    #[Computed]
    public function totalTasksCompleted()
    {
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
        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        $userId = Auth::id();

        $service = app(PriceEfficiencyService::class);
        return $service->getAdminAverageFollowupTime($userId, $buId);
    }

    #[Computed]
    public function averageCompletionTime()
    {
        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        $userId = Auth::id();

        $service = app(PriceEfficiencyService::class);
        return $service->getAdminAverageCompletionTime($userId, $buId);
    }

    #[Computed]
    public function totalSavings()
    {
        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        $userId = Auth::id();

        $service = app(PriceEfficiencyService::class);
        return $service->getAdminTotalSavings($userId, $buId);
    }

    #[Computed]
    public function averageSavingsPercentage()
    {
        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        $userId = Auth::id();

        $service = app(PriceEfficiencyService::class);
        return $service->getAdminAverageSavingsPercentage($userId, $buId);
    }

    #[Computed]
    public function savingsTrendData()
    {
        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        $userId = Auth::id();

        // Get savings by month for the last 6 months
        $savingsByMonth = AdminTask::select(
            DB::raw('DATE_FORMAT(completed_at, "%Y-%m") as month'),
            DB::raw('AVG(savings_percentage) as avg_savings')
        )
            ->where('business_unit_id', $buId)
            ->where('assigned_admin_id', $userId)
            ->where('status', 'done')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $labels = [];
        $data = [];

        foreach ($savingsByMonth as $item) {
            $date = \Carbon\Carbon::createFromFormat('Y-m', $item->month);
            $labels[] = $date->format('M Y');
            $data[] = round($item->avg_savings, 1);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    #[Computed]
    public function departmentBreakdown()
    {
        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        $userId = Auth::id();

        // Get task count by department
        $departments = AdminTask::select('department_id', DB::raw('COUNT(*) as task_count'))
            ->where('business_unit_id', $buId)
            ->where('assigned_admin_id', $userId)
            ->where('status', 'done')
            ->with('department:id,name')
            ->groupBy('department_id')
            ->orderBy('task_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'department' => $item->department->name ?? 'Unknown',
                    'count' => $item->task_count,
                ];
            });

        // Calculate total and percentages
        $total = $departments->sum('count');
        
        return $departments->map(function ($item) use ($total) {
            $item['percentage'] = $total > 0 ? round(($item['count'] / $total) * 100, 1) : 0;
            return $item;
        });
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
        return view('livewire.modules.purchasing.admin.admin-dashboard')
            ->layout('layouts.app');
    }
}
