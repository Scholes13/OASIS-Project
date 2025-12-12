<?php

namespace App\Livewire\Modules\Purchasing\Admin;

use App\Livewire\Traits\HasLazyLoading;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use Illuminate\Support\Facades\Auth;
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

    public function render()
    {
        return view('livewire.modules.purchasing.admin.admin-dashboard')
            ->layout('layouts.app');
    }
}
