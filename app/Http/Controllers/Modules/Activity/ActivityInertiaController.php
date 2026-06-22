<?php

namespace App\Http\Controllers\Modules\Activity;

use App\Actions\Modules\Activity\BackdateApprovalAction;
use App\Actions\Modules\Activity\CreateActivityTaskAction;
use App\Actions\Modules\Activity\UpdateActivityTaskAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Activity\StoreActivityTaskRequest;
use App\Http\Requests\Modules\Activity\UpdateActivityTaskRequest;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\Activity\EmployeeTask;
use App\Services\Modules\Activity\ActivityAnalyticsAggregator;
use App\Services\Modules\Activity\ActivityExportService;
use App\Services\Modules\Activity\ActivityMemberFocusService;
use App\Services\Modules\Activity\ActivityTypePrioritizationService;
use App\Services\Modules\Activity\ActivityVisualsBuilder;
use App\Services\Modules\Activity\BackdateApprovalQueryService;
use App\Services\Modules\Activity\BackdatePermissionService;
use App\Services\Modules\Activity\ExecutiveOverviewService;
use App\Services\Modules\Activity\TaskPresenter;
use App\Services\Modules\Activity\TaskQueryBuilder;
use App\Services\Modules\Activity\TaskScopeResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ActivityInertiaController extends Controller
{
    public function __construct(
        protected BackdatePermissionService $backdateService,
        protected ActivityTypePrioritizationService $prioritizationService,
        protected ActivityMemberFocusService $memberFocusService,
        protected TaskScopeResolver $scopeResolver,
        protected TaskPresenter $presenter,
        protected ActivityAnalyticsAggregator $analyticsAggregator,
        protected ActivityVisualsBuilder $visualsBuilder,
        protected ExecutiveOverviewService $executiveOverview,
        protected TaskQueryBuilder $taskQueryBuilder,
        protected BackdateApprovalQueryService $backdateQueryService,
        protected CreateActivityTaskAction $createTaskAction,
        protected UpdateActivityTaskAction $updateTaskAction,
        protected BackdateApprovalAction $backdateApprovalAction
    ) {}

    /**
     * Display the Activity Dashboard (Personal & Department Analytics).
     */
    public function dashboard(): Response
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');
        $departmentId = $user->getCurrentDepartmentId();

        $tab = request()->input('tab', 'all');
        $distributionPeriod = request()->input('distribution_period', 'all');
        $personalStats = $this->analyticsAggregator->getPersonalStats($user->id, $buId, $departmentId, $distributionPeriod);
        $personalVisuals = $this->visualsBuilder->getPersonalVisuals($user->id, $buId, $departmentId, $tab, $distributionPeriod);

        $departmentStats = null;
        $departmentVisuals = null;
        $departmentMembers = [];
        $subDepartments = [];
        $sanitizedMemberUserId = null;
        $sanitizedDeptFilterId = null;
        $effectiveDepartmentId = $departmentId;

        if ($user->can('view-department-analytics')) {
            $subDepartments = $this->scopeResolver->resolveSubDepartments($departmentId);
            $sanitizedDeptFilterId = $this->scopeResolver->sanitizeDeptFilter(
                request()->query('dept_filter'),
                $subDepartments
            );

            if ($sanitizedDeptFilterId !== null) {
                $effectiveDepartmentId = $sanitizedDeptFilterId;
            }

            $departmentMembers = $this->memberFocusService->resolveDepartmentMembers($buId, $effectiveDepartmentId);
            $sanitizedMemberUserId = $this->memberFocusService->sanitizeRequestedMember(
                request()->query('member_user_id'),
                $departmentMembers
            );
            $deptDistributionPeriod = request()->input('dept_distribution_period', 'all');
            $departmentStats = $this->analyticsAggregator->getDepartmentStats($effectiveDepartmentId, $buId, $deptDistributionPeriod, $sanitizedMemberUserId);
            $departmentVisuals = $this->visualsBuilder->getDepartmentVisuals($effectiveDepartmentId, $buId, $deptDistributionPeriod, $sanitizedMemberUserId);
        }

        $executiveStats = null;
        $canViewReports = $this->executiveOverview->canViewExecutiveDashboard($user, $buId);
        if ($canViewReports) {
            $executiveStats = $this->executiveOverview->getExecutiveOverview($user);
        }

        $queryParams = request()->query();
        $queryParams['member_user_id'] = $sanitizedMemberUserId ? (string) $sanitizedMemberUserId : null;
        $queryParams['dept_filter'] = $sanitizedDeptFilterId ? (string) $sanitizedDeptFilterId : null;

        return Inertia::render('Activity/ActivityDashboard', [
            'personalStats' => $personalStats,
            'personalVisuals' => $personalVisuals,
            'departmentStats' => $departmentStats,
            'departmentVisuals' => $departmentVisuals,
            'departmentMembers' => $departmentMembers,
            'subDepartments' => $subDepartments,
            'executiveStats' => $executiveStats,
            'canViewReports' => $canViewReports,
            'queryParams' => $queryParams,
        ]);
    }

    /**
     * Display the Task List (Overview/List/Board/Calendar/Timeline).
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');
        $departmentId = $user->getCurrentDepartmentId();

        $scope = $request->get('scope', 'my');
        $filters = $this->taskQueryBuilder->resolveFilters($request, $buId, $departmentId, $user->id);
        $includeBreakdown = $request->boolean('with_breakdown', false);

        try {
            $resolved = $this->taskQueryBuilder->resolveTeamMembers($request, $buId, $departmentId, $scope);
            $teamMembers = $resolved['teamMembers'];
            $sanitizedMemberUserId = $resolved['memberUserId'];
            $filters['member_user_id'] = $sanitizedMemberUserId ? (string) $sanitizedMemberUserId : '';

            $query = $this->taskQueryBuilder->buildFilteredQuery(
                $buId,
                $user->id,
                $departmentId,
                $scope,
                $sanitizedMemberUserId,
                $filters
            );

            $stats = $this->analyticsAggregator->getStatsForScope($buId, $user->id, $departmentId, $scope, $sanitizedMemberUserId);
            $view = $request->get('view', 'list');
            $tasks = $this->taskQueryBuilder->paginateForView($query, $view);

            $byActivityType = $includeBreakdown
                ? $this->analyticsAggregator->getByActivityType($buId, $user->id, $departmentId)
                : [];

            $activityTypes = $this->presenter->getDepartmentActivityTypes($departmentId);
            $selectedTask = $this->presenter->getSelectedTaskForModal($request);
            if ($selectedTask) {
                $this->presenter->hydrateTaskAvatars($selectedTask);
            }
            $selectedTaskModal = $selectedTask ? $this->presenter->getSelectedTaskModal($request) : null;

            return Inertia::render('Activity/Dashboard', [
                'stats' => $stats,
                'tasks' => $tasks,
                'selectedTask' => $selectedTask,
                'selectedTaskModal' => $selectedTaskModal,
                'activityTypes' => $activityTypes,
                'filters' => $filters,
                'teamMembers' => $teamMembers,
                'byActivityType' => $byActivityType,
                'departmentUsers' => Inertia::lazy(fn () => User::where('primary_department_id', $departmentId)
                    ->where('id', '!=', $user->id)
                    ->select(['id', 'name', 'email'])
                    ->get()),
                'backdatePermission' => Inertia::lazy(fn () => $this->backdateService->checkUserPermission($user->id)),
                'allowedDateRange' => Inertia::lazy(fn () => $this->backdateService->getAllowedDateRange($user)),
                'backdateEnabled' => Inertia::lazy(fn () => $this->backdateService->isBackdateApprovalEnabled()),
                'prioritizedActivityTypes' => Inertia::lazy(fn () => $this->presenter->formatPrioritizedActivityTypes(
                    $this->prioritizationService->getForUser($user)
                )),
            ]);
        } catch (\Exception $e) {
            $activityTypes = $this->presenter->getDepartmentActivityTypes($departmentId);

            return Inertia::render('Activity/Dashboard', [
                'stats' => [
                    'total' => 0,
                    'completed' => 0,
                    'in_progress' => 0,
                    'overdue' => 0,
                ],
                'tasks' => new LengthAwarePaginator([], 0, 20),
                'selectedTask' => null,
                'selectedTaskModal' => null,
                'activityTypes' => $activityTypes,
                'filters' => $filters,
                'teamMembers' => [],
                'byActivityType' => [],
            ]);
        }
    }

    public function show(EmployeeTask $task): RedirectResponse
    {
        abort_unless($this->scopeResolver->canViewTask($task, Auth::user(), session('current_business_unit_id')), 404);

        return $this->redirectToTaskIndex($task, 'detail');
    }

    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('activity.task.index', array_merge($request->query(), [
            'modal' => 'create',
        ]));
    }

    public function store(StoreActivityTaskRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');
        $departmentId = session('current_department_id') ?? $user->getCurrentDepartmentId();

        $result = $this->createTaskAction->execute($request, $user, $buId, $departmentId);

        if (! $result['ok']) {
            return back()->with('error', $result['error']);
        }

        return redirect()
            ->to($result['redirect_url'])
            ->with('success', 'Task created successfully.')
            ->with('created_task_id', $result['task']->id);
    }

    public function edit(EmployeeTask $task): RedirectResponse
    {
        abort_unless($this->scopeResolver->canEditTask($task, Auth::user(), session('current_business_unit_id')), 404);

        return $this->redirectToTaskIndex($task, 'edit');
    }

    public function update(UpdateActivityTaskRequest $request, EmployeeTask $task): RedirectResponse
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');

        abort_unless($this->scopeResolver->canEditTask($task, $user, $buId), 403);

        $result = $this->updateTaskAction->execute($request, $task, $user, $buId);

        if (! $result['ok']) {
            return back()->with('error', $result['error']);
        }

        if ($result['type'] === 'partial') {
            if ($request->wantsJson() || $request->header('X-Inertia')) {
                return back()->with('success', 'Task updated successfully.');
            }

            return redirect()
                ->route('activity.task.index')
                ->with('success', 'Task updated successfully.');
        }

        return $this->redirectToTaskIndex($result['task'], 'detail')
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(EmployeeTask $task): RedirectResponse
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');

        abort_unless($this->scopeResolver->canEditTask($task, $user, $buId), 403);

        try {
            $task->delete();
            Cache::forget("activity_stats_{$buId}_{$user->id}");

            return redirect()
                ->route('activity.task.index')
                ->with('success', 'Task deleted successfully.');
        } catch (\Exception $e) {
            report($e);

            return back()->with('error', 'Failed to delete task.');
        }
    }

    public function department(Request $request): Response
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');
        $departmentId = $user->getCurrentDepartmentId();

        try {
            $tasks = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where('department_id', $departmentId)
                ->whereDoesntHave('participants', fn ($q) => $q->where('user_id', $user->id))
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->with(['activityType', 'subActivity', 'participants', 'creator', 'department'])
                ->latest()
                ->paginate(20);

            return Inertia::render('Activity/DepartmentTasks', ['tasks' => $tasks]);
        } catch (\Exception $e) {
            return Inertia::render('Activity/DepartmentTasks', [
                'tasks' => new LengthAwarePaginator([], 0, 20),
            ]);
        }
    }

    public function analyticsPersonal(): Response
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');
        $departmentId = $user->getCurrentDepartmentId();

        return Inertia::render('Activity/Analytics/Personal', [
            'stats' => $this->analyticsAggregator->getPersonalStats($user->id, $buId, $departmentId),
        ]);
    }

    public function analyticsDepartment(): Response
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');
        $departmentId = $user->getCurrentDepartmentId();

        return Inertia::render('Activity/Analytics/Department', [
            'stats' => $this->analyticsAggregator->getDepartmentStats($departmentId, $buId),
        ]);
    }

    public function analyticsBusinessUnit(): Response
    {
        $buId = session('current_business_unit_id');

        return Inertia::render('Activity/Analytics/BusinessUnit', [
            'stats' => $this->analyticsAggregator->getBusinessUnitStats($buId),
        ]);
    }

    public function reportingDashboard(Request $request): Response
    {
        $this->authorize('view-reports');

        $dateRange = [
            'start' => $request->get('start_date', now()->subDays(30)->format('Y-m-d')),
            'end' => $request->get('end_date', now()->format('Y-m-d')),
        ];

        return Inertia::render('Activity/Reporting/BODDashboard', [
            'dateRange' => $dateRange,
            'initialData' => null,
        ]);
    }

    public function managerDashboard(Request $request): Response
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');

        $dateRange = [
            'start' => $request->get('start_date', now()->subDays(30)->format('Y-m-d')),
            'end' => $request->get('end_date', now()->format('Y-m-d')),
        ];

        $businessUnits = $user->businessUnits()
            ->with('businessUnit')
            ->get()
            ->pluck('businessUnit')
            ->filter()
            ->values();

        $departments = Department::where('business_unit_id', $buId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'business_unit_id']);

        return Inertia::render('Activity/Reporting/ManagerDashboard', [
            'dateRange' => $dateRange,
            'businessUnits' => $businessUnits,
            'departments' => $departments,
            'selectedBusinessUnitId' => $buId,
            'selectedDepartmentId' => $user->getCurrentDepartmentId(),
            'initialData' => null,
        ]);
    }

    public function backdateRequests(): Response
    {
        abort_unless(config('features.backdate_approval'), 404);
        $user = Auth::user();

        return Inertia::render('Activity/Backdate/Requests', [
            'requests' => $this->backdateQueryService->paginateUserRequests($user),
            'activePermission' => $this->backdateService->checkUserPermission($user->id),
            'hasPendingRequest' => $this->backdateQueryService->userHasPendingRequest($user),
        ]);
    }

    public function backdateApprovals(Request $request): Response
    {
        abort_unless(config('features.backdate_approval'), 404);
        $user = Auth::user();
        $buId = session('current_business_unit_id');
        $departmentId = $user->getCurrentDepartmentId();

        $accessLevel = $user->getAccessLevel();
        if (! in_array($accessLevel, ['department_head', 'super_admin', 'executive', 'general_manager'])) {
            abort(403, 'Only department heads can access this page');
        }

        $statusFilter = $request->get('status', 'pending');

        return Inertia::render('Activity/Backdate/Approvals', [
            'requests' => $this->backdateQueryService->paginateApprovals($request, $user, $buId, $departmentId),
            'pendingCount' => $this->backdateQueryService->pendingCount($user, $buId, $departmentId),
            'statusFilter' => $statusFilter,
        ]);
    }

    public function approveBackdate(int $id): RedirectResponse
    {
        abort_unless(config('features.backdate_approval'), 404);

        $result = $this->backdateApprovalAction->approve($id, Auth::user());

        return $result['ok']
            ? back()->with('success', $result['message'])
            : back()->with('error', $result['error']);
    }

    public function rejectBackdate(Request $request, int $id): RedirectResponse
    {
        abort_unless(config('features.backdate_approval'), 404);

        $result = $this->backdateApprovalAction->reject($request, $id, Auth::user());

        return $result['ok']
            ? back()->with('success', $result['message'])
            : back()->with('error', $result['error']);
    }

    public function submitBackdateRequest(Request $request): RedirectResponse
    {
        abort_unless(config('features.backdate_approval'), 404);

        $result = $this->backdateApprovalAction->submit($request, Auth::user());

        return $result['ok']
            ? back()->with('success', $result['message'])
            : back()->withErrors($result['errors']);
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');
        $scope = $request->get('scope', 'my');

        try {
            $exportService = app(ActivityExportService::class);

            $userId = $scope === 'my' ? $user->id : null;
            $departmentId = $user->getCurrentDepartmentId();
            $teamMembers = $this->memberFocusService->resolveDepartmentMembers($buId, $departmentId);
            $focusedMemberUserId = $scope === 'department'
                ? $this->memberFocusService->sanitizeRequestedMember($request->query('member_user_id'), $teamMembers)
                : null;

            return $exportService->exportToXlsx(
                businessUnitId: $buId,
                departmentId: $departmentId,
                userId: $userId,
                focusedMemberUserId: $focusedMemberUserId,
                dateFrom: $request->get('date_from'),
                dateTo: $request->get('date_to'),
                status: $request->get('status'),
                activityTypeId: $request->get('activity_type_id')
            );
        } catch (\Throwable $exception) {
            report($exception);

            return response('Failed to export activity report.', 500);
        }
    }

    /**
     * Redirect deprecated task detail/edit routes back to the task index.
     */
    protected function redirectToTaskIndex(EmployeeTask $task, string $modal): RedirectResponse
    {
        return redirect()->route('activity.task.index', array_merge(request()->query(), [
            'task' => $task->id,
            'modal' => $modal,
        ]));
    }
}
