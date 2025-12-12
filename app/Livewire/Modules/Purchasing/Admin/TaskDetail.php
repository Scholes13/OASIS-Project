<?php

namespace App\Livewire\Modules\Purchasing\Admin;

use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Services\Modules\Purchasing\Admin\AdminTaskService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class TaskDetail extends Component
{
    public $taskId;
    public $activeBusinessUnitId;
    
    // Form fields for completion
    public $realizedTotalPrice = '';
    public $notes = '';
    
    // UI state
    public $showCompleteModal = false;

    public function mount($taskId): void
    {
        $this->taskId = $taskId;
        $this->activeBusinessUnitId = session('current_business_unit_id');
    }

    public function hydrate(): void
    {
        // Re-check BU after each request
        $sessionBuId = session('current_business_unit_id');
        if ($this->activeBusinessUnitId != $sessionBuId) {
            $this->activeBusinessUnitId = $sessionBuId;
        }
    }

    #[On('business-unit-switched')]
    public function handleBusinessUnitSwitch($businessUnitId): void
    {
        // Update session FIRST (single source of truth)
        session(['current_business_unit_id' => $businessUnitId]);

        // Update property (for UI binding)
        $this->activeBusinessUnitId = $businessUnitId;

        // Acknowledge completion
        $this->dispatch('bu-switch-acknowledge', component: 'task-detail');

        $buName = session('current_business_unit_name', 'new business unit');
        $this->dispatch('toast', type: 'success', message: "Switched to {$buName}");
        
        // Redirect to task list if task is not in current BU
        $task = AdminTask::find($this->taskId);
        if ($task && $task->business_unit_id != $businessUnitId) {
            $this->redirect(route('purchasing.admin.tasks'));
        }
    }

    #[Computed]
    public function task()
    {
        return AdminTask::with([
            'taskable',
            'assignedAdmin',
            'department',
            'businessUnit',
            'activities' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'activities.causer'
        ])->findOrFail($this->taskId);
    }

    /**
     * Claim an unassigned task
     */
    public function claimTask(): void
    {
        try {
            $task = $this->task;
            
            // Validation
            if ($task->assigned_admin_id !== null) {
                $this->dispatch('toast', type: 'error', message: 'Task is already assigned to another admin');
                return;
            }

            if ($task->status !== 'pending_followup') {
                $this->dispatch('toast', type: 'error', message: 'Only pending tasks can be claimed');
                return;
            }

            // Use service to claim task (handles exclusivity with database lock)
            $taskService = app(AdminTaskService::class);
            $taskService->claimTask($task, Auth::id());

            $this->dispatch('toast', type: 'success', message: 'Task claimed successfully');
            $this->dispatch('task-claimed', taskId: $this->taskId);
            
            // Refresh the task data
            unset($this->task);
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    /**
     * Start working on a task
     */
    public function startTask(): void
    {
        try {
            $task = $this->task;
            
            // Validation
            if ($task->status !== 'pending_followup') {
                $this->dispatch('toast', type: 'error', message: 'Task must be in pending status to start');
                return;
            }

            if ($task->assigned_admin_id !== Auth::id()) {
                $this->dispatch('toast', type: 'error', message: 'Task must be assigned to you to start');
                return;
            }

            // Use service to start task
            $taskService = app(AdminTaskService::class);
            $taskService->startTask($task);

            $this->dispatch('toast', type: 'success', message: 'Task started successfully');
            $this->dispatch('task-started', taskId: $this->taskId);
            
            // Refresh the task data
            unset($this->task);
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    /**
     * Open the complete task modal
     */
    public function openCompleteModal(): void
    {
        $task = $this->task;
        
        // Validation
        if ($task->status !== 'in_progress') {
            $this->dispatch('toast', type: 'error', message: 'Task must be in progress to complete');
            return;
        }

        if ($task->assigned_admin_id !== Auth::id()) {
            $this->dispatch('toast', type: 'error', message: 'Task must be assigned to you to complete');
            return;
        }

        // Pre-fill with estimated price as suggestion
        $this->realizedTotalPrice = number_format($task->estimated_total_price, 2, '.', '');
        $this->notes = '';
        $this->showCompleteModal = true;
    }

    /**
     * Close the complete task modal
     */
    public function closeCompleteModal(): void
    {
        $this->showCompleteModal = false;
        $this->realizedTotalPrice = '';
        $this->notes = '';
        $this->resetValidation();
    }

    /**
     * Complete a task with realized price
     */
    public function completeTask(): void
    {
        // Validate input
        $this->validate([
            'realizedTotalPrice' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
        ], [
            'realizedTotalPrice.required' => 'Realized price is required',
            'realizedTotalPrice.numeric' => 'Realized price must be a number',
            'realizedTotalPrice.min' => 'Realized price must be greater than zero',
            'notes.max' => 'Notes cannot exceed 1000 characters',
        ]);

        try {
            $task = $this->task;
            
            // Additional validation
            if ($task->status !== 'in_progress') {
                $this->dispatch('toast', type: 'error', message: 'Task must be in progress to complete');
                return;
            }

            if ($task->assigned_admin_id !== Auth::id()) {
                $this->dispatch('toast', type: 'error', message: 'Task must be assigned to you to complete');
                return;
            }

            // Use service to complete task
            $taskService = app(AdminTaskService::class);
            $taskService->completeTask(
                $task,
                (float) $this->realizedTotalPrice,
                $this->notes ?: null
            );

            $this->dispatch('toast', type: 'success', message: 'Task completed successfully');
            $this->dispatch('task-completed', taskId: $this->taskId);
            
            // Close modal and refresh
            $this->closeCompleteModal();
            unset($this->task);
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColor($status): string
    {
        return match ($status) {
            'pending_followup' => 'bg-amber-100 text-amber-700',
            'in_progress' => 'bg-blue-100 text-blue-700',
            'done' => 'bg-emerald-100 text-emerald-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabel($status): string
    {
        return match ($status) {
            'pending_followup' => 'Pending Follow-up',
            'in_progress' => 'In Progress',
            'done' => 'Completed',
            default => ucfirst($status),
        };
    }

    /**
     * Get taskable type label
     */
    public function getTaskableTypeLabel($type): string
    {
        if (str_contains($type, 'PurchaseRequest')) {
            return 'Purchase Request';
        }
        if (str_contains($type, 'StockRequest')) {
            return 'Stock Request';
        }
        return 'Unknown';
    }

    public function render()
    {
        return view('livewire.modules.purchasing.admin.task-detail')
            ->layout('layouts.app');
    }
}
