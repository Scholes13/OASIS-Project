<?php

namespace App\Livewire\Modules\Purchasing\Admin;

use App\Livewire\Traits\HasLazyLoading;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Models\Core\UserBusinessUnit;
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
    
    // Date range filter
    public $datePreset = 'this_month';
    public $dateFrom;
    public $dateTo;
    
    // User role flags
    public bool $isPurchasingAdmin = false;
    public bool $isManagement = false;
    
    // Business unit IDs for filtering (includes children for parent BU)
    protected array $filteredBusinessUnitIds = [];

    public function mount(): void
    {
        $this->activeBusinessUnitId = session('current_business_unit_id');
        $this->applyDatePreset('this_month');
        $this->checkUserRole();
        $this->loadFilteredBusinessUnitIds();
    }
    
    /**
     * Load business unit IDs to filter by
     * If current BU is a parent, include all child BUs
     */
    protected function loadFilteredBusinessUnitIds(): void
    {
        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        
        if (!$buId) {
            $this->filteredBusinessUnitIds = [];
            return;
        }
        
        $businessUnit = \App\Models\Core\BusinessUnit::with('children')->find($buId);
        
        if (!$businessUnit) {
            $this->filteredBusinessUnitIds = [$buId];
            return;
        }
        
        $ids = [$businessUnit->id];
        
        // If this is a parent business unit, include all children
        if ($businessUnit->children && $businessUnit->children->isNotEmpty()) {
            $ids = array_merge($ids, $businessUnit->children->pluck('id')->toArray());
        }
        
        $this->filteredBusinessUnitIds = $ids;
    }
    
    /**
     * Get filtered business unit IDs
     */
    protected function getFilteredBusinessUnitIds(): array
    {
        if (empty($this->filteredBusinessUnitIds)) {
            $this->loadFilteredBusinessUnitIds();
        }
        return $this->filteredBusinessUnitIds;
    }
    
    /**
     * Check if current user is purchasing admin or management
     * Management (Top Management, Director, CEO, etc.) can only view/monitor
     * Purchasing Admin can claim and work on tasks
     */
    protected function checkUserRole(): void
    {
        $userId = Auth::id();
        $buId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
        
        // Check user's role in current business unit
        $userBu = UserBusinessUnit::where('user_id', $userId)
            ->where('business_unit_id', $buId)
            ->with('position')
            ->first();
        
        if ($userBu) {
            $this->isPurchasingAdmin = (bool) $userBu->is_purchasing_admin;
            
            // Check if user is management based on position
            $managementPositions = ['Top Management', 'Director', 'CEO', 'General Manager', 'Managing Director'];
            $this->isManagement = $userBu->position && in_array($userBu->position->name, $managementPositions);
        }
        
        // Super admin can do everything
        if (Auth::user()->isSuperAdmin()) {
            $this->isPurchasingAdmin = true;
            $this->isManagement = false;
        }
    }
    
    public function updatedDatePreset($value): void
    {
        $this->applyDatePreset($value);
    }
    
    protected function applyDatePreset($preset): void
    {
        $today = now();
        
        switch ($preset) {
            case 'today':
                $this->dateFrom = $today->copy()->startOfDay()->format('Y-m-d');
                $this->dateTo = $today->copy()->endOfDay()->format('Y-m-d');
                break;
            case 'this_week':
                $this->dateFrom = $today->copy()->startOfWeek()->format('Y-m-d');
                $this->dateTo = $today->copy()->endOfWeek()->format('Y-m-d');
                break;
            case 'this_month':
                $this->dateFrom = $today->copy()->startOfMonth()->format('Y-m-d');
                $this->dateTo = $today->copy()->endOfMonth()->format('Y-m-d');
                break;
            case 'last_month':
                $this->dateFrom = $today->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->dateTo = $today->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            case 'this_quarter':
                $this->dateFrom = $today->copy()->firstOfQuarter()->format('Y-m-d');
                $this->dateTo = $today->copy()->lastOfQuarter()->format('Y-m-d');
                break;
            case 'this_year':
                $this->dateFrom = $today->copy()->startOfYear()->format('Y-m-d');
                $this->dateTo = $today->copy()->endOfYear()->format('Y-m-d');
                break;
            case 'all_time':
                $this->dateFrom = null;
                $this->dateTo = null;
                break;
            default:
                $this->dateFrom = $today->copy()->startOfMonth()->format('Y-m-d');
                $this->dateTo = $today->copy()->endOfMonth()->format('Y-m-d');
        }
    }
    
    public function getDateRangeLabel(): string
    {
        if (!$this->dateFrom && !$this->dateTo) {
            return 'All Time';
        }
        
        $from = $this->dateFrom ? \Carbon\Carbon::parse($this->dateFrom)->format('M j, Y') : '';
        $to = $this->dateTo ? \Carbon\Carbon::parse($this->dateTo)->format('M j, Y') : '';
        
        return "Period: {$from} - {$to}";
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
        
        // Re-check user role for new BU
        $this->checkUserRole();
        
        // Reload filtered business unit IDs (for parent BU aggregation)
        $this->filteredBusinessUnitIds = [];
        $this->loadFilteredBusinessUnitIds();

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

        $buIds = $this->getFilteredBusinessUnitIds();
        
        if (empty($buIds)) {
            return collect();
        }
        
        $userId = Auth::id();

        // Get recent tasks (pending + in progress)
        $query = AdminTask::whereIn('business_unit_id', $buIds)
            ->whereIn('status', ['pending_followup', 'in_progress']);
        
        // Management sees all tasks, purchasing admin sees only unassigned or their own
        if (!$this->isManagement) {
            $query->where(function ($q) use ($userId) {
                $q->whereNull('assigned_admin_id')
                    ->orWhere('assigned_admin_id', $userId);
            });
        }
        
        return $query->with(['taskable', 'assignedAdmin', 'department'])
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
        $buIds = $this->getFilteredBusinessUnitIds();
        
        if (empty($buIds)) {
            return 0;
        }

        $query = AdminTask::whereIn('business_unit_id', $buIds)
            ->where('status', 'done');
        
        // For management viewing parent BU, show all completed tasks
        // For purchasing admin, show only their own tasks
        if (!$this->isManagement) {
            $query->where('assigned_admin_id', Auth::id());
        }
            
        if ($this->dateFrom) {
            $query->whereDate('completed_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('completed_at', '<=', $this->dateTo);
        }

        return $query->count();
    }

    #[Computed]
    public function averageFollowupTime()
    {
        $buIds = $this->getFilteredBusinessUnitIds();
        
        if (empty($buIds)) {
            return 0;
        }

        $query = AdminTask::whereIn('business_unit_id', $buIds)
            ->where('status', 'done')
            ->whereNotNull('followup_time_minutes');
        
        // Management sees all tasks, purchasing admin sees only their own
        if (!$this->isManagement) {
            $query->where('assigned_admin_id', Auth::id());
        }
            
        if ($this->dateFrom) {
            $query->whereDate('completed_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('completed_at', '<=', $this->dateTo);
        }

        return $query->avg('followup_time_minutes') ?? 0;
    }

    #[Computed]
    public function averageCompletionTime()
    {
        $buIds = $this->getFilteredBusinessUnitIds();
        
        if (empty($buIds)) {
            return 0;
        }

        $query = AdminTask::whereIn('business_unit_id', $buIds)
            ->where('status', 'done')
            ->whereNotNull('completion_time_minutes');
        
        // Management sees all tasks, purchasing admin sees only their own
        if (!$this->isManagement) {
            $query->where('assigned_admin_id', Auth::id());
        }
            
        if ($this->dateFrom) {
            $query->whereDate('completed_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('completed_at', '<=', $this->dateTo);
        }

        return $query->avg('completion_time_minutes') ?? 0;
    }

    #[Computed]
    public function totalSavings()
    {
        $buIds = $this->getFilteredBusinessUnitIds();
        
        if (empty($buIds)) {
            return 0;
        }

        $query = AdminTask::whereIn('business_unit_id', $buIds)
            ->where('status', 'done');
        
        // Management sees all tasks, purchasing admin sees only their own
        if (!$this->isManagement) {
            $query->where('assigned_admin_id', Auth::id());
        }
            
        if ($this->dateFrom) {
            $query->whereDate('completed_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('completed_at', '<=', $this->dateTo);
        }

        return $query->sum('savings_amount') ?? 0;
    }

    #[Computed]
    public function averageSavingsPercentage()
    {
        $buIds = $this->getFilteredBusinessUnitIds();
        
        if (empty($buIds)) {
            return 0;
        }

        $query = AdminTask::whereIn('business_unit_id', $buIds)
            ->where('status', 'done');
        
        // Management sees all tasks, purchasing admin sees only their own
        if (!$this->isManagement) {
            $query->where('assigned_admin_id', Auth::id());
        }
            
        if ($this->dateFrom) {
            $query->whereDate('completed_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('completed_at', '<=', $this->dateTo);
        }

        return $query->avg('savings_percentage') ?? 0;
    }

    #[Computed]
    public function savingsTrendData()
    {
        $buIds = $this->getFilteredBusinessUnitIds();
        
        if (empty($buIds)) {
            return ['labels' => [], 'data' => []];
        }

        $query = AdminTask::select(
            DB::raw('DATE_FORMAT(completed_at, "%Y-%m") as month'),
            DB::raw('AVG(savings_percentage) as avg_savings')
        )
            ->whereIn('business_unit_id', $buIds)
            ->where('status', 'done')
            ->whereNotNull('completed_at');
        
        // Management sees all tasks, purchasing admin sees only their own
        if (!$this->isManagement) {
            $query->where('assigned_admin_id', Auth::id());
        }
            
        if ($this->dateFrom) {
            $query->whereDate('completed_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('completed_at', '<=', $this->dateTo);
        }

        $savingsByMonth = $query->groupBy('month')
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
        $buIds = $this->getFilteredBusinessUnitIds();
        
        if (empty($buIds)) {
            return collect();
        }

        $query = AdminTask::select('department_id', DB::raw('COUNT(*) as task_count'))
            ->whereIn('business_unit_id', $buIds)
            ->where('status', 'done')
            ->with('department:id,name');
        
        // Management sees all tasks, purchasing admin sees only their own
        if (!$this->isManagement) {
            $query->where('assigned_admin_id', Auth::id());
        }
            
        if ($this->dateFrom) {
            $query->whereDate('completed_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('completed_at', '<=', $this->dateTo);
        }

        $departments = $query->groupBy('department_id')
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
