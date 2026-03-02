<?php

namespace App\Http\Controllers\Modules\Purchasing\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchasingAdminController extends Controller
{
    /**
     * Display the purchasing admin dashboard.
     */
    public function dashboard(Request $request): Response
    {
        $user = auth()->user();
        $buId = session('current_business_unit_id');

        // Base query for this Business Unit
        $baseQuery = AdminTask::where('admin_tasks.business_unit_id', $buId);

        // --- 1. Filter Metrics by Date (Optional, default to this month for 'period' related stats?) ---
        // For general counters (Top cards), usually we show ALL current state.
        // For "Performance" (averages), we usually filter by specific period.

        $datePreset = $request->input('date_preset', 'this_month');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $periodQuery = clone $baseQuery;

        if ($dateFrom && $dateTo) {
            $periodQuery->whereBetween('entered_at', [$dateFrom, $dateTo]);
        } elseif ($datePreset === 'this_month') {
            $periodQuery->whereMonth('entered_at', now()->month)
                ->whereYear('entered_at', now()->year);
        } elseif ($datePreset === 'last_month') {
            $periodQuery->whereMonth('entered_at', now()->subMonth()->month)
                ->whereYear('entered_at', now()->subMonth()->year);
        } elseif ($datePreset === 'this_year') {
            $periodQuery->whereYear('entered_at', now()->year);
        } elseif ($datePreset === 'all_time') {
            // No filter
        }
        // --- 2. Counters (Current State) ---
        // Pending: Unassigned OR Assigned to me (usually pending is pooled)
        $pendingCount = (clone $baseQuery)->where('status', 'pending_followup')->count();
        $inProgressCount = (clone $baseQuery)->where('status', 'in_progress')->where('assigned_admin_id', $user->id)->count();
        $doneCount = (clone $baseQuery)->where('status', 'done')->where('assigned_admin_id', $user->id)->count(); // Total completed by me ever? or in period?

        // Usually dashboard "Tasks Completed" metric is period based.
        // "Completed" top card might be "Total Completed ever" or "This Month".
        // Let's make Top Card "Completed" be "Total Completed by me ever" to match the label "Your Tasks".

        // --- 3. Performance Metrics (Period based, User based) ---
        // For performance, we usually look at tasks assigned to the CURRENT USER
        $performanceQuery = (clone $periodQuery)
            ->where('assigned_admin_id', $user->id)
            ->where('status', 'done');

        $metrics = $performanceQuery->selectRaw('
            COUNT(*) as total_tasks_completed,
            AVG(followup_time_minutes) as avg_followup_time,
            AVG(completion_time_minutes) as avg_completion_time,
            SUM(savings_amount) as total_savings,
            AVG(savings_percentage) as avg_savings_percentage
        ')->first();

        // Provide defaults if no metrics found
        $defaultMetrics = (object) [
            'total_tasks_completed' => 0,
            'avg_followup_time' => 0,
            'avg_completion_time' => 0,
            'total_savings' => 0,
            'avg_savings_percentage' => 0,
        ];

        $metrics = $metrics ?? $defaultMetrics;

        // --- 4. Savings Trend (Last 6 months, User based) ---
        $trendStart = now()->subMonths(5)->startOfMonth();
        $savingsTrendData = AdminTask::where('business_unit_id', $buId)
            ->where('assigned_admin_id', $user->id)
            ->where('status', 'done')
            ->where('completed_at', '>=', $trendStart)
            ->selectRaw('DATE_FORMAT(completed_at, "%Y-%m") as month, SUM(savings_amount) as total_savings')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $trendLabels = [];
        $trendData = [];
        // Fill gaps
        $current = $trendStart->copy();
        while ($current <= now()->endOfMonth()) {
            $monthKey = $current->format('Y-m');
            $trendLabels[] = $current->format('M Y');
            $record = $savingsTrendData->firstWhere('month', $monthKey);
            $trendData[] = $record ? (float) $record->total_savings : 0;
            $current->addMonth();
        }

        // --- 5. Department Breakdown (Period based, Business Unit wide) ---
        // This usually shows where usage is coming from
        $deptBreakdownRaw = (clone $periodQuery)
            ->join('departments', 'admin_tasks.department_id', '=', 'departments.id')
            ->selectRaw('departments.name as department_name, COUNT(*) as task_count')
            ->groupBy('departments.name')
            ->orderByDesc('task_count')
            ->limit(5)
            ->get();

        $totalDeptTasks = $deptBreakdownRaw->sum('task_count');
        $deptBreakdown = $deptBreakdownRaw->map(function ($item) use ($totalDeptTasks) {
            return [
                'department' => $item->department_name,
                'count' => $item->task_count,
                'percentage' => $totalDeptTasks > 0 ? round(($item->task_count / $totalDeptTasks) * 100, 1) : 0,
            ];
        });

        // --- 6. Recent Tasks (User based) ---
        $recentTasks = AdminTask::with(['taskable', 'department'])
            ->where('business_unit_id', $buId)
            ->where('assigned_admin_id', $user->id) // Only my tasks
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('PurchasingAdmin/Dashboard', [
            'stats' => [
                'pending' => $pendingCount,
                'in_progress' => $inProgressCount,
                'done' => $doneCount, // Total ever
            ],
            'recentTasks' => $recentTasks,
            'metrics' => [
                'total_tasks_completed' => (int) $metrics->total_tasks_completed,
                'avg_followup_time' => (float) $metrics->avg_followup_time,
                'avg_completion_time' => (float) $metrics->avg_completion_time,
                'total_savings' => (float) $metrics->total_savings,
                'avg_savings_percentage' => (float) $metrics->avg_savings_percentage,
            ],
            'savingsTrend' => [
                'labels' => $trendLabels,
                'data' => $trendData,
            ],
            'departmentBreakdown' => $deptBreakdown,
            'datePreset' => $datePreset,
            'dateRange' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'userRole' => [
                'is_purchasing_admin' => true, // TODO: Check actual role/permission
                'is_management' => false,
            ],
        ]);
    }

    /**
     * Display tasks list.
     */
    public function tasks(Request $request): Response
    {
        $user = auth()->user();
        $buId = session('current_business_unit_id');

        $filters = [
            'status' => $request->input('status', 'pending'),
            'type' => $request->input('type', ''),
            'date' => $request->input('date', 'all'),
            'search' => $request->input('search', ''),
        ];

        // Base query
        $query = AdminTask::with(['taskable', 'assignedAdmin:id,name', 'department:id,name'])
            ->where('business_unit_id', $buId);

        // Apply Tab/Status Filter
        switch ($filters['status']) {
            case 'pending':
                $query->where('status', 'pending_followup')
                    ->where(function ($q) use ($user) {
                        $q->whereNull('assigned_admin_id')
                            ->orWhere('assigned_admin_id', $user->id);
                    });
                break;
            case 'in_progress':
                $query->where('status', 'in_progress')
                    ->where('assigned_admin_id', $user->id);
                break;
            case 'completed':
                $query->where('status', 'done')
                    ->where('assigned_admin_id', $user->id);
                break;
        }

        // Apply Date Filter
        if ($filters['date'] !== 'all') {
            $dateQuery = match ($filters['date']) {
                'today' => now()->startOfDay(),
                'last_30_days' => now()->subDays(30)->startOfDay(),
                default => null,
            };

            if ($dateQuery) {
                $query->where('entered_at', '>=', $dateQuery);
            }
        }

        // Apply Type Filter
        if (! empty($filters['type'])) {
            // Map simple type to full class name if needed, or assume frontend sends partial or full
            // Frontend sends 'purchase_request' or 'stock_request'
            $typeMap = [
                'purchase_request' => 'App\\Models\\Modules\\Purchasing\\PurchaseRequest\\PurchaseRequest',
                'stock_request' => 'App\\Models\\Modules\\Inventory\\StockRequest\\StockRequest', // Verify namespace if needed
            ];

            if (isset($typeMap[$filters['type']])) {
                $query->where('taskable_type', $typeMap[$filters['type']]);
            }
        }

        // Apply Search
        if (! empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->whereHas('taskable', function ($q) use ($searchTerm) {
                // Check if taskable has pr_number or st_number
                // polymorphic inner query is tricky if columns differ, but usually we check one or the other
                // simpler is to check if model has column before querying, but in whereHas we are inside builder
                // Assuming taskable interface or similar columns.
                // Based on TaskList.php:
                $q->where(function ($sub) use ($searchTerm) {
                    // We need to know which table we are querying or use specific logic
                    // Livewire TaskList uses:
                    // $q->where('pr_number', 'like', "%{$searchTerm}%")->orWhere('st_number', 'like', "%{$searchTerm}%");
                    // This assumes the underlying model has these columns.
                    // Since taskable connects to PR or ST, and both might not have both columns, this can fail if not careful.
                    // But Laravel's whereHas handles the type check implicitly on the relationship? No.
                    // WhereHas iterates the relationship.
                    // Safe way: try-catch or assume models are consistent.
                    // Let's stick to TaskList.php logic for now.
                    try {
                        $sub->where('pr_number', 'like', "%{$searchTerm}%");
                    } catch (\Exception $e) {
                        // ignore
                    }
                    try {
                        $sub->orWhere('st_number', 'like', "%{$searchTerm}%");
                    } catch (\Exception $e) {
                        // ignore
                    }
                });
            });
        }

        // Get Stats/Counts
        // We need to query separate basics to get counts for tabs regardless of current tab selection
        $statsQuery = AdminTask::where('business_unit_id', $buId);
        $pendingCount = (clone $statsQuery)->where('status', 'pending_followup')
            ->where(function ($q) use ($user) {
                $q->whereNull('assigned_admin_id')->orWhere('assigned_admin_id', $user->id);
            })->count();
        $inProgressCount = (clone $statsQuery)->where('status', 'in_progress')
            ->where('assigned_admin_id', $user->id)->count();
        $completedCount = (clone $statsQuery)->where('status', 'done')
            ->where('assigned_admin_id', $user->id)->count();

        $tasks = $query->orderBy('entered_at', 'desc')->paginate(10)->withQueryString();

        // Get all tasks for Board/Calendar/Timeline views (no status filter, no pagination)
        $allTasksQuery = AdminTask::with(['taskable', 'department', 'assignedAdmin'])
            ->where('business_unit_id', $buId);

        // Apply type filter if set
        if (! empty($filters['type'])) {
            $allTasksQuery->where('taskable_type', 'like', '%'.ucfirst(str_replace('_', '', $filters['type'])).'%');
        }

        // Apply date filter if set
        if (! empty($filters['date']) && $filters['date'] !== 'all') {
            if ($filters['date'] === 'today') {
                $allTasksQuery->whereDate('entered_at', now()->toDateString());
            } elseif ($filters['date'] === 'last_30_days') {
                $allTasksQuery->where('entered_at', '>=', now()->subDays(30));
            }
        }

        // Apply search filter if set
        if (! empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $allTasksQuery->whereHas('taskable', function ($sub) use ($searchTerm) {
                $sub->where(function ($q) use ($searchTerm) {
                    $q->where('pr_number', 'like', "%{$searchTerm}%")
                        ->orWhere('st_number', 'like', "%{$searchTerm}%");
                });
            });
        }

        $allTasks = $allTasksQuery->orderBy('entered_at', 'desc')->get();

        return Inertia::render('PurchasingAdmin/Tasks', [
            'tasks' => $tasks,
            'allTasks' => $allTasks,
            'filters' => $filters,
            'counts' => [
                'pending' => $pendingCount,
                'in_progress' => $inProgressCount,
                'completed' => $completedCount,
            ],
        ]);
    }

    /**
     * Display task detail.
     */
    public function taskDetail(Request $request, $taskId): Response
    {
        $task = AdminTask::with([
            'taskable',
            'department',
            'businessUnit',
            'assignedAdmin',
        ])->findOrFail($taskId);

        // Check business unit access
        if ($task->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this task.');
        }

        return Inertia::render('PurchasingAdmin/TaskDetail', [
            'task' => $task,
        ]);
    }

    /**
     * Claim a task.
     */
    public function claimTask(Request $request, $taskId)
    {
        $task = AdminTask::findOrFail($taskId);

        // Check if task is already assigned
        if ($task->assigned_admin_id !== null) {
            return back()->with('error', 'Task is already assigned');
        }

        // Check business unit access
        if ($task->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this task.');
        }

        // Assign task to current user
        $task->update([
            'assigned_admin_id' => auth()->id(),
        ]);

        return back()->with('success', 'Task claimed successfully');
    }

    /**
     * Start a task.
     */
    public function startTask(Request $request, $taskId)
    {
        $task = AdminTask::findOrFail($taskId);

        // Check if task is assigned to current user
        if ($task->assigned_admin_id !== auth()->id()) {
            return back()->with('error', 'Task is not assigned to you');
        }

        // Check if task is in pending status
        if ($task->status !== 'pending_followup') {
            return back()->with('error', 'Task is not in pending status');
        }

        // Check business unit access
        if ($task->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this task.');
        }

        // Start the task
        $startedAt = now();
        $followupTimeMinutes = abs($task->entered_at->diffInMinutes($startedAt));

        $task->update([
            'status' => 'in_progress',
            'started_at' => $startedAt,
            'followup_time_minutes' => $followupTimeMinutes,
        ]);

        return back()->with('success', 'Task started successfully');
    }

    /**
     * Update task status (for drag-and-drop).
     */
    public function updateTaskStatus(Request $request, $taskId)
    {
        $request->validate([
            'status' => 'required|in:pending_followup,in_progress,done',
        ]);

        $task = AdminTask::findOrFail($taskId);

        // Check business unit access
        if ($task->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this task.');
        }

        $newStatus = $request->input('status');
        $updateData = ['status' => $newStatus];

        // If moving to in_progress and not started
        if ($newStatus === 'in_progress' && ! $task->started_at) {
            $updateData['started_at'] = now();
            $updateData['followup_time_minutes'] = abs($task->entered_at->diffInMinutes(now()));

            // Auto-assign to current user if not assigned
            if (! $task->assigned_admin_id) {
                $updateData['assigned_admin_id'] = auth()->id();
            }
        }

        // If moving to done
        if ($newStatus === 'done' && ! $task->completed_at) {
            $updateData['completed_at'] = now();
            if ($task->started_at) {
                $updateData['completion_time_minutes'] = abs($task->started_at->diffInMinutes(now()));
            }
        }

        $task->update($updateData);

        return back()->with('success', 'Task status updated');
    }

    /**
     * Complete a task with realization data.
     */
    public function completeTask(Request $request, $taskId)
    {
        $request->validate([
            'realized_total_price' => 'required|numeric|min:1',
            'vendor_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $task = AdminTask::with('taskable')->findOrFail($taskId);

        // Check if task is assigned to current user
        if ($task->assigned_admin_id !== auth()->id()) {
            return back()->with('error', 'Task is not assigned to you');
        }

        // Check if task is in progress
        if ($task->status !== 'in_progress') {
            return back()->with('error', 'Task must be in progress to complete');
        }

        // Check business unit access
        if ($task->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this task.');
        }

        $realizedPrice = $request->input('realized_total_price');
        $vendorName = $request->input('vendor_name');
        $estimatedPrice = $task->estimated_total_price;
        $savingsAmount = $estimatedPrice - $realizedPrice;
        $savingsPercentage = $estimatedPrice > 0 ? ($savingsAmount / $estimatedPrice) * 100 : 0;

        $completedAt = now();
        $completionTimeMinutes = $task->started_at ? abs($task->started_at->diffInMinutes($completedAt)) : 0;

        $task->update([
            'status' => 'done',
            'realized_total_price' => $realizedPrice,
            'savings_amount' => $savingsAmount,
            'savings_percentage' => $savingsPercentage,
            'completed_at' => $completedAt,
            'completion_time_minutes' => $completionTimeMinutes,
            'notes' => $request->input('notes'),
        ]);

        // Update vendor/supplier on the purchase request if provided
        if ($vendorName && $task->taskable) {
            if (str_contains($task->taskable_type, 'PurchaseRequest')) {
                $task->taskable->update([
                    'supplier' => $vendorName,
                ]);
            }
        }

        return redirect()->route('purchasing.admin.tasks')->with('success', 'Task completed successfully');
    }

    /**
     * Display personal task history.
     */
    public function taskHistory(Request $request): Response
    {
        $user = auth()->user();

        // Filters
        $filters = [
            'date_from' => $request->get('date_from', now()->startOfMonth()->format('Y-m-d')),
            'date_to' => $request->get('date_to', ''),
            'status' => $request->get('status', 'all'),
            'type' => $request->get('type', 'all'),
        ];

        // Build query
        $query = AdminTask::with(['taskable', 'businessUnit', 'department'])
            ->where('assigned_admin_id', $user->id)
            ->orderBy('entered_at', 'desc');

        // Date range filter
        if (! empty($filters['date_from'])) {
            $query->whereDate('entered_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('entered_at', '<=', $filters['date_to']);
        }

        // Status filter
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Type filter
        if ($filters['type'] !== 'all') {
            if ($filters['type'] === 'purchase_request') {
                $query->where('taskable_type', 'like', '%PurchaseRequest%');
            } elseif ($filters['type'] === 'stock_request') {
                $query->where('taskable_type', 'like', '%StockRequest%');
            }
        }

        $tasks = $query->paginate(10)->withQueryString();

        // Get statistics (only from completed tasks)
        $statsQuery = AdminTask::where('assigned_admin_id', $user->id)
            ->where('status', 'done');

        if (! empty($filters['date_from'])) {
            $statsQuery->whereDate('entered_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $statsQuery->whereDate('entered_at', '<=', $filters['date_to']);
        }

        $statistics = $statsQuery->selectRaw('
            COUNT(*) as total_completed,
            AVG(followup_time_minutes) as avg_followup_time,
            AVG(completion_time_minutes) as avg_completion_time,
            SUM(savings_amount) as total_savings,
            AVG(savings_percentage) as avg_savings_percentage
        ')->first()->toArray();

        return Inertia::render('PurchasingAdmin/TaskHistory', [
            'tasks' => $tasks,
            'statistics' => $statistics,
            'filters' => $filters,
        ]);
    }

    /**
     * Export task history to CSV/Excel.
     */
    public function exportTaskHistory(Request $request)
    {
        $user = auth()->user();
        $format = $request->get('format', 'csv');

        // Build query with same filters
        $query = AdminTask::with(['taskable', 'businessUnit', 'department'])
            ->where('assigned_admin_id', $user->id)
            ->orderBy('entered_at', 'desc');

        if (! empty($request->get('date_from'))) {
            $query->whereDate('entered_at', '>=', $request->get('date_from'));
        }
        if (! empty($request->get('date_to'))) {
            $query->whereDate('entered_at', '<=', $request->get('date_to'));
        }
        if ($request->get('status', 'all') !== 'all') {
            $query->where('status', $request->get('status'));
        }
        if ($request->get('type', 'all') !== 'all') {
            if ($request->get('type') === 'purchase_request') {
                $query->where('taskable_type', 'like', '%PurchaseRequest%');
            } elseif ($request->get('type') === 'stock_request') {
                $query->where('taskable_type', 'like', '%StockRequest%');
            }
        }

        $tasks = $query->get();
        $filename = 'task-history-'.$user->name.'-'.now()->format('Y-m-d').'.'.($format === 'excel' ? 'xls' : 'csv');

        return response()->streamDownload(function () use ($tasks) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header
            fputcsv($handle, [
                'Document',
                'Type',
                'Business Unit',
                'Status',
                'Entered At',
                'Follow-up Time (min)',
                'Completion Time (min)',
                'Estimated Price',
                'Realized Price',
                'Savings Amount',
                'Savings %',
            ]);

            // Data
            foreach ($tasks as $task) {
                $docNumber = $task->taskable->pr_number ?? $task->taskable->st_number ?? 'N/A';
                $type = str_contains($task->taskable_type, 'PurchaseRequest') ? 'PR' : 'ST';
                $status = match ($task->status) {
                    'pending_followup' => 'Pending',
                    'in_progress' => 'In Progress',
                    'done' => 'Completed',
                    default => $task->status,
                };

                fputcsv($handle, [
                    $docNumber,
                    $type,
                    $task->businessUnit->name ?? 'N/A',
                    $status,
                    $task->entered_at?->format('Y-m-d H:i'),
                    $task->followup_time_minutes,
                    $task->completion_time_minutes,
                    $task->estimated_total_price,
                    $task->realized_total_price,
                    $task->savings_amount,
                    $task->savings_percentage,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => $format === 'excel' ? 'application/vnd.ms-excel' : 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Display department report.
     */
    public function departmentReport(Request $request): Response
    {
        $user = auth()->user();
        $buId = session('current_business_unit_id');

        // Get user's department in current BU
        $userBu = \App\Models\Core\UserBusinessUnit::where('user_id', $user->id)
            ->where('business_unit_id', $buId)
            ->first();

        $departmentId = $userBu?->department_id;
        $department = $departmentId ? \App\Models\Core\Department::find($departmentId) : null;

        if (! $departmentId) {
            return Inertia::render('PurchasingAdmin/DepartmentReport', [
                'department' => null,
                'totalSavings' => 0,
                'averageFollowupTime' => 0,
                'averageCompletionTime' => 0,
                'totalTasksCompleted' => 0,
                'savingsBreakdown' => ['purchase_request' => 0, 'stock_request' => 0],
                'adminPerformance' => [],
                'departmentTrendData' => ['labels' => [], 'data' => []],
            ]);
        }

        // Get statistics
        $stats = AdminTask::where('business_unit_id', $buId)
            ->where('department_id', $departmentId)
            ->where('status', 'done')
            ->selectRaw('
                SUM(savings_amount) as total_savings,
                AVG(followup_time_minutes) as avg_followup_time,
                AVG(completion_time_minutes) as avg_completion_time,
                COUNT(*) as total_completed
            ')
            ->first();

        // Savings breakdown by type
        $prSavings = AdminTask::where('business_unit_id', $buId)
            ->where('department_id', $departmentId)
            ->where('status', 'done')
            ->where('taskable_type', 'like', '%PurchaseRequest%')
            ->sum('savings_amount') ?? 0;

        $stSavings = AdminTask::where('business_unit_id', $buId)
            ->where('department_id', $departmentId)
            ->where('status', 'done')
            ->where('taskable_type', 'like', '%StockRequest%')
            ->sum('savings_amount') ?? 0;

        // Admin performance
        $admins = \App\Models\Core\UserBusinessUnit::with('user')
            ->where('business_unit_id', $buId)
            ->where('department_id', $departmentId)
            ->where('is_purchasing_admin', true)
            ->get();

        $adminPerformance = $admins->map(function ($userBu) use ($buId) {
            $userId = $userBu->user_id;
            $adminStats = AdminTask::where('business_unit_id', $buId)
                ->where('assigned_admin_id', $userId)
                ->where('status', 'done')
                ->selectRaw('
                    COUNT(*) as tasks_completed,
                    SUM(savings_amount) as total_savings,
                    AVG(savings_percentage) as avg_savings_percentage,
                    AVG(followup_time_minutes) as avg_followup_time,
                    AVG(completion_time_minutes) as avg_completion_time
                ')
                ->first();

            return [
                'name' => $userBu->user->name,
                'tasks_completed' => (int) ($adminStats->tasks_completed ?? 0),
                'total_savings' => (float) ($adminStats->total_savings ?? 0),
                'avg_savings_percentage' => (float) ($adminStats->avg_savings_percentage ?? 0),
                'avg_followup_time' => (float) ($adminStats->avg_followup_time ?? 0),
                'avg_completion_time' => (float) ($adminStats->avg_completion_time ?? 0),
            ];
        })->sortByDesc('total_savings')->values()->toArray();

        // Trend data (last 12 months)
        $trendData = AdminTask::where('business_unit_id', $buId)
            ->where('department_id', $departmentId)
            ->where('status', 'done')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subMonths(12))
            ->selectRaw('DATE_FORMAT(completed_at, "%Y-%m") as month, SUM(savings_amount) as total_savings')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $labels = [];
        $data = [];
        foreach ($trendData as $item) {
            $date = \Carbon\Carbon::createFromFormat('Y-m', $item->month);
            $labels[] = $date->format('M Y');
            $data[] = round($item->total_savings, 2);
        }

        return Inertia::render('PurchasingAdmin/DepartmentReport', [
            'department' => $department,
            'totalSavings' => (float) ($stats->total_savings ?? 0),
            'averageFollowupTime' => (float) ($stats->avg_followup_time ?? 0),
            'averageCompletionTime' => (float) ($stats->avg_completion_time ?? 0),
            'totalTasksCompleted' => (int) ($stats->total_completed ?? 0),
            'savingsBreakdown' => [
                'purchase_request' => (float) $prSavings,
                'stock_request' => (float) $stSavings,
            ],
            'adminPerformance' => $adminPerformance,
            'departmentTrendData' => ['labels' => $labels, 'data' => $data],
        ]);
    }

    /**
     * Display consolidated report.
     */
    public function consolidatedReport(Request $request): Response
    {
        $buId = session('current_business_unit_id');
        $currentBu = \App\Models\Core\BusinessUnit::find($buId);

        // Only show for parent BUs
        $childBUs = collect();
        if ($currentBu && $currentBu->parent_id === null) {
            $childBUs = \App\Models\Core\BusinessUnit::where('parent_id', $currentBu->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        if ($childBUs->isEmpty()) {
            return Inertia::render('PurchasingAdmin/ConsolidatedReport', [
                'childBusinessUnits' => [],
                'businessUnitMetrics' => [],
                'overallMetrics' => [
                    'total_tasks' => 0,
                    'total_savings' => 0,
                    'avg_savings_percentage' => 0,
                    'avg_followup_time' => 0,
                    'avg_completion_time' => 0,
                ],
                'comparativeTrendData' => ['labels' => [], 'datasets' => []],
            ]);
        }

        $buIds = $childBUs->pluck('id')->toArray();

        // Business unit metrics
        $businessUnitMetrics = $childBUs->map(function ($bu) {
            $stats = AdminTask::where('business_unit_id', $bu->id)
                ->where('status', 'done')
                ->selectRaw('
                    COUNT(*) as total_tasks,
                    SUM(savings_amount) as total_savings,
                    AVG(savings_percentage) as avg_savings_percentage,
                    AVG(followup_time_minutes) as avg_followup_time,
                    AVG(completion_time_minutes) as avg_completion_time
                ')
                ->first();

            return [
                'id' => $bu->id,
                'code' => $bu->code,
                'name' => $bu->name,
                'total_tasks' => (int) ($stats->total_tasks ?? 0),
                'total_savings' => (float) ($stats->total_savings ?? 0),
                'avg_savings_percentage' => (float) ($stats->avg_savings_percentage ?? 0),
                'avg_followup_time' => (float) ($stats->avg_followup_time ?? 0),
                'avg_completion_time' => (float) ($stats->avg_completion_time ?? 0),
            ];
        })->sortByDesc('total_savings')->values()->toArray();

        // Overall metrics
        $overallStats = AdminTask::whereIn('business_unit_id', $buIds)
            ->where('status', 'done')
            ->selectRaw('
                COUNT(*) as total_tasks,
                SUM(savings_amount) as total_savings,
                AVG(savings_percentage) as avg_savings_percentage,
                AVG(followup_time_minutes) as avg_followup_time,
                AVG(completion_time_minutes) as avg_completion_time
            ')
            ->first();

        // Comparative trend data
        $trendData = AdminTask::whereIn('business_unit_id', $buIds)
            ->where('status', 'done')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subMonths(12))
            ->selectRaw('business_unit_id, DATE_FORMAT(completed_at, "%Y-%m") as month, SUM(savings_amount) as total_savings')
            ->groupBy('business_unit_id', 'month')
            ->orderBy('month')
            ->get();

        $months = $trendData->pluck('month')->unique()->sort()->values();
        $labels = $months->map(fn ($m) => \Carbon\Carbon::createFromFormat('Y-m', $m)->format('M Y'))->toArray();

        $colors = [
            ['border' => 'rgb(99, 102, 241)', 'bg' => 'rgba(99, 102, 241, 0.1)'],
            ['border' => 'rgb(16, 185, 129)', 'bg' => 'rgba(16, 185, 129, 0.1)'],
            ['border' => 'rgb(59, 130, 246)', 'bg' => 'rgba(59, 130, 246, 0.1)'],
            ['border' => 'rgb(245, 158, 11)', 'bg' => 'rgba(245, 158, 11, 0.1)'],
            ['border' => 'rgb(239, 68, 68)', 'bg' => 'rgba(239, 68, 68, 0.1)'],
        ];

        $datasets = $childBUs->values()->map(function ($bu, $index) use ($trendData, $months, $colors) {
            $data = $months->map(function ($month) use ($bu, $trendData) {
                $record = $trendData->where('business_unit_id', $bu->id)->where('month', $month)->first();

                return $record ? round($record->total_savings, 2) : 0;
            })->toArray();

            $color = $colors[$index % count($colors)];

            return [
                'label' => $bu->name,
                'data' => $data,
                'borderColor' => $color['border'],
                'backgroundColor' => $color['bg'],
            ];
        })->toArray();

        return Inertia::render('PurchasingAdmin/ConsolidatedReport', [
            'childBusinessUnits' => $childBUs->map(fn ($bu) => ['id' => $bu->id, 'code' => $bu->code, 'name' => $bu->name])->toArray(),
            'businessUnitMetrics' => $businessUnitMetrics,
            'overallMetrics' => [
                'total_tasks' => (int) ($overallStats->total_tasks ?? 0),
                'total_savings' => (float) ($overallStats->total_savings ?? 0),
                'avg_savings_percentage' => (float) ($overallStats->avg_savings_percentage ?? 0),
                'avg_followup_time' => (float) ($overallStats->avg_followup_time ?? 0),
                'avg_completion_time' => (float) ($overallStats->avg_completion_time ?? 0),
            ],
            'comparativeTrendData' => ['labels' => $labels, 'datasets' => $datasets],
        ]);
    }

    /**
     * Display audit history (Super Admin).
     */
    public function auditHistory(Request $request): Response
    {
        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)->format('Y-m-d')),
            'date_to' => $request->get('date_to', now()->format('Y-m-d')),
            'status' => $request->get('status', 'all'),
            'type' => $request->get('type', 'all'),
            'admin' => $request->get('admin', 'all'),
            'department' => $request->get('department', 'all'),
        ];

        $query = AdminTask::with(['taskable', 'businessUnit', 'department', 'assignedAdmin'])
            ->orderBy('entered_at', 'desc');

        // Date filters
        if ($filters['date_from']) {
            $query->whereDate('entered_at', '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $query->whereDate('entered_at', '<=', $filters['date_to']);
        }

        // Status filter
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Type filter
        if ($filters['type'] !== 'all') {
            if ($filters['type'] === 'purchase_request') {
                $query->where('taskable_type', 'like', '%PurchaseRequest%');
            } elseif ($filters['type'] === 'stock_request') {
                $query->where('taskable_type', 'like', '%StockRequest%');
            }
        }

        // Admin filter
        if ($filters['admin'] !== 'all') {
            $query->where('assigned_admin_id', $filters['admin']);
        }

        // Department filter
        if ($filters['department'] !== 'all') {
            $query->where('department_id', $filters['department']);
        }

        $tasks = $query->paginate(20)->withQueryString();

        // Get admins list
        $admins = \App\Models\Core\User::whereHas('businessUnits', function ($q) {
            $q->where('is_purchasing_admin', true);
        })->orderBy('name')->get(['id', 'name']);

        // Get departments list
        $departments = \App\Models\Core\Department::where('is_purchasing_department', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('PurchasingAdmin/AuditHistory', [
            'tasks' => $tasks,
            'admins' => $admins,
            'departments' => $departments,
            'filters' => $filters,
        ]);
    }

    /**
     * Display department audit history (Department Manager).
     */
    public function departmentAuditHistory(Request $request): Response
    {
        $user = auth()->user();
        $buId = session('current_business_unit_id');

        // Get user's department
        $userBu = \App\Models\Core\UserBusinessUnit::where('user_id', $user->id)
            ->where('business_unit_id', $buId)
            ->first();

        $departmentId = $userBu?->department_id;

        $filters = [
            'date_from' => $request->get('date_from', now()->subDays(30)->format('Y-m-d')),
            'date_to' => $request->get('date_to', now()->format('Y-m-d')),
            'status' => $request->get('status', 'all'),
            'type' => $request->get('type', 'all'),
            'admin' => $request->get('admin', 'all'),
        ];

        if (! $departmentId) {
            return Inertia::render('PurchasingAdmin/DepartmentAuditHistory', [
                'tasks' => ['data' => [], 'links' => [], 'current_page' => 1, 'last_page' => 1, 'total' => 0, 'from' => 0, 'to' => 0],
                'admins' => [],
                'filters' => $filters,
            ]);
        }

        $query = AdminTask::with(['taskable', 'businessUnit', 'department', 'assignedAdmin'])
            ->where('department_id', $departmentId)
            ->orderBy('entered_at', 'desc');

        // Date filters
        if ($filters['date_from']) {
            $query->whereDate('entered_at', '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $query->whereDate('entered_at', '<=', $filters['date_to']);
        }

        // Status filter
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Type filter
        if ($filters['type'] !== 'all') {
            if ($filters['type'] === 'purchase_request') {
                $query->where('taskable_type', 'like', '%PurchaseRequest%');
            } elseif ($filters['type'] === 'stock_request') {
                $query->where('taskable_type', 'like', '%StockRequest%');
            }
        }

        // Admin filter
        if ($filters['admin'] !== 'all') {
            $query->where('assigned_admin_id', $filters['admin']);
        }

        $tasks = $query->paginate(20)->withQueryString();

        // Get admins in this department
        $admins = \App\Models\Core\User::whereHas('businessUnits', function ($q) use ($departmentId) {
            $q->where('is_purchasing_admin', true)
                ->where('department_id', $departmentId);
        })->orderBy('name')->get(['id', 'name']);

        return Inertia::render('PurchasingAdmin/DepartmentAuditHistory', [
            'tasks' => $tasks,
            'admins' => $admins,
            'filters' => $filters,
        ]);
    }

    /**
     * Display personal task history (Purchasing Admin).
     */
    public function personalTaskHistory(Request $request): Response
    {
        $user = auth()->user();

        $filters = [
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
            'status' => $request->get('status', 'all'),
            'type' => $request->get('type', 'all'),
        ];

        $query = AdminTask::with(['taskable', 'businessUnit', 'department'])
            ->where('assigned_admin_id', $user->id)
            ->orderBy('entered_at', 'desc');

        // Date filters
        if (! empty($filters['date_from'])) {
            $query->whereDate('entered_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('entered_at', '<=', $filters['date_to']);
        }

        // Status filter
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Type filter
        if ($filters['type'] !== 'all') {
            if ($filters['type'] === 'purchase_request') {
                $query->where('taskable_type', 'like', '%PurchaseRequest%');
            } elseif ($filters['type'] === 'stock_request') {
                $query->where('taskable_type', 'like', '%StockRequest%');
            }
        }

        $tasks = $query->paginate(10)->withQueryString();

        // Statistics
        $statsQuery = AdminTask::where('assigned_admin_id', $user->id)
            ->where('status', 'done');

        if (! empty($filters['date_from'])) {
            $statsQuery->whereDate('entered_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $statsQuery->whereDate('entered_at', '<=', $filters['date_to']);
        }

        $statistics = $statsQuery->selectRaw('
            COUNT(*) as total_completed,
            AVG(followup_time_minutes) as avg_followup_time,
            AVG(completion_time_minutes) as avg_completion_time,
            SUM(savings_amount) as total_savings,
            AVG(savings_percentage) as avg_savings_percentage
        ')->first();

        return Inertia::render('PurchasingAdmin/PersonalTaskHistory', [
            'tasks' => $tasks,
            'statistics' => [
                'total_completed' => (int) ($statistics->total_completed ?? 0),
                'avg_followup_time' => (float) ($statistics->avg_followup_time ?? 0),
                'avg_completion_time' => (float) ($statistics->avg_completion_time ?? 0),
                'total_savings' => (float) ($statistics->total_savings ?? 0),
                'avg_savings_percentage' => (float) ($statistics->avg_savings_percentage ?? 0),
            ],
            'filters' => $filters,
        ]);
    }

    /**
     * Display management history.
     */
    public function managementHistory(Request $request): Response
    {
        $buId = session('current_business_unit_id');
        $businessUnit = \App\Models\Core\BusinessUnit::with('children')->find($buId);

        // Get BU IDs (include children if parent)
        $buIds = [$buId];
        if ($businessUnit && $businessUnit->children->isNotEmpty()) {
            $buIds = array_merge($buIds, $businessUnit->children->pluck('id')->toArray());
        }

        $filters = [
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
            'status' => $request->get('status', 'all'),
            'type' => $request->get('type', 'all'),
            'admin' => $request->get('admin', 'all'),
        ];

        $query = AdminTask::with(['taskable', 'businessUnit', 'department', 'assignedAdmin'])
            ->whereIn('business_unit_id', $buIds)
            ->whereNotNull('assigned_admin_id')
            ->orderBy('entered_at', 'desc');

        // Date filters
        if (! empty($filters['date_from'])) {
            $query->whereDate('entered_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('entered_at', '<=', $filters['date_to']);
        }

        // Status filter
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Type filter
        if ($filters['type'] !== 'all') {
            if ($filters['type'] === 'purchase_request') {
                $query->where('taskable_type', 'like', '%PurchaseRequest%');
            } elseif ($filters['type'] === 'stock_request') {
                $query->where('taskable_type', 'like', '%StockRequest%');
            }
        }

        // Admin filter
        if ($filters['admin'] !== 'all') {
            $query->where('assigned_admin_id', $filters['admin']);
        }

        $tasks = $query->paginate(10)->withQueryString();

        // Statistics
        $statsQuery = AdminTask::whereIn('business_unit_id', $buIds)
            ->where('status', 'done');

        if (! empty($filters['date_from'])) {
            $statsQuery->whereDate('entered_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $statsQuery->whereDate('entered_at', '<=', $filters['date_to']);
        }
        if ($filters['admin'] !== 'all') {
            $statsQuery->where('assigned_admin_id', $filters['admin']);
        }

        $statistics = $statsQuery->selectRaw('
            COUNT(*) as total_completed,
            AVG(followup_time_minutes) as avg_followup_time,
            AVG(completion_time_minutes) as avg_completion_time,
            SUM(savings_amount) as total_savings,
            AVG(savings_percentage) as avg_savings_percentage
        ')->first();

        // Admin list
        $adminIds = AdminTask::whereIn('business_unit_id', $buIds)
            ->whereNotNull('assigned_admin_id')
            ->distinct()
            ->pluck('assigned_admin_id');

        $adminList = \App\Models\Core\User::whereIn('id', $adminIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('PurchasingAdmin/ManagementHistory', [
            'tasks' => $tasks,
            'statistics' => [
                'total_completed' => (int) ($statistics->total_completed ?? 0),
                'avg_followup_time' => (float) ($statistics->avg_followup_time ?? 0),
                'avg_completion_time' => (float) ($statistics->avg_completion_time ?? 0),
                'total_savings' => (float) ($statistics->total_savings ?? 0),
                'avg_savings_percentage' => (float) ($statistics->avg_savings_percentage ?? 0),
            ],
            'adminList' => $adminList,
            'filters' => $filters,
        ]);
    }
}
