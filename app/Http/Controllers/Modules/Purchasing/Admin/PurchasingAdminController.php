<?php

namespace App\Http\Controllers\Modules\Purchasing\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Services\Modules\Purchasing\Admin\AdminTaskCsvExporter;
use App\Services\Modules\Purchasing\Admin\AdminTaskHistoryService;
use App\Services\Modules\Purchasing\Admin\AdminTaskListService;
use App\Services\Modules\Purchasing\Admin\AdminTaskMetricsService;
use App\Services\Modules\Purchasing\Admin\AdminTaskReportService;
use App\Services\Modules\Purchasing\Admin\AdminTaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchasingAdminController extends Controller
{
    public function __construct(
        protected AdminTaskService $adminTaskService,
        protected AdminTaskMetricsService $metricsService,
        protected AdminTaskListService $listService,
        protected AdminTaskHistoryService $historyService,
        protected AdminTaskCsvExporter $csvExporter,
        protected AdminTaskReportService $reportService,
    ) {}

    private function currentUserIsPurchasingReadonly(): bool
    {
        $user = auth()->user();
        $buId = (int) session('current_business_unit_id');
        if (! $user || ! $buId) {
            return false;
        }

        return \App\Models\Core\UserBusinessUnit::query()
            ->where('user_id', $user->id)
            ->where('business_unit_id', $buId)
            ->where('is_active', true)
            ->where('is_purchasing_admin', true)
            ->where('is_purchasing_readonly', true)
            ->exists();
    }

    /**
     * Display the purchasing admin dashboard.
     */
    public function dashboard(Request $request): Response
    {
        $user = auth()->user();
        $buId = (int) session('current_business_unit_id');

        $data = $this->metricsService->buildDashboardData($user, $buId, [
            'date_preset' => $request->input('date_preset', 'this_month'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ]);

        return Inertia::render('PurchasingAdmin/Dashboard', [
            'stats' => $data['stats'],
            'recentTasks' => $data['recentTasks'],
            'metrics' => $data['metrics'],
            'savingsTrend' => $data['savingsTrend'],
            'departmentBreakdown' => $data['departmentBreakdown'],
            'datePreset' => $data['datePreset'],
            'dateRange' => [
                'from' => $request->input('date_from'),
                'to' => $request->input('date_to'),
            ],
            'userRole' => [
                'is_purchasing_admin' => $user->isAdminInBuOrAncestor('is_purchasing_admin', $buId),
                'is_purchasing_readonly' => $this->currentUserIsPurchasingReadonly(),
                'is_management' => $user->hasTopManagementAccess() || $user->isSuperAdmin(),
            ],
        ]);
    }

    /**
     * Display tasks list.
     */
    public function tasks(Request $request): Response
    {
        $data = $this->listService->buildTasksPageData(
            auth()->user(),
            (int) session('current_business_unit_id'),
            [
                'status' => $request->input('status', 'pending'),
                'type' => $request->input('type', ''),
                'date' => $request->input('date', 'all'),
                'search' => $request->input('search', ''),
            ],
        );

        return Inertia::render('PurchasingAdmin/Tasks', [
            'tasks' => $data['tasks'],
            'allTasks' => $data['allTasks'],
            'filters' => $data['filters'],
            'counts' => $data['counts'],
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
    public function claimTask(Request $request, $taskId): RedirectResponse
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

        if ($this->currentUserIsPurchasingReadonly()) {
            return back()->with('error', 'Read-only purchasing access cannot claim tasks.');
        }

        try {
            $this->adminTaskService->claimTask($task, (int) auth()->id());

            return back()->with('success', 'Task claimed successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Start a task.
     */
    public function startTask(Request $request, $taskId): RedirectResponse
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

        if ($this->currentUserIsPurchasingReadonly()) {
            return back()->with('error', 'Read-only purchasing access cannot start tasks.');
        }

        try {
            $this->adminTaskService->startTask($task);

            return back()->with('success', 'Task started successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
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

        if ($this->currentUserIsPurchasingReadonly()) {
            return back()->with('error', 'Read-only purchasing access cannot update tasks.');
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
    public function completeTask(Request $request, $taskId): RedirectResponse
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

        if ($this->currentUserIsPurchasingReadonly()) {
            return back()->with('error', 'Read-only purchasing access cannot complete tasks.');
        }

        try {
            $this->adminTaskService->completeTask(
                $task,
                (float) $request->input('realized_total_price'),
                $request->input('notes')
            );

            return redirect()->route('purchasing.admin.tasks')->with('success', 'Task completed successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display personal task history.
     */
    public function taskHistory(Request $request): Response
    {
        $data = $this->historyService->buildTaskHistoryData(
            auth()->user(),
            [
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'status' => $request->get('status', 'all'),
                'type' => $request->get('type', 'all'),
            ],
        );

        return Inertia::render('PurchasingAdmin/TaskHistory', [
            'tasks' => $data['tasks'],
            'statistics' => $data['statistics'],
            'filters' => $data['filters'],
        ]);
    }

    /**
     * Export task history to CSV/Excel.
     */
    public function exportTaskHistory(Request $request)
    {
        return $this->csvExporter->streamPersonalTaskHistory(
            auth()->user(),
            [
                'format' => $request->get('format', 'csv'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'status' => $request->get('status', 'all'),
                'type' => $request->get('type', 'all'),
            ],
        );
    }

    /**
     * Display department report.
     */
    public function departmentReport(Request $request): Response
    {
        $data = $this->reportService->buildDepartmentReportData(
            auth()->user(),
            (int) session('current_business_unit_id'),
        );

        return Inertia::render('PurchasingAdmin/DepartmentReport', $data);
    }

    /**
     * Display consolidated report.
     */
    public function consolidatedReport(Request $request): Response
    {
        $data = $this->reportService->buildConsolidatedReportData(
            (int) session('current_business_unit_id'),
        );

        return Inertia::render('PurchasingAdmin/ConsolidatedReport', $data);
    }

    /**
     * Display audit history (Super Admin).
     */
    public function auditHistory(Request $request): Response
    {
        $data = $this->historyService->buildAuditHistoryData([
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'status' => $request->get('status', 'all'),
            'type' => $request->get('type', 'all'),
            'admin' => $request->get('admin', 'all'),
            'department' => $request->get('department', 'all'),
        ]);

        return Inertia::render('PurchasingAdmin/AuditHistory', [
            'tasks' => $data['tasks'],
            'admins' => $data['admins'],
            'departments' => $data['departments'],
            'filters' => $data['filters'],
        ]);
    }

    /**
     * Display department audit history (Department Manager).
     */
    public function departmentAuditHistory(Request $request): Response
    {
        $data = $this->historyService->buildDepartmentAuditHistoryData(
            auth()->user(),
            (int) session('current_business_unit_id'),
            [
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'status' => $request->get('status', 'all'),
                'type' => $request->get('type', 'all'),
                'admin' => $request->get('admin', 'all'),
            ],
        );

        return Inertia::render('PurchasingAdmin/DepartmentAuditHistory', [
            'tasks' => $data['tasks'],
            'admins' => $data['admins'],
            'filters' => $data['filters'],
        ]);
    }

    /**
     * Display personal task history (Purchasing Admin).
     */
    public function personalTaskHistory(Request $request): Response
    {
        $data = $this->historyService->buildPersonalTaskHistoryData(
            auth()->user(),
            [
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'status' => $request->get('status', 'all'),
                'type' => $request->get('type', 'all'),
            ],
        );

        return Inertia::render('PurchasingAdmin/PersonalTaskHistory', [
            'tasks' => $data['tasks'],
            'statistics' => $data['statistics'],
            'filters' => $data['filters'],
        ]);
    }

    /**
     * Display management history.
     */
    public function managementHistory(Request $request): Response
    {
        $data = $this->historyService->buildManagementHistoryData(
            (int) session('current_business_unit_id'),
            [
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'status' => $request->get('status', 'all'),
                'type' => $request->get('type', 'all'),
                'admin' => $request->get('admin', 'all'),
            ],
        );

        return Inertia::render('PurchasingAdmin/ManagementHistory', [
            'tasks' => $data['tasks'],
            'statistics' => $data['statistics'],
            'adminList' => $data['adminList'],
            'filters' => $data['filters'],
        ]);
    }
}
