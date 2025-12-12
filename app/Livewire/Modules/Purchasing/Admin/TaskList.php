<?php

namespace App\Livewire\Modules\Purchasing\Admin;

use App\Livewire\Traits\HasFilters;
use App\Livewire\Traits\HasLazyLoading;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class TaskList extends Component
{
    use HasFilters, HasLazyLoading, WithPagination;

    public $activeBusinessUnitId;
    public $activeTab = 'pending'; // pending, in_progress, completed

    public function mount(): void
    {
        $this->activeBusinessUnitId = session('current_business_unit_id');

        // Initialize filters with defaults
        $this->filters = [
            'date_from' => '',
            'date_to' => '',
            'type' => '', // PR or ST
        ];
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
        $this->resetFilters();
        $this->resetPage();

        // Acknowledge completion
        $this->dispatch('bu-switch-acknowledge', component: 'task-list');

        $buName = session('current_business_unit_name', 'new business unit');
        $this->dispatch('toast', type: 'success', message: "Switched to {$buName}");
    }

    #[On('task-claimed')]
    #[On('task-started')]
    #[On('task-completed')]
    public function refreshTasks(): void
    {
        $this->resetLazyLoad();
    }

    public function switchTab($tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
        $this->resetLazyLoad();
    }

    #[Computed]
    public function tasks()
    {
        if (!$this->readyToLoad) {
            return collect();
        }

        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        $userId = Auth::id();

        $query = AdminTask::where('business_unit_id', $buId);

        // Filter by tab/status
        switch ($this->activeTab) {
            case 'pending':
                // Pending: unassigned tasks + tasks assigned to current user with pending status
                $query->where('status', 'pending_followup')
                    ->where(function ($q) use ($userId) {
                        $q->whereNull('assigned_admin_id')
                            ->orWhere('assigned_admin_id', $userId);
                    });
                break;

            case 'in_progress':
                // In Progress: only tasks assigned to current user
                $query->where('status', 'in_progress')
                    ->where('assigned_admin_id', $userId);
                break;

            case 'completed':
                // Completed: only tasks completed by current user
                $query->where('status', 'done')
                    ->where('assigned_admin_id', $userId);
                break;
        }

        // Apply date range filter
        if (!empty($this->filters['date_from'])) {
            $query->whereDate('entered_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('entered_at', '<=', $this->filters['date_to']);
        }

        // Apply type filter (PR or ST)
        if (!empty($this->filters['type'])) {
            $query->where('taskable_type', $this->filters['type']);
        }

        // Eager load relationships
        return $query
            ->with(['taskable', 'assignedAdmin', 'department'])
            ->orderBy('entered_at', 'desc')
            ->paginate(12);
    }

    #[Computed]
    public function tabCounts()
    {
        if (!$this->readyToLoad) {
            return [
                'pending' => 0,
                'in_progress' => 0,
                'completed' => 0,
            ];
        }

        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        $userId = Auth::id();

        // Get counts for each tab
        $pendingCount = AdminTask::where('business_unit_id', $buId)
            ->where('status', 'pending_followup')
            ->where(function ($query) use ($userId) {
                $query->whereNull('assigned_admin_id')
                    ->orWhere('assigned_admin_id', $userId);
            })
            ->count();

        $inProgressCount = AdminTask::where('business_unit_id', $buId)
            ->where('status', 'in_progress')
            ->where('assigned_admin_id', $userId)
            ->count();

        $completedCount = AdminTask::where('business_unit_id', $buId)
            ->where('status', 'done')
            ->where('assigned_admin_id', $userId)
            ->count();

        return [
            'pending' => $pendingCount,
            'in_progress' => $inProgressCount,
            'completed' => $completedCount,
        ];
    }

    public function claimTask($taskId): void
    {
        \Log::info('🎯 claimTask CALLED', [
            'taskId' => $taskId,
            'userId' => Auth::id(),
            'activeBusinessUnitId' => $this->activeBusinessUnitId,
        ]);

        $task = AdminTask::find($taskId);

        if (!$task) {
            \Log::warning('❌ Task not found', ['taskId' => $taskId]);
            $this->dispatch('toast', type: 'error', message: 'Task not found');
            return;
        }

        \Log::info('📋 Task found', [
            'taskId' => $task->id,
            'currentAssignedAdminId' => $task->assigned_admin_id,
            'status' => $task->status,
        ]);

        // Check if task is already assigned
        if ($task->assigned_admin_id !== null) {
            \Log::warning('⚠️ Task already assigned', [
                'taskId' => $task->id,
                'assignedTo' => $task->assigned_admin_id,
            ]);
            $this->dispatch('toast', type: 'error', message: 'Task is already assigned');
            return;
        }

        // Assign task to current user
        $task->update([
            'assigned_admin_id' => Auth::id(),
        ]);

        \Log::info('✅ Task claimed successfully', [
            'taskId' => $task->id,
            'assignedTo' => Auth::id(),
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
        return view('livewire.modules.purchasing.admin.task-list')
            ->layout('layouts.app');
    }
}
