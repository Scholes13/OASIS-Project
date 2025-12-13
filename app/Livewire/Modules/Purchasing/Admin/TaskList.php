<?php

namespace App\Livewire\Modules\Purchasing\Admin;

use App\Livewire\Traits\HasFilters;
use App\Livewire\Traits\HasLazyLoading;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Models\Modules\Purchasing\Admin\AdminTaskItemRealization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class TaskList extends Component
{
    use HasFilters, HasLazyLoading, WithPagination;

    public $activeBusinessUnitId;
    public $activeTab = 'pending'; // pending, in_progress, completed

    // Quick complete modal
    public $showCompleteModal = false;
    public $completingTaskId = null;
    public $realizedTotalPrice = '';
    public $completionNotes = '';

    // Item-level realization data (Requirements 1.1, 1.2)
    public $completingTaskItems = [];  // Holds loaded items from PR/ST
    public $itemRealizations = [];     // Holds user input for each item

    // Date filter preset
    public $datePreset = 'all';
    
    // Search
    public $search = '';

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
    
    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->resetLazyLoad();
    }

    public function updatedDatePreset($value): void
    {
        $today = now();
        
        switch ($value) {
            case 'today':
                $this->filters['date_from'] = $today->format('Y-m-d');
                $this->filters['date_to'] = $today->format('Y-m-d');
                break;
            case 'last_30_days':
                $this->filters['date_from'] = $today->copy()->subDays(30)->format('Y-m-d');
                $this->filters['date_to'] = $today->format('Y-m-d');
                break;
            case 'last_3_months':
                $this->filters['date_from'] = $today->copy()->subMonths(3)->format('Y-m-d');
                $this->filters['date_to'] = $today->format('Y-m-d');
                break;
            case 'last_6_months':
                $this->filters['date_from'] = $today->copy()->subMonths(6)->format('Y-m-d');
                $this->filters['date_to'] = $today->format('Y-m-d');
                break;
            case 'all':
            default:
                $this->filters['date_from'] = '';
                $this->filters['date_to'] = '';
                break;
        }
        
        $this->resetPage();
        $this->resetLazyLoad();
    }

    public function resetFilters(): void
    {
        $this->filters = [
            'date_from' => '',
            'date_to' => '',
            'type' => '',
        ];
        $this->datePreset = 'all';
        $this->search = '';
        $this->resetPage();
        $this->resetLazyLoad();
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

        // Apply search filter (search by PR/ST number)
        if (!empty($this->search)) {
            $searchTerm = $this->search;
            $query->whereHas('taskable', function ($q) use ($searchTerm) {
                $q->where('pr_number', 'like', "%{$searchTerm}%")
                    ->orWhere('st_number', 'like', "%{$searchTerm}%");
            });
        }

        // Eager load relationships (taskable is polymorphic, can't select specific columns)
        return $query
            ->with([
                'taskable',
                'assignedAdmin:id,name',
                'department:id,name'
            ])
            ->select([
                'id', 'taskable_type', 'taskable_id', 'business_unit_id',
                'department_id', 'assigned_admin_id', 'status',
                'estimated_total_price', 'entered_at'
            ])
            ->orderBy('entered_at', 'desc')
            ->paginate(10);
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

        // Single query with conditional aggregation for better performance
        $counts = AdminTask::where('business_unit_id', $buId)
            ->selectRaw("
                SUM(CASE WHEN status = 'pending_followup' AND (assigned_admin_id IS NULL OR assigned_admin_id = ?) THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'in_progress' AND assigned_admin_id = ? THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'done' AND assigned_admin_id = ? THEN 1 ELSE 0 END) as completed
            ", [$userId, $userId, $userId])
            ->first();

        return [
            'pending' => (int) ($counts->pending ?? 0),
            'in_progress' => (int) ($counts->in_progress ?? 0),
            'completed' => (int) ($counts->completed ?? 0),
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
        \Log::info('🚀 startTask CALLED', [
            'taskId' => $taskId,
            'userId' => Auth::id(),
        ]);

        $task = AdminTask::find($taskId);

        if (!$task) {
            \Log::warning('❌ Task not found', ['taskId' => $taskId]);
            $this->dispatch('toast', type: 'error', message: 'Task not found');
            return;
        }

        \Log::info('📋 Task found for start', [
            'taskId' => $task->id,
            'assignedAdminId' => $task->assigned_admin_id,
            'currentUserId' => Auth::id(),
            'status' => $task->status,
        ]);

        // Check if task is assigned to current user
        if ($task->assigned_admin_id !== Auth::id()) {
            \Log::warning('⚠️ Task not assigned to current user', [
                'taskId' => $task->id,
                'assignedTo' => $task->assigned_admin_id,
                'currentUser' => Auth::id(),
            ]);
            $this->dispatch('toast', type: 'error', message: 'Task is not assigned to you');
            return;
        }

        // Check if task is in pending status
        if ($task->status !== 'pending_followup') {
            \Log::warning('⚠️ Task not in pending status', [
                'taskId' => $task->id,
                'status' => $task->status,
            ]);
            $this->dispatch('toast', type: 'error', message: 'Task is not in pending status');
            return;
        }

        // Start the task
        $startedAt = now();
        // Follow-up time = time from entered to started (always positive)
        $followupTimeMinutes = $task->entered_at->diffInMinutes($startedAt, false);
        // Ensure positive value
        $followupTimeMinutes = abs($followupTimeMinutes);

        $task->update([
            'status' => 'in_progress',
            'started_at' => $startedAt,
            'followup_time_minutes' => $followupTimeMinutes,
        ]);

        \Log::info('✅ Task started successfully', [
            'taskId' => $task->id,
            'newStatus' => 'in_progress',
            'startedAt' => $startedAt,
            'followupTimeMinutes' => $followupTimeMinutes,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Task started successfully');
        $this->dispatch('task-started', taskId: $taskId);
        $this->resetLazyLoad();
    }

    public function openCompleteModal($taskId): void
    {
        $task = AdminTask::find($taskId);

        if (!$task) {
            $this->dispatch('toast', type: 'error', message: 'Task not found');
            return;
        }

        if ($task->status !== 'in_progress') {
            $this->dispatch('toast', type: 'error', message: 'Task must be in progress to complete');
            return;
        }

        if ($task->assigned_admin_id !== Auth::id()) {
            $this->dispatch('toast', type: 'error', message: 'Task must be assigned to you to complete');
            return;
        }

        $this->completingTaskId = $taskId;
        $this->completionNotes = '';
        
        // Load items for item-level realization (Requirements 1.1, 1.2, 2.1, 6.1)
        $this->loadTaskItems($task);
        
        // Calculate grand total from item realizations
        $this->calculateGrandTotal();
        
        $this->showCompleteModal = true;
    }

    /**
     * Load PR/ST items when modal opens
     * Pre-fill realized prices with estimated prices (Requirement 2.1)
     * Pre-fill realized supplier with original supplier (Requirement 6.1)
     */
    protected function loadTaskItems(AdminTask $task): void
    {
        $this->completingTaskItems = [];
        $this->itemRealizations = [];

        $taskable = $task->taskable;
        
        if (!$taskable) {
            return;
        }

        // Get items based on taskable type (PR or ST)
        $items = $taskable->items()->orderBy('item_order')->get();

        foreach ($items as $index => $item) {
            // Determine item type for polymorphic storage
            $itemType = $task->taskable_type === 'App\\Models\\Modules\\Purchasing\\PurchaseRequest\\PurchaseRequest' 
                ? 'pr_item' 
                : 'st_item';

            // Store item data for display
            $this->completingTaskItems[$index] = [
                'id' => $item->id,
                'type' => $itemType,
                'name' => $item->item_name,
                'quantity' => (float) ($itemType === 'pr_item' ? $item->quantity : $item->quantity),
                'unit' => $item->unit,
                'estimated_unit_price' => (float) ($itemType === 'pr_item' ? $item->unit_price : $item->price),
                'estimated_total_price' => (float) ($itemType === 'pr_item' ? $item->total_price : $item->total),
                'original_supplier' => $itemType === 'pr_item' ? ($item->supplier_name ?? '') : '',
            ];

            // Pre-fill realized values with estimated values (Requirement 2.1, 6.1)
            $this->itemRealizations[$index] = [
                'realized_unit_price' => (float) ($itemType === 'pr_item' ? $item->unit_price : $item->price),
                'realized_total_price' => (float) ($itemType === 'pr_item' ? $item->total_price : $item->total),
                'realized_supplier' => $itemType === 'pr_item' ? ($item->supplier_name ?? '') : '',
            ];
        }
    }

    /**
     * Handle real-time updates when admin edits item values
     * Recalculate item total when unit price changes (Requirement 1.3, 2.2)
     * Recalculate grand total when any item changes (Requirement 2.3)
     */
    public function updateItemRealization($index, $field, $value): void
    {
        if (!isset($this->itemRealizations[$index]) || !isset($this->completingTaskItems[$index])) {
            return;
        }

        // Update the field value
        if ($field === 'realized_unit_price') {
            // Parse numeric value (remove formatting)
            $numericValue = (float) preg_replace('/[^0-9.]/', '', $value);
            $this->itemRealizations[$index]['realized_unit_price'] = $numericValue;
            
            // Recalculate item total: quantity × unit_price (Requirement 1.3)
            $quantity = $this->completingTaskItems[$index]['quantity'];
            $this->itemRealizations[$index]['realized_total_price'] = $quantity * $numericValue;
        } elseif ($field === 'realized_supplier') {
            $this->itemRealizations[$index]['realized_supplier'] = $value;
        }

        // Recalculate grand total (Requirement 2.3)
        $this->calculateGrandTotal();
    }

    /**
     * Calculate item total from quantity and unit price
     * Property 1: Item Total Calculation Consistency
     */
    public function calculateItemTotal($index): float
    {
        if (!isset($this->itemRealizations[$index]) || !isset($this->completingTaskItems[$index])) {
            return 0;
        }

        $quantity = $this->completingTaskItems[$index]['quantity'];
        $unitPrice = $this->itemRealizations[$index]['realized_unit_price'];
        
        return $quantity * $unitPrice;
    }

    /**
     * Calculate grand total from all item realized totals
     * Property 2: Grand Total Calculation Consistency
     */
    public function calculateGrandTotal(): void
    {
        $grandTotal = 0;
        
        foreach ($this->itemRealizations as $index => $realization) {
            $grandTotal += $realization['realized_total_price'];
        }
        
        $this->realizedTotalPrice = $grandTotal;
    }

    public function closeCompleteModal(): void
    {
        $this->showCompleteModal = false;
        $this->completingTaskId = null;
        $this->realizedTotalPrice = '';
        $this->completionNotes = '';
        $this->completingTaskItems = [];
        $this->itemRealizations = [];
        $this->resetValidation();
    }

    /**
     * Complete task with item-level realization data
     * Requirements: 1.4, 1.5, 4.1, 4.2, 5.1, 5.2, 6.2, 6.3
     */
    public function completeTaskWithItems(): void
    {
        // Validate item realizations
        $validationRules = [
            'completionNotes' => 'nullable|string|max:1000',
        ];
        
        // Add validation for each item's realized unit price
        foreach ($this->itemRealizations as $index => $realization) {
            $validationRules["itemRealizations.{$index}.realized_unit_price"] = 'required|numeric|min:0';
        }

        $this->validate($validationRules, [
            'itemRealizations.*.realized_unit_price.required' => 'Realized price is required for all items',
            'itemRealizations.*.realized_unit_price.numeric' => 'Realized price must be a number',
            'itemRealizations.*.realized_unit_price.min' => 'Realized price cannot be negative',
        ]);

        $task = AdminTask::find($this->completingTaskId);

        if (!$task) {
            $this->dispatch('toast', type: 'error', message: 'Task not found');
            $this->closeCompleteModal();
            return;
        }

        if ($task->status !== 'in_progress') {
            $this->dispatch('toast', type: 'error', message: 'Task must be in progress to complete');
            $this->closeCompleteModal();
            return;
        }

        if ($task->assigned_admin_id !== Auth::id()) {
            $this->dispatch('toast', type: 'error', message: 'Task must be assigned to you to complete');
            $this->closeCompleteModal();
            return;
        }

        try {
            DB::beginTransaction();

            // Store item realizations (Requirements 4.1, 4.2, 6.2, 6.3)
            $grandTotalRealized = 0;
            $grandTotalEstimated = 0;

            foreach ($this->completingTaskItems as $index => $item) {
                $realization = $this->itemRealizations[$index];
                
                // Recalculate item total to ensure consistency (Property 1)
                $realizedTotalPrice = $item['quantity'] * $realization['realized_unit_price'];
                
                // Calculate savings per item (Requirements 5.1, 5.2)
                $savingsAmount = $item['estimated_total_price'] - $realizedTotalPrice;
                $savingsPercentage = $item['estimated_total_price'] > 0
                    ? ($savingsAmount / $item['estimated_total_price']) * 100
                    : 0;

                // Create item realization record
                AdminTaskItemRealization::create([
                    'admin_task_id' => $task->id,
                    'item_type' => $item['type'],
                    'item_id' => $item['id'],
                    'item_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'estimated_unit_price' => $item['estimated_unit_price'],
                    'estimated_total_price' => $item['estimated_total_price'],
                    'realized_unit_price' => $realization['realized_unit_price'],
                    'realized_total_price' => $realizedTotalPrice,
                    'savings_amount' => $savingsAmount,
                    'savings_percentage' => $savingsPercentage,
                    'original_supplier' => $item['original_supplier'],
                    'realized_supplier' => $realization['realized_supplier'],
                ]);

                $grandTotalRealized += $realizedTotalPrice;
                $grandTotalEstimated += $item['estimated_total_price'];
            }

            // Calculate task-level metrics
            $completedAt = now();
            $completionTimeMinutes = $task->started_at 
                ? abs($task->started_at->diffInMinutes($completedAt, false)) 
                : 0;
            
            // Calculate grand total savings (Property 2)
            $grandSavingsAmount = $grandTotalEstimated - $grandTotalRealized;
            $grandSavingsPercentage = $grandTotalEstimated > 0
                ? ($grandSavingsAmount / $grandTotalEstimated) * 100
                : 0;

            // Update AdminTask with grand totals (Requirement 1.5)
            $task->update([
                'status' => 'done',
                'completed_at' => $completedAt,
                'completion_time_minutes' => $completionTimeMinutes,
                'realized_total_price' => $grandTotalRealized,
                'savings_amount' => $grandSavingsAmount,
                'savings_percentage' => $grandSavingsPercentage,
                'notes' => $this->completionNotes ?: null,
            ]);

            DB::commit();

            $this->dispatch('toast', type: 'success', message: 'Task completed successfully with item details');
            $this->dispatch('task-completed', taskId: $this->completingTaskId);
            $this->closeCompleteModal();
            $this->resetLazyLoad();

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to complete task with items', [
                'taskId' => $this->completingTaskId,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('toast', type: 'error', message: 'Failed to complete task: ' . $e->getMessage());
        }
    }

    /**
     * Legacy method for backward compatibility
     * Redirects to completeTaskWithItems
     */
    public function completeTask(): void
    {
        $this->completeTaskWithItems();
    }

    public function render()
    {
        return view('livewire.modules.purchasing.admin.task-list')
            ->layout('layouts.app');
    }
}
