<?php

namespace App\Http\Controllers\Modules\Activity;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Modules\Activity\BackdatePermission;
use App\Models\Modules\Activity\EmployeeTask;
use App\Services\Modules\Activity\ActivityAdminExportService;
use App\Services\Modules\Activity\BackdatePermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ActivityAdminController extends Controller
{
    public function __construct(
        protected BackdatePermissionService $backdateService,
        protected ActivityAdminExportService $exportService,
    ) {}

    /**
     * Activity Admin Dashboard - Overview of all departments in current BU.
     */
    public function dashboard(Request $request): Response
    {
        $scopedBusinessUnitIds = $this->resolveScopedBusinessUnitIds();
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $requestedDepartmentId = $request->integer('department_id');

        // Parent or holding BUs should aggregate descendant BU activity data too.
        $departments = Department::whereIn('business_unit_id', $scopedBusinessUnitIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $selectedDepartmentId = $requestedDepartmentId && $departments->contains('id', $requestedDepartmentId)
            ? $requestedDepartmentId
            : null;

        // Determine which departments to aggregate stats for
        $filteredDepartments = $selectedDepartmentId
            ? $departments->where('id', (int) $selectedDepartmentId)
            : $departments;

        // Aggregate stats per department
        $departmentStats = [];
        foreach ($filteredDepartments as $dept) {
            $query = EmployeeTask::whereIn('business_unit_id', $scopedBusinessUnitIds)
                ->where('department_id', $dept->id)
                ->whereBetween('task_date', [$dateFrom, $dateTo]);

            $total = (clone $query)->count();
            $completed = (clone $query)->where('status', 'completed')->count();
            $inProgress = (clone $query)->where('status', 'in_progress')->count();
            $planned = (clone $query)->where('status', 'planned')->count();
            $cancelled = (clone $query)->where('status', 'cancelled')->count();
            $totalMinutes = (clone $query)->where('status', 'completed')->sum('duration_minutes');

            $departmentStats[] = [
                'department' => $dept,
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'planned' => $planned,
                'cancelled' => $cancelled,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
                'total_hours' => round(($totalMinutes ?? 0) / 60, 1),
            ];
        }

        // Summary (scoped to filtered departments)
        $buSummary = [
            'total' => array_sum(array_column($departmentStats, 'total')),
            'completed' => array_sum(array_column($departmentStats, 'completed')),
            'in_progress' => array_sum(array_column($departmentStats, 'in_progress')),
            'planned' => array_sum(array_column($departmentStats, 'planned')),
            'cancelled' => array_sum(array_column($departmentStats, 'cancelled')),
            'total_hours' => round(array_sum(array_column($departmentStats, 'total_hours')), 1),
        ];
        $buSummary['completion_rate'] = $buSummary['total'] > 0
            ? round(($buSummary['completed'] / $buSummary['total']) * 100, 1)
            : 0;

        // Activity type distribution (scoped to department if filtered)
        $buActivityTypes = EmployeeTask::whereIn('business_unit_id', $scopedBusinessUnitIds)
            ->whereBetween('task_date', [$dateFrom, $dateTo])
            ->when($selectedDepartmentId, fn ($q) => $q->where('department_id', $selectedDepartmentId))
            ->join('employee_activity_types', 'employee_tasks.activity_type_id', '=', 'employee_activity_types.id')
            ->select('employee_activity_types.name')
            ->selectRaw('MIN(employee_activity_types.color) as color')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw("SUM(CASE WHEN employee_tasks.status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw('COALESCE(SUM(employee_tasks.duration_minutes), 0) as total_minutes')
            ->groupBy('employee_activity_types.name')
            ->orderByDesc('count')
            ->limit(8)
            ->get()
            ->map(function ($item) use ($buSummary) {
                return [
                    'name' => $item->name,
                    'color' => $item->color,
                    'count' => $item->count,
                    'completed' => $item->completed,
                    'hours' => round($item->total_minutes / 60, 1),
                    'percentage' => $buSummary['total'] > 0 ? round(($item->count / $buSummary['total']) * 100, 1) : 0,
                ];
            });

        // Daily trend (scoped to department if filtered)
        $dailyTrend = EmployeeTask::whereIn('business_unit_id', $scopedBusinessUnitIds)
            ->whereBetween('task_date', [$dateFrom, $dateTo])
            ->when($selectedDepartmentId, fn ($q) => $q->where('department_id', $selectedDepartmentId))
            ->select('task_date')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->groupBy('task_date')
            ->orderBy('task_date')
            ->get()
            ->map(fn ($d) => [
                'date' => $d->task_date->format('M d'),
                'total' => $d->total,
                'completed' => $d->completed,
            ]);

        // Top contributors (scoped to department if filtered)
        $topContributors = EmployeeTask::whereIn('employee_tasks.business_unit_id', $scopedBusinessUnitIds)
            ->whereBetween('employee_tasks.task_date', [$dateFrom, $dateTo])
            ->when($selectedDepartmentId, fn ($q) => $q->where('employee_tasks.department_id', $selectedDepartmentId))
            ->join('users', 'employee_tasks.created_by', '=', 'users.id')
            ->leftJoin('departments', 'employee_tasks.department_id', '=', 'departments.id')
            ->select('employee_tasks.created_by', 'users.name', 'departments.name as dept_name')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN employee_tasks.status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->groupBy('employee_tasks.created_by', 'users.name', 'departments.name')
            ->orderByDesc('completed')
            ->limit(10)
            ->get();

        // Pending HOD backdate requests count (only when feature is enabled)
        $pendingBackdateCount = 0;
        if (config('features.backdate_approval')) {
            $pendingBackdateCount = BackdatePermission::whereIn('business_unit_id', $scopedBusinessUnitIds)
                ->where('status', 'pending')
                ->whereHas('requester', function ($q) use ($scopedBusinessUnitIds) {
                    $q->whereHas('businessUnits', function ($q2) use ($scopedBusinessUnitIds) {
                        $q2->whereIn('business_unit_id', $scopedBusinessUnitIds)
                            ->whereHas('position', fn ($q3) => $q3->whereIn('access_level', ['department_head', 'team_leader']));
                    });
                })
                ->count();
        }

        return Inertia::render('Activity/Admin/Dashboard', [
            'departmentStats' => $departmentStats,
            'buSummary' => $buSummary,
            'buActivityTypes' => $buActivityTypes,
            'dailyTrend' => $dailyTrend,
            'topContributors' => $topContributors,
            'pendingBackdateCount' => $pendingBackdateCount,
            'departments' => $departments,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'department_id' => $selectedDepartmentId ? (int) $selectedDepartmentId : null,
            ],
        ]);
    }

    /**
     * Department detail - tasks and user breakdown for a specific department.
     */
    public function departmentDetail(Request $request, int $departmentId): Response
    {
        $scopedBusinessUnitIds = $this->resolveScopedBusinessUnitIds();
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $status = $request->get('status', '');
        $activityTypeId = $request->get('activity_type_id', '');
        $search = $request->get('search', '');

        $department = Department::whereIn('business_unit_id', $scopedBusinessUnitIds)
            ->findOrFail($departmentId);

        // Tasks query with filters
        $query = EmployeeTask::whereIn('business_unit_id', $scopedBusinessUnitIds)
            ->where('department_id', $departmentId)
            ->whereBetween('task_date', [$dateFrom, $dateTo])
            ->when($status, fn ($q, $v) => $q->where('status', $v))
            ->when($activityTypeId, fn ($q, $v) => $q->where('activity_type_id', $v))
            ->when($search, fn ($q, $v) => $q->where('task_title', 'like', "%{$v}%"));

        $tasks = (clone $query)
            ->with(['activityType', 'subActivity', 'participants', 'creator', 'department'])
            ->latest('task_date')
            ->paginate(20)
            ->withQueryString();

        // Per-user breakdown
        $userBreakdown = EmployeeTask::whereIn('employee_tasks.business_unit_id', $scopedBusinessUnitIds)
            ->where('employee_tasks.department_id', $departmentId)
            ->whereBetween('employee_tasks.task_date', [$dateFrom, $dateTo])
            ->join('users', 'employee_tasks.created_by', '=', 'users.id')
            ->select('employee_tasks.created_by', 'users.name as created_by_name')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN employee_tasks.status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN employee_tasks.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress")
            ->selectRaw("SUM(CASE WHEN employee_tasks.status = 'planned' THEN 1 ELSE 0 END) as planned")
            ->groupBy('employee_tasks.created_by', 'users.name')
            ->orderByDesc('total')
            ->get();

        // Activity type distribution
        $activityTypeDistribution = EmployeeTask::whereIn('business_unit_id', $scopedBusinessUnitIds)
            ->where('department_id', $departmentId)
            ->whereBetween('task_date', [$dateFrom, $dateTo])
            ->join('employee_activity_types', 'employee_tasks.activity_type_id', '=', 'employee_activity_types.id')
            ->select('employee_activity_types.name', 'employee_activity_types.color')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('employee_activity_types.id', 'employee_activity_types.name', 'employee_activity_types.color')
            ->orderByDesc('count')
            ->get();

        // Department stats
        $statsQuery = EmployeeTask::whereIn('business_unit_id', $scopedBusinessUnitIds)
            ->where('department_id', $departmentId)
            ->whereBetween('task_date', [$dateFrom, $dateTo]);

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
            'in_progress' => (clone $statsQuery)->where('status', 'in_progress')->count(),
            'planned' => (clone $statsQuery)->where('status', 'planned')->count(),
        ];

        // Activity types for filter dropdown
        $activityTypes = DB::table('department_activity_types')
            ->join('employee_activity_types', 'department_activity_types.activity_type_id', '=', 'employee_activity_types.id')
            ->where('department_activity_types.department_id', $departmentId)
            ->where('employee_activity_types.is_active', true)
            ->select('employee_activity_types.id', 'employee_activity_types.name', 'employee_activity_types.code', 'employee_activity_types.color')
            ->orderBy('department_activity_types.sort_order')
            ->get();

        $selectedTask = $this->resolveSelectedDepartmentTask(
            request: $request,
            departmentId: $departmentId,
            scopedBusinessUnitIds: $scopedBusinessUnitIds,
        );
        $selectedTaskModal = $selectedTask ? 'detail' : null;

        return Inertia::render('Activity/Admin/DepartmentDetail', [
            'department' => $department,
            'tasks' => $tasks,
            'stats' => $stats,
            'userBreakdown' => $userBreakdown,
            'activityTypeDistribution' => $activityTypeDistribution,
            'activityTypes' => $activityTypes,
            'selectedTask' => $selectedTask,
            'selectedTaskModal' => $selectedTaskModal,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'status' => $status,
                'activity_type_id' => $activityTypeId,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Task detail (read-only view).
     */
    public function taskDetail(EmployeeTask $task): Response
    {
        $scopedBusinessUnitIds = $this->resolveScopedBusinessUnitIds();

        if (! in_array((int) $task->business_unit_id, $scopedBusinessUnitIds, true)) {
            abort(403, 'Task does not belong to current business unit.');
        }

        $task->load([
            'activityType',
            'subActivity',
            'participants',
            'creator',
            'department',
            'attachments',
        ]);

        return Inertia::render('Activity/Admin/TaskDetail', [
            'task' => $task,
        ]);
    }

    /**
     * HOD Backdate approval queue for Activity Admin.
     */
    public function backdateApprovals(Request $request): Response
    {
        abort_unless(config('features.backdate_approval'), 404);

        $scopedBusinessUnitIds = $this->resolveScopedBusinessUnitIds();
        $status = $request->get('status', 'pending');

        $query = BackdatePermission::whereIn('business_unit_id', $scopedBusinessUnitIds)
            ->whereHas('requester', function ($q) use ($scopedBusinessUnitIds) {
                $q->whereHas('businessUnits', function ($q2) use ($scopedBusinessUnitIds) {
                    $q2->whereIn('business_unit_id', $scopedBusinessUnitIds)
                        ->whereHas('position', fn ($q3) => $q3->whereIn('access_level', ['department_head', 'team_leader']));
                });
            })
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->with(['requester', 'department'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Activity/Admin/BackdateApprovals', [
            'requests' => $query,
            'filters' => ['status' => $status],
        ]);
    }

    /**
     * Approve HOD backdate request.
     */
    public function approveBackdate(Request $request, int $id)
    {
        abort_unless(config('features.backdate_approval'), 404);

        $permission = BackdatePermission::findOrFail($id);
        $this->backdateService->approveRequest($permission, Auth::user());

        return back()->with('success', 'Backdate request approved.');
    }

    /**
     * Reject HOD backdate request.
     */
    public function rejectBackdate(Request $request, int $id)
    {
        abort_unless(config('features.backdate_approval'), 404);

        $request->validate(['reason' => 'required|string|max:500']);

        $permission = BackdatePermission::findOrFail($id);
        $this->backdateService->rejectRequest($permission, Auth::user(), $request->input('reason'));

        return back()->with('success', 'Backdate request rejected.');
    }

    /**
     * Export activity report to Excel.
     */
    public function export(Request $request)
    {
        $scopedBusinessUnitIds = $this->resolveScopedBusinessUnitIds();
        $requestedDepartmentId = $request->integer('department_id');
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $status = $request->get('status');
        $activityTypeId = $request->get('activity_type_id');
        $departmentId = null;

        if ($requestedDepartmentId) {
            $departmentId = Department::query()
                ->whereIn('business_unit_id', $scopedBusinessUnitIds)
                ->whereKey($requestedDepartmentId)
                ->exists()
                ? $requestedDepartmentId
                : null;
        }

        return $this->exportService->exportToXlsx(
            businessUnitIds: $scopedBusinessUnitIds,
            departmentId: $departmentId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            status: $status,
            activityTypeId: $activityTypeId ? (int) $activityTypeId : null,
        );
    }

    /**
     * Resolve the active BU scope for Activity Admin.
     * Parent or holding BUs include all descendants so their dashboard can act as a roll-up view.
     *
     * @return array<int>
     */
    private function resolveScopedBusinessUnitIds(): array
    {
        $currentBusinessUnitId = (int) session('current_business_unit_id');

        if ($currentBusinessUnitId <= 0) {
            return [];
        }

        $currentBusinessUnit = BusinessUnit::with('descendants')->find($currentBusinessUnitId);

        if (! $currentBusinessUnit) {
            return [$currentBusinessUnitId];
        }

        return $currentBusinessUnit->getAccessibleBusinessUnits();
    }

    private function resolveSelectedDepartmentTask(Request $request, int $departmentId, array $scopedBusinessUnitIds): ?EmployeeTask
    {
        $modal = $request->string('modal')->toString();
        $taskId = $request->integer('task');

        if ($modal !== 'detail' || $taskId <= 0) {
            return null;
        }

        return EmployeeTask::query()
            ->whereKey($taskId)
            ->whereIn('business_unit_id', $scopedBusinessUnitIds)
            ->where('department_id', $departmentId)
            ->with([
                'activityType',
                'subActivity',
                'participants',
                'creator',
                'department',
                'attachments',
            ])
            ->first();
    }
}
