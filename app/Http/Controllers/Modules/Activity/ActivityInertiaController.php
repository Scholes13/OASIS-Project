<?php

namespace App\Http\Controllers\Modules\Activity;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use App\Models\Core\User;
use App\Models\Modules\Activity\EmployeeTask;
use App\Services\Modules\Activity\ActivityTypePrioritizationService;
use App\Services\Modules\Activity\BackdatePermissionService;
use App\Services\Modules\Activity\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ActivityInertiaController extends Controller
{
    protected const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        protected TaskService $taskService,
        protected BackdatePermissionService $backdateService,
        protected ActivityTypePrioritizationService $prioritizationService
    ) {}

    /**
     * Display the Activity Dashboard (Personal & Department Analytics)
     *
     * Top management users (with 'view-reports' permission) see the same
     * ActivityDashboard with an additional `canViewReports` flag. They can
     * navigate to the BOD Reporting Dashboard via a link when it's ready.
     *
     * Graceful degradation: If `Activity/Reporting/BODDashboard` page is
     * not yet implemented, the user still sees their personal dashboard
     * instead of a 500 error.
     */
    public function dashboard()
    {
        $user = Auth::user();

        $buId = session('current_business_unit_id');
        $departmentId = $user->getCurrentDepartmentId();

        // Personal stats & visuals
        $tab = request()->input('tab', 'all');
        $distributionPeriod = request()->input('distribution_period', 'all');
        $personalStats = $this->getPersonalStats($user->id, $buId, $departmentId, $distributionPeriod);
        $personalVisuals = $this->getPersonalVisuals($user->id, $buId, $departmentId, $tab, $distributionPeriod);

        // Department stats & visuals (if user has permission)
        $departmentStats = null;
        $departmentVisuals = null;
        if ($user->can('view-department-analytics')) {
            $deptDistributionPeriod = request()->input('dept_distribution_period', 'all');
            $departmentStats = $this->getDepartmentStats($departmentId, $buId, $deptDistributionPeriod);
            $departmentVisuals = $this->getDepartmentVisuals($departmentId, $buId, $deptDistributionPeriod);
        }

        // Executive overview for top management (cross-BU aggregation)
        // Only show if user has executive access in current BU context
        // or is executive in a parent/ancestor BU of the current one
        $executiveStats = null;
        $canViewReports = $this->canViewExecutiveDashboard($user, $buId);
        if ($canViewReports) {
            $executiveStats = $this->getExecutiveOverview($user);
        }

        return Inertia::render('Activity/ActivityDashboard', [
            'personalStats' => $personalStats,
            'personalVisuals' => $personalVisuals,
            'departmentStats' => $departmentStats,
            'departmentVisuals' => $departmentVisuals,
            'executiveStats' => $executiveStats,
            'canViewReports' => $canViewReports,
            'queryParams' => request()->query(),
        ]);
    }

    /**
     * Display the Task List (Overview/List/Board/Calendar/Timeline)
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');
        $departmentId = $user->getCurrentDepartmentId();

        // Parse filters from request
        // scope: 'my' = only user's tasks (participant/creator), 'department' = all department tasks
        $scope = $request->get('scope', 'my');
        $filters = [
            'search' => $request->get('search', ''),
            'activity_type_id' => $request->get('activity_type_id', ''),
            'status' => $request->get('status', ''),
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
            'scope' => $scope,
        ];
        $includeBreakdown = $request->boolean('with_breakdown', false);

        try {
            // Build base query based on scope
            $query = EmployeeTask::query()
                ->where('business_unit_id', $buId);

            // Apply scope filter (server-side for correct pagination)
            if ($scope === 'my') {
                // My Tasks: only tasks where user is participant or creator
                $query->where(function ($q) use ($user) {
                    $q->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
                        ->orWhere('created_by', $user->id);
                });
            } else {
                // Department: all tasks in user's department OR user is participant
                $query->where(function ($q) use ($user, $departmentId) {
                    $q->where('department_id', $departmentId)
                        ->orWhereHas('participants', fn ($q) => $q->where('user_id', $user->id));
                });
            }

            // Apply additional filters
            $query->when($filters['activity_type_id'], fn ($q, $v) => $q->where('activity_type_id', $v))
                ->when($filters['status'], fn ($q, $v) => $q->where('status', $v))
                ->when($filters['search'], fn ($q, $v) => $q->where('task_title', 'like', "%{$v}%"))
                ->when($filters['date_from'], fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                ->when($filters['date_to'], fn ($q, $v) => $q->whereDate('created_at', '<=', $v));

            // Calculate stats based on current scope (not cached, for accuracy)
            $stats = $this->getStatsForScope($buId, $user->id, $departmentId, $scope);

            // Determine view mode: board/calendar/timeline need all tasks, list uses pagination
            $view = $request->get('view', 'list');
            $taskQuery = (clone $query)
                ->select([
                    'id',
                    'business_unit_id',
                    'department_id',
                    'created_by',
                    'activity_type_id',
                    'sub_activity_id',
                    'task_title',
                    'task_description',
                    'task_date',
                    'due_date',
                    'status',
                    'priority',
                    'started_at',
                    'completed_at',
                    'duration_minutes',
                    'created_at',
                    'updated_at',
                ])
                ->with([
                    'activityType:id,name,color',
                    'subActivity:id,name,activity_type_id',
                    'participants:id,name',
                    'creator:id,name',
                    'department:id,name,code',
                ])
                ->latest('id');

            if (in_array($view, ['board', 'calendar', 'timeline'])) {
                // Board/Calendar/Timeline need all active tasks (exclude cancelled), no pagination
                $allTasks = $taskQuery->whereNotIn('status', ['cancelled'])->limit(200)->get();
                $tasks = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allTasks, $allTasks->count(), $allTasks->count() ?: 1, 1
                );
            } else {
                $tasks = $taskQuery->paginate(20)->withQueryString();
            }

            // Optional breakdown (not needed for current My Tasks UI)
            $byActivityType = $includeBreakdown
                ? $this->getByActivityType($buId, $user->id, $departmentId)
                : [];

            // Get activity types assigned to user's department for filter dropdown
            // Ordered by department's configured sort_order (Requirements 5.1, 5.3)
            $activityTypes = $this->getDepartmentActivityTypes($departmentId);

            return Inertia::render('Activity/Dashboard', [
                'stats' => $stats,
                'tasks' => $tasks,
                'activityTypes' => $activityTypes,
                'filters' => $filters,
                'byActivityType' => $byActivityType,
                // Lazy loaded props for Create Task Modal
                'departmentUsers' => \Inertia\Inertia::lazy(fn () => \App\Models\Core\User::where('primary_department_id', $departmentId)
                    ->where('id', '!=', $user->id)
                    ->select(['id', 'name', 'email'])
                    ->get()),
                'backdatePermission' => \Inertia\Inertia::lazy(fn () => $this->backdateService->checkUserPermission($user->id)),
                'allowedDateRange' => \Inertia\Inertia::lazy(fn () => $this->backdateService->getAllowedDateRange($user)),
                'backdateEnabled' => \Inertia\Inertia::lazy(fn () => $this->backdateService->isBackdateApprovalEnabled()),
                'prioritizedActivityTypes' => \Inertia\Inertia::lazy(fn () => $this->formatPrioritizedActivityTypes($this->prioritizationService->getForUser($user))),
            ]);
        } catch (\Exception $e) {
            // Table doesn't exist yet (migrations not run)
            // Still try to get department activity types for filter dropdown
            $activityTypes = $this->getDepartmentActivityTypes($departmentId);

            return Inertia::render('Activity/Dashboard', [
                'stats' => [
                    'total' => 0,
                    'completed' => 0,
                    'in_progress' => 0,
                    'overdue' => 0,
                ],
                'tasks' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
                'activityTypes' => $activityTypes,
                'filters' => $filters,
                'byActivityType' => [],
            ]);
        }
    }

    /**
     * Display a task detail
     */
    public function show(EmployeeTask $task): Response
    {
        $task->load([
            'activityType',
            'subActivity',
            'participants',
            'creator',
            'department',
            'attachments',
        ]);

        return Inertia::render('Activity/TaskDetail', [
            'task' => $task,
        ]);
    }

    /**
     * Show create task form
     */
    public function create(): Response
    {
        $user = Auth::user();
        $departmentUsers = User::where('primary_department_id', $user->getCurrentDepartmentId())
            ->where('id', '!=', $user->id)
            ->select(['id', 'name', 'email'])
            ->get();

        // Get backdate permission info
        $backdatePermission = $this->backdateService->checkUserPermission($user->id);
        $allowedDateRange = $this->backdateService->getAllowedDateRange($user);

        // Get prioritized activity types for this user
        $prioritizedTypes = $this->prioritizationService->getForUser($user);
        $activityTypes = $this->formatPrioritizedActivityTypes($prioritizedTypes);

        $backdateEnabled = $this->backdateService->isBackdateApprovalEnabled();

        return Inertia::render('Activity/TaskForm', [
            'task' => null,
            'activityTypes' => $activityTypes,
            'departmentUsers' => $departmentUsers,
            'backdateEnabled' => $backdateEnabled,
            'backdatePermission' => $backdateEnabled && $backdatePermission ? [
                'id' => $backdatePermission->id,
                'status' => $backdatePermission->status,
                'requested_date' => $backdatePermission->requested_date->format('Y-m-d'),
                'granted_until' => $backdatePermission->granted_until?->format('Y-m-d H:i:s'),
                'is_active' => $backdatePermission->isActive(),
            ] : null,
            'allowedDateRange' => [
                'from' => $allowedDateRange['from']->format('Y-m-d'),
                'to' => $allowedDateRange['to']->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Store a new task
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');
        $departmentId = session('current_department_id') ?? $user->getCurrentDepartmentId();

        // Get valid activity type IDs for this department
        $validActivityTypeIds = $this->getValidActivityTypeIds($departmentId);

        // Check if task_date is backdate (before today)
        $taskDate = \Carbon\Carbon::parse($request->input('task_date'));
        $isBackdate = $taskDate->isBefore(now()->startOfDay());
        $status = $request->input('status');

        // Build dynamic validation rules for time fields
        $startTimeRule = 'nullable|date_format:H:i';
        $endTimeRule = 'nullable|date_format:H:i';
        $completedDateRule = 'nullable|date';
        if ($status === 'completed' || ($status === 'in_progress' && $isBackdate)) {
            $startTimeRule = 'required|date_format:H:i';
        }
        if ($status === 'completed') {
            $endTimeRule = 'required|date_format:H:i';
            $completedDateRule = 'required|date|after_or_equal:'.$request->input('task_date');
        }

        $validated = $request->validate([
            'task_title' => 'required|string|max:255',
            'task_description' => 'nullable|string',
            'activity_type_id' => [
                'required',
                'exists:employee_activity_types,id',
                function ($attribute, $value, $fail) use ($validActivityTypeIds) {
                    if (! in_array($value, $validActivityTypeIds)) {
                        $fail('The selected activity type is not assigned to your department.');
                    }
                },
            ],
            'sub_activity_id' => 'nullable|exists:employee_sub_activities,id',
            'status' => 'required|in:planned,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high',
            'task_date' => 'required|date',
            'due_date' => $status === 'completed' ? 'nullable|date' : 'required|date|after_or_equal:task_date',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'exists:users,id',
            // Time fields (HH:mm format)
            'start_time' => $startTimeRule,
            'end_time' => $endTimeRule,
            'completed_date' => $completedDateRule,
        ]);

        // Validate task_date against backdate permission
        if (! $this->backdateService->canCreateTaskWithDate($user, $taskDate)) {
            $allowedRange = $this->backdateService->getAllowedDateRange($user);

            return back()
                ->withInput()
                ->withErrors([
                    'task_date' => 'Task date is outside allowed range. You can only create tasks from '.
                        $allowedRange['from']->format('Y-m-d').' to '.
                        $allowedRange['to']->format('Y-m-d').'. '.
                        'Request backdate access if you need to create tasks with older dates.',
                ]);
        }

        // Validate end_time > start_time when completed_date equals task_date
        $completedDate = $validated['completed_date'] ?? $validated['task_date'];
        if ($status === 'completed' && $completedDate === $validated['task_date'] && ! empty($validated['start_time']) && ! empty($validated['end_time'])) {
            if ($validated['end_time'] <= $validated['start_time']) {
                return back()
                    ->withInput()
                    ->withErrors(['end_time' => 'Waktu selesai harus setelah waktu mulai.']);
            }
        }

        // Prepare time fields based on status
        $startedAt = null;
        $completedAt = null;
        $durationMinutes = null;

        if ($validated['status'] === 'in_progress') {
            // In progress: combine task_date + start_time if backdate, otherwise now()
            if ($isBackdate && ! empty($validated['start_time'])) {
                $startedAt = \Carbon\Carbon::parse($validated['task_date'].' '.$validated['start_time']);
            } else {
                $startedAt = now();
            }
        } elseif ($validated['status'] === 'completed') {
            // Completed: combine task_date + start_time
            $startedAt = \Carbon\Carbon::parse($validated['task_date'].' '.$validated['start_time']);

            // completed_at: combine completed_date + end_time
            $completedAt = \Carbon\Carbon::parse($completedDate.' '.$validated['end_time']);

            $durationMinutes = $startedAt->diffInMinutes($completedAt);
        }

        DB::beginTransaction();
        try {
            $task = EmployeeTask::create([
                'business_unit_id' => $buId,
                'department_id' => session('current_department_id'),
                'created_by' => $user->id,
                'activity_type_id' => $validated['activity_type_id'],
                'sub_activity_id' => $validated['sub_activity_id'] ?? null,
                'task_title' => $validated['task_title'],
                'task_description' => $validated['task_description'] ?? null,
                'status' => $validated['status'],
                'priority' => $validated['priority'],
                'task_date' => $validated['task_date'],
                'due_date' => $validated['due_date'] ?? null,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'completed_by' => $validated['status'] === 'completed' ? $user->id : null,
                'duration_minutes' => $durationMinutes,
            ]);

            // Add creator as owner participant
            $task->participants()->attach($user->id, [
                'is_owner' => true,
                'joined_at' => now(),
            ]);

            // Add other participants
            if (! empty($validated['participant_ids'])) {
                foreach ($validated['participant_ids'] as $participantId) {
                    if ($participantId != $user->id) {
                        $task->participants()->attach($participantId, [
                            'is_owner' => false,
                            'joined_at' => now(),
                        ]);
                    }
                }
            }

            // Clear cache
            $this->clearCache($buId, $user->id);

            DB::commit();

            return redirect()
                ->back(302, [], route('activity.task.create'))
                ->with('success', 'Task created successfully.')
                ->with('created_task_id', $task->id);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to create task: '.$e->getMessage());
        }
    }

    /**
     * Show edit task form
     */
    public function edit(EmployeeTask $task): Response
    {
        $task->load(['activityType', 'subActivity', 'participants']);

        $user = Auth::user();
        $departmentUsers = User::where('primary_department_id', $user->getCurrentDepartmentId())
            ->where('id', '!=', $user->id)
            ->select(['id', 'name', 'email'])
            ->get();

        // Get backdate permission info
        $backdatePermission = $this->backdateService->checkUserPermission($user->id);
        $allowedDateRange = $this->backdateService->getAllowedDateRange($user);

        // Get prioritized activity types for this user
        $prioritizedTypes = $this->prioritizationService->getForUser($user);
        $activityTypes = $this->formatPrioritizedActivityTypes($prioritizedTypes);

        $backdateEnabled = $this->backdateService->isBackdateApprovalEnabled();

        return Inertia::render('Activity/TaskForm', [
            'task' => $task,
            'activityTypes' => $activityTypes,
            'departmentUsers' => $departmentUsers,
            'backdateEnabled' => $backdateEnabled,
            'backdatePermission' => $backdateEnabled && $backdatePermission ? [
                'id' => $backdatePermission->id,
                'status' => $backdatePermission->status,
                'requested_date' => $backdatePermission->requested_date->format('Y-m-d'),
                'granted_until' => $backdatePermission->granted_until?->format('Y-m-d H:i:s'),
                'is_active' => $backdatePermission->isActive(),
            ] : null,
            'allowedDateRange' => [
                'from' => $allowedDateRange['from']->format('Y-m-d'),
                'to' => $allowedDateRange['to']->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Update a task
     */
    public function update(Request $request, EmployeeTask $task)
    {
        // Check if this is a partial update (e.g., drag & drop for due_date or status change)
        $isPartialUpdate = $request->has('due_date') && ! $request->has('task_title');
        $isStatusUpdate = $request->has('status') && ! $request->has('task_title');

        if ($isPartialUpdate || $isStatusUpdate) {
            // Partial update - only validate provided fields
            $validated = $request->validate([
                'due_date' => 'sometimes|date',
                'status' => 'sometimes|in:planned,in_progress,completed,cancelled',
            ]);

            $user = Auth::user();
            $buId = session('current_business_unit_id');

            $updateData = [];

            if (isset($validated['due_date'])) {
                $updateData['due_date'] = $validated['due_date'];
            }

            if (isset($validated['status'])) {
                $updateData['status'] = $validated['status'];

                // Handle status-specific updates
                if ($validated['status'] === 'in_progress' && ! $task->started_at) {
                    $updateData['started_at'] = now();
                }

                if ($validated['status'] === 'completed') {
                    $updateData['completed_at'] = now();
                    $updateData['completed_by'] = $user->id;
                    if ($task->started_at) {
                        $updateData['duration_minutes'] = $task->started_at->diffInMinutes(now());
                    }
                }
            }

            $task->update($updateData);
            $this->clearCache($buId, $user->id);

            // For AJAX/Inertia partial requests, return back
            if ($request->wantsJson() || $request->header('X-Inertia')) {
                return back()->with('success', 'Task updated successfully.');
            }

            return redirect()
                ->route('activity.task.index')
                ->with('success', 'Task updated successfully.');
        }

        // Full update - validate all fields
        $user = Auth::user();
        $buId = session('current_business_unit_id');
        $departmentId = $task->department_id ?? $user->getCurrentDepartmentId();

        // Get valid activity type IDs for this department
        $validActivityTypeIds = $this->getValidActivityTypeIds($departmentId);

        // Pre-filter participant_ids to remove invalid values (null, 0, empty strings)
        $participantIds = $request->input('participant_ids', []);
        if (is_array($participantIds)) {
            $participantIds = array_values(array_filter($participantIds, fn ($id) => ! empty($id) && is_numeric($id) && $id > 0));
        } else {
            $participantIds = [];
        }
        $request->merge(['participant_ids' => $participantIds]);

        // Check if task_date is backdate (before today)
        $taskDate = $task->task_date;
        $isBackdate = $taskDate->isBefore(now()->startOfDay());
        $status = $request->input('status');

        // Build dynamic validation rules for time fields
        $startTimeRule = 'nullable|date_format:H:i';
        $endTimeRule = 'nullable|date_format:H:i';
        $completedDateRule = 'nullable|date';
        if ($status === 'completed' || ($status === 'in_progress' && $isBackdate)) {
            $startTimeRule = 'required|date_format:H:i';
        }
        if ($status === 'completed') {
            $endTimeRule = 'required|date_format:H:i';
            $completedDateRule = 'required|date|after_or_equal:'.$taskDate->format('Y-m-d');
        }

        $validated = $request->validate([
            'task_title' => 'required|string|max:255',
            'task_description' => 'nullable|string',
            'activity_type_id' => [
                'required',
                'exists:employee_activity_types,id',
                function ($attribute, $value, $fail) use ($validActivityTypeIds) {
                    if (! in_array($value, $validActivityTypeIds)) {
                        $fail('The selected activity type is not assigned to this department.');
                    }
                },
            ],
            'sub_activity_id' => 'nullable|exists:employee_sub_activities,id',
            'status' => 'required|in:planned,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high',
            'due_date' => $status === 'completed'
                ? 'nullable|date'
                : 'required|date|after_or_equal:'.$taskDate->format('Y-m-d'),
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'nullable|integer|exists:users,id',
            // Time fields (HH:mm format)
            'start_time' => $startTimeRule,
            'end_time' => $endTimeRule,
            'completed_date' => $completedDateRule,
        ]);

        // Validate end_time > start_time when completed_date equals task_date
        $completedDate = $validated['completed_date'] ?? $taskDate->format('Y-m-d');
        if ($status === 'completed' && $completedDate === $taskDate->format('Y-m-d') && ! empty($validated['start_time']) && ! empty($validated['end_time'])) {
            if ($validated['end_time'] <= $validated['start_time']) {
                return back()
                    ->withInput()
                    ->withErrors(['end_time' => 'Waktu selesai harus setelah waktu mulai.']);
            }
        }

        // Prepare time fields based on status
        $startedAt = $task->started_at;
        $completedAt = null;
        $durationMinutes = $task->duration_minutes;
        $completedBy = $task->completed_by;

        if ($validated['status'] === 'in_progress') {
            // In progress: combine task_date + start_time if backdate, otherwise keep existing or set now()
            if ($isBackdate && ! empty($validated['start_time'])) {
                $startedAt = \Carbon\Carbon::parse($taskDate->format('Y-m-d').' '.$validated['start_time']);
            } elseif (! $task->started_at) {
                $startedAt = now();
            }
        } elseif ($validated['status'] === 'completed') {
            // Completed: combine task_date + start_time
            $startedAt = \Carbon\Carbon::parse($taskDate->format('Y-m-d').' '.$validated['start_time']);

            // completed_at: combine completed_date + end_time
            $completedAt = \Carbon\Carbon::parse($completedDate.' '.$validated['end_time']);

            $durationMinutes = $startedAt->diffInMinutes($completedAt);
            $completedBy = $user->id;
        } elseif ($validated['status'] === 'planned') {
            // Reset to planned: clear time fields
            $startedAt = null;
            $completedAt = null;
            $durationMinutes = null;
            $completedBy = null;
        }

        DB::beginTransaction();
        try {
            $task->update([
                'activity_type_id' => $validated['activity_type_id'],
                'sub_activity_id' => $validated['sub_activity_id'] ?? null,
                'task_title' => $validated['task_title'],
                'task_description' => $validated['task_description'] ?? null,
                'status' => $validated['status'],
                'priority' => $validated['priority'],
                'due_date' => $validated['due_date'] ?? null,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'completed_by' => $completedBy,
                'duration_minutes' => $durationMinutes,
            ]);

            // Update participants (keep owner, sync others)
            $ownerId = $task->participants()->wherePivot('is_owner', true)->first()?->id;
            $newParticipants = [];

            // Always keep owner if exists
            if ($ownerId) {
                $newParticipants[$ownerId] = ['is_owner' => true, 'joined_at' => now()];
            }

            // Add other participants from validated input
            if (! empty($validated['participant_ids'])) {
                foreach ($validated['participant_ids'] as $participantId) {
                    // Skip if empty, null, or same as owner
                    if (empty($participantId) || $participantId == $ownerId) {
                        continue;
                    }
                    $newParticipants[(int) $participantId] = ['is_owner' => false, 'joined_at' => now()];
                }
            }

            // Only sync if we have participants
            if (! empty($newParticipants)) {
                $task->participants()->sync($newParticipants);
            }

            // Clear cache
            $this->clearCache($buId, $user->id);

            DB::commit();

            return redirect()
                ->route('activity.task.show', $task)
                ->with('success', 'Task updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to update task: '.$e->getMessage());
        }
    }

    /**
     * Delete a task
     */
    public function destroy(EmployeeTask $task)
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');

        try {
            $task->delete();
            $this->clearCache($buId, $user->id);

            return redirect()
                ->route('activity.task.index')
                ->with('success', 'Task deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete task: '.$e->getMessage());
        }
    }

    /**
     * Department Tasks page
     */
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

            return Inertia::render('Activity/DepartmentTasks', [
                'tasks' => $tasks,
            ]);
        } catch (\Exception $e) {
            // Table doesn't exist yet (migrations not run)
            return Inertia::render('Activity/DepartmentTasks', [
                'tasks' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
            ]);
        }
    }

    /**
     * Personal Analytics page
     */
    public function analyticsPersonal(): Response
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');
        $departmentId = $user->getCurrentDepartmentId();

        // Personal stats
        $stats = $this->getPersonalStats($user->id, $buId, $departmentId);

        return Inertia::render('Activity/Analytics/Personal', [
            'stats' => $stats,
        ]);
    }

    /**
     * Department Analytics page
     */
    public function analyticsDepartment(): Response
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');
        $departmentId = $user->getCurrentDepartmentId();

        // Department stats
        $stats = $this->getDepartmentStats($departmentId, $buId);

        return Inertia::render('Activity/Analytics/Department', [
            'stats' => $stats,
        ]);
    }

    /**
     * Business Unit Analytics page (Top Management only)
     */
    public function analyticsBusinessUnit(): Response
    {
        $buId = session('current_business_unit_id');

        // Business Unit stats
        $stats = $this->getBusinessUnitStats($buId);

        return Inertia::render('Activity/Analytics/BusinessUnit', [
            'stats' => $stats,
        ]);
    }

    /**
     * BOD Reporting Dashboard (Top Management only)
     *
     * Displays aggregated metrics across all business units for BOD members.
     * Requires 'view-reports' permission.
     */
    public function reportingDashboard(Request $request): Response
    {
        $this->authorize('view-reports');

        $dateRange = [
            'start' => $request->get('start_date', now()->subDays(30)->format('Y-m-d')),
            'end' => $request->get('end_date', now()->format('Y-m-d')),
        ];

        // Return Inertia React page - data will be loaded via API
        return Inertia::render('Activity/Reporting/BODDashboard', [
            'dateRange' => $dateRange,
            'initialData' => null, // Data loaded via API for better UX
        ]);
    }

    /**
     * Manager Reporting Dashboard
     *
     * Displays detailed team metrics for managers.
     * Shows workload heatmap, team availability, validation queue, and individual metrics.
     */
    public function managerDashboard(Request $request): Response
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');

        $dateRange = [
            'start' => $request->get('start_date', now()->subDays(30)->format('Y-m-d')),
            'end' => $request->get('end_date', now()->format('Y-m-d')),
        ];

        // Get business units the user has access to
        $businessUnits = $user->businessUnits()
            ->with('businessUnit')
            ->get()
            ->pluck('businessUnit')
            ->filter()
            ->values();

        // Get departments for the current business unit
        $departments = \App\Models\Core\Department::where('business_unit_id', $buId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'business_unit_id']);

        return Inertia::render('Activity/Reporting/ManagerDashboard', [
            'dateRange' => $dateRange,
            'businessUnits' => $businessUnits,
            'departments' => $departments,
            'selectedBusinessUnitId' => $buId,
            'selectedDepartmentId' => $user->getCurrentDepartmentId(),
            // Initial data will be loaded via API call from the frontend
            'initialData' => null,
        ]);
    }

    /**
     * Get stats for dashboard
     */
    protected function getStats(int $buId, int $userId, ?int $departmentId): array
    {
        try {
            $baseQuery = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where(function ($q) use ($userId, $departmentId) {
                    $q->where('department_id', $departmentId)
                        ->orWhereHas('participants', fn ($q) => $q->where('user_id', $userId));
                });

            $today = now()->toDateString();

            // Exclude cancelled from total to match frontend display
            return [
                'total' => (clone $baseQuery)->where('status', '!=', 'cancelled')->count(),
                'planned' => (clone $baseQuery)->where('status', 'planned')->count(),
                'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
                'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
                'overdue' => (clone $baseQuery)
                    ->where('due_date', '<', $today)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count(),
            ];
        } catch (\Exception $e) {
            // Table doesn't exist yet (migrations not run)
            return [
                'total' => 0,
                'planned' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'overdue' => 0,
            ];
        }
    }

    /**
     * Get stats for a specific scope (my tasks vs department)
     */
    protected function getStatsForScope(int $buId, int $userId, ?int $departmentId, string $scope): array
    {
        try {
            $baseQuery = EmployeeTask::query()
                ->where('business_unit_id', $buId);

            if ($scope === 'my') {
                // My Tasks: only tasks where user is participant or creator
                $baseQuery->where(function ($q) use ($userId) {
                    $q->whereHas('participants', fn ($q) => $q->where('user_id', $userId))
                        ->orWhere('created_by', $userId);
                });
            } else {
                // Department: all tasks in user's department OR user is participant
                $baseQuery->where(function ($q) use ($userId, $departmentId) {
                    $q->where('department_id', $departmentId)
                        ->orWhereHas('participants', fn ($q) => $q->where('user_id', $userId));
                });
            }

            $today = now()->toDateString();

            $aggregates = (clone $baseQuery)
                ->selectRaw("SUM(CASE WHEN status <> 'cancelled' THEN 1 ELSE 0 END) AS total")
                ->selectRaw("SUM(CASE WHEN status = 'planned' THEN 1 ELSE 0 END) AS planned")
                ->selectRaw("SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress")
                ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed")
                ->selectRaw("SUM(CASE WHEN due_date < ? AND status NOT IN ('completed', 'cancelled') THEN 1 ELSE 0 END) AS overdue", [$today])
                ->first();

            return [
                'total' => (int) ($aggregates->total ?? 0),
                'planned' => (int) ($aggregates->planned ?? 0),
                'in_progress' => (int) ($aggregates->in_progress ?? 0),
                'completed' => (int) ($aggregates->completed ?? 0),
                'overdue' => (int) ($aggregates->overdue ?? 0),
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'planned' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'overdue' => 0,
            ];
        }
    }

    /**
     * Get tasks grouped by activity type
     */
    protected function getByActivityType(int $buId, int $userId, ?int $departmentId): array
    {
        try {
            return EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where(function ($q) use ($userId, $departmentId) {
                    $q->where('department_id', $departmentId)
                        ->orWhereHas('participants', fn ($q) => $q->where('user_id', $userId));
                })
                ->join('employee_activity_types', 'employee_tasks.activity_type_id', '=', 'employee_activity_types.id')
                ->select('employee_activity_types.name', 'employee_activity_types.color', DB::raw('count(*) as count'))
                ->groupBy('employee_activity_types.id', 'employee_activity_types.name', 'employee_activity_types.color')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            // Table doesn't exist yet (migrations not run)
            return [];
        }
    }

    /**
     * Get personal stats for analytics
     */
    protected function getPersonalStats(int $userId, int $buId, ?int $departmentId, string $distributionPeriod = 'all'): array
    {
        try {
            $baseQuery = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where('department_id', $departmentId)
                ->whereHas('participants', fn ($q) => $q->where('user_id', $userId));

            // Apply period filter to match distribution chart
            $baseQuery = $this->applyPeriodFilter($baseQuery, $distributionPeriod);

            $today = now()->toDateString();
            $thisMonth = now()->startOfMonth();

            // Exclude cancelled from total to match frontend display
            return [
                'total' => (clone $baseQuery)->where('status', '!=', 'cancelled')->count(),
                'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
                'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
                'overdue' => (clone $baseQuery)
                    ->where('due_date', '<', $today)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count(),
                'completed_this_month' => (clone $baseQuery)
                    ->where('status', 'completed')
                    ->where('completed_at', '>=', $thisMonth)
                    ->count(),
            ];
        } catch (\Exception $e) {
            // Table doesn't exist yet (migrations not run)
            return [
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'overdue' => 0,
                'completed_this_month' => 0,
            ];
        }
    }

    /**
     * Get department stats for analytics
     */
    protected function getDepartmentStats(int $departmentId, int $buId, string $distributionPeriod = 'all'): array
    {
        try {
            $baseQuery = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where('department_id', $departmentId);

            // Apply period filter to match distribution chart
            $baseQuery = $this->applyPeriodFilter($baseQuery, $distributionPeriod);

            $today = now()->toDateString();

            return [
                'total' => (clone $baseQuery)->count(),
                'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
                'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
                'overdue' => (clone $baseQuery)
                    ->where('due_date', '<', $today)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count(),
            ];
        } catch (\Exception $e) {
            // Table doesn't exist yet (migrations not run)
            return [
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'overdue' => 0,
            ];
        }
    }

    /**
     * Get business unit stats for analytics
     */
    protected function getBusinessUnitStats(int $buId): array
    {
        try {
            $baseQuery = EmployeeTask::query()
                ->where('business_unit_id', $buId);

            $today = now()->toDateString();
            $thisMonth = now()->startOfMonth();

            return [
                'total' => (clone $baseQuery)->count(),
                'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
                'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
                'planned' => (clone $baseQuery)->where('status', 'planned')->count(),
                'overdue' => (clone $baseQuery)
                    ->where('due_date', '<', $today)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count(),
                'completed_this_month' => (clone $baseQuery)
                    ->where('status', 'completed')
                    ->where('completed_at', '>=', $thisMonth)
                    ->count(),
            ];
        } catch (\Exception $e) {
            // Table doesn't exist yet (migrations not run)
            return [
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'planned' => 0,
                'overdue' => 0,
                'completed_this_month' => 0,
            ];
        }
    }

    /**
     * Check if user can view executive dashboard in the current BU context.
     *
     * Returns true if the user has executive/c_level access in the current BU,
     * or is executive in an ancestor BU (e.g., c_level in MRP can see executive
     * tab when viewing MRP or GPR, but not when viewing WNS where they are HoD).
     */
    protected function canViewExecutiveDashboard(User $user, ?int $businessUnitId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $businessUnitId) {
            return false;
        }

        // Check if user has executive access directly in the current BU
        $accessLevel = $user->getAccessLevel($businessUnitId);
        if ($accessLevel === 'executive') {
            return true;
        }

        // Check if user has executive access in an ancestor BU of the current one
        $currentBU = BusinessUnit::find($businessUnitId);
        if ($currentBU) {
            $ancestorId = $currentBU->parent_id;
            $visited = [$businessUnitId];

            while ($ancestorId && ! in_array($ancestorId, $visited)) {
                $visited[] = $ancestorId;
                $ancestorAccessLevel = $user->getAccessLevel($ancestorId);

                if ($ancestorAccessLevel === 'executive') {
                    return true;
                }

                $ancestor = BusinessUnit::find($ancestorId);
                $ancestorId = $ancestor?->parent_id;
            }
        }

        return false;
    }

    /**
     * Get executive overview for top management (cross-BU aggregation).
     *
     * Provides aggregated task metrics across all accessible business units,
     * per-BU breakdown with department drill-down, and top departments by
     * overdue count for executive attention.
     *
     * @return array{aggregate: array, businessUnits: array, topOverdueDepartments: array}
     */
    protected function getExecutiveOverview(User $user): array
    {
        try {
            $accessibleBuIds = $user->getAccessibleBusinessUnitIds();

            if (empty($accessibleBuIds)) {
                return ['aggregate' => $this->emptyStats(), 'businessUnits' => [], 'topOverdueDepartments' => []];
            }

            $today = now()->toDateString();
            $thisMonth = now()->startOfMonth();

            // Aggregate stats across all accessible BUs
            $aggregate = EmployeeTask::query()
                ->whereIn('business_unit_id', $accessibleBuIds)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = "planned" THEN 1 ELSE 0 END) as planned,
                    SUM(CASE WHEN due_date < ? AND status NOT IN ("completed","cancelled") THEN 1 ELSE 0 END) as overdue,
                    SUM(CASE WHEN status = "completed" AND completed_at >= ? THEN 1 ELSE 0 END) as completed_this_month
                ', [$today, $thisMonth])
                ->first();

            // Per-BU breakdown (LEFT JOIN so BUs with zero tasks still appear)
            $buStats = BusinessUnit::query()
                ->whereIn('business_units.id', $accessibleBuIds)
                ->leftJoin('employee_tasks', 'employee_tasks.business_unit_id', '=', 'business_units.id')
                ->groupBy('business_units.id', 'business_units.code', 'business_units.name', 'business_units.logo')
                ->selectRaw('
                    business_units.id as business_unit_id,
                    business_units.code as bu_code,
                    business_units.name as bu_name,
                    business_units.logo as bu_logo,
                    COUNT(employee_tasks.id) as total,
                    SUM(CASE WHEN employee_tasks.status = "completed" THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN employee_tasks.status = "in_progress" THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN employee_tasks.status = "planned" THEN 1 ELSE 0 END) as planned,
                    SUM(CASE WHEN employee_tasks.due_date < ? AND employee_tasks.status NOT IN ("completed","cancelled") THEN 1 ELSE 0 END) as overdue,
                    SUM(CASE WHEN employee_tasks.status = "completed" AND employee_tasks.completed_at >= ? THEN 1 ELSE 0 END) as completed_this_month
                ', [$today, $thisMonth])
                ->orderByDesc('total')
                ->get()
                ->map(function ($row) {
                    $completionRate = $row->total > 0 ? round(($row->completed / $row->total) * 100) : 0;

                    return [
                        'id' => $row->business_unit_id,
                        'code' => $row->bu_code,
                        'name' => $row->bu_name,
                        'logo' => $row->bu_logo ? asset('storage/'.$row->bu_logo) : null,
                        'total' => (int) $row->total,
                        'completed' => (int) $row->completed,
                        'in_progress' => (int) $row->in_progress,
                        'planned' => (int) $row->planned,
                        'overdue' => (int) $row->overdue,
                        'completed_this_month' => (int) $row->completed_this_month,
                        'completion_rate' => $completionRate,
                    ];
                })
                ->values()
                ->toArray();

            // Top overdue departments (executive attention needed)
            $topOverdueDepts = EmployeeTask::query()
                ->whereIn('employee_tasks.business_unit_id', $accessibleBuIds)
                ->where('employee_tasks.due_date', '<', $today)
                ->whereNotIn('employee_tasks.status', ['completed', 'cancelled'])
                ->join('departments', 'departments.id', '=', 'employee_tasks.department_id')
                ->join('business_units', 'business_units.id', '=', 'employee_tasks.business_unit_id')
                ->groupBy('employee_tasks.department_id', 'departments.name', 'employee_tasks.business_unit_id', 'business_units.code')
                ->selectRaw('
                    employee_tasks.department_id,
                    departments.name as dept_name,
                    employee_tasks.business_unit_id,
                    business_units.code as bu_code,
                    COUNT(*) as overdue_count
                ')
                ->orderByDesc('overdue_count')
                ->limit(5)
                ->get()
                ->map(fn ($row) => [
                    'departmentId' => (int) $row->department_id,
                    'department' => $row->dept_name,
                    'businessUnitId' => (int) $row->business_unit_id,
                    'businessUnit' => $row->bu_code,
                    'overdueCount' => (int) $row->overdue_count,
                ])
                ->toArray();

            return [
                'aggregate' => [
                    'total' => (int) ($aggregate->total ?? 0),
                    'completed' => (int) ($aggregate->completed ?? 0),
                    'in_progress' => (int) ($aggregate->in_progress ?? 0),
                    'planned' => (int) ($aggregate->planned ?? 0),
                    'overdue' => (int) ($aggregate->overdue ?? 0),
                    'completed_this_month' => (int) ($aggregate->completed_this_month ?? 0),
                    'total_business_units' => count($buStats),
                ],
                'businessUnits' => $buStats,
                'topOverdueDepartments' => $topOverdueDepts,
            ];
        } catch (\Exception $e) {
            return ['aggregate' => $this->emptyStats(), 'businessUnits' => [], 'topOverdueDepartments' => []];
        }
    }

    /**
     * Return empty stats structure for graceful degradation.
     *
     * @return array{total: int, completed: int, in_progress: int, planned: int, overdue: int, completed_this_month: int}
     */
    private function emptyStats(): array
    {
        return [
            'total' => 0,
            'completed' => 0,
            'in_progress' => 0,
            'planned' => 0,
            'overdue' => 0,
            'completed_this_month' => 0,
        ];
    }

    /**
     * Get visuals for personal dashboard
     */
    protected function getPersonalVisuals(int $userId, int $buId, ?int $departmentId, string $tab = 'todo', string $distributionPeriod = 'all'): array
    {
        try {
            // My Task Roadmap (Active Tasks)
            $query = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where('department_id', $departmentId)
                ->whereHas('participants', fn ($q) => $q->where('user_id', $userId));

            // Tab logic
            if ($tab === 'todo') {
                $query->where('status', 'planned');
            } elseif ($tab === 'inprogress') {
                $query->where('status', 'in_progress');
            } elseif ($tab === 'review') {
                // Placeholder for review status, using completed for now
                $query->where('status', 'completed');
            } else {
                $query->whereIn('status', ['planned', 'in_progress']);
            }

            $roadmap = $query->with(['activityType', 'subActivity', 'participants.primaryPosition'])
                ->orderBy('due_date', 'asc')
                ->paginate(10)
                ->withQueryString();

            // Upcoming Deadlines (Next 7 days)
            $upcoming = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where('department_id', $departmentId)
                ->whereHas('participants', fn ($q) => $q->where('user_id', $userId))
                ->whereIn('status', ['planned', 'in_progress'])
                ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->orderBy('due_date', 'asc')
                ->take(5)
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->task_title,
                    'due_date' => $t->due_date ? $t->due_date->format('Y-m-d') : null,
                    'is_critical' => $t->due_date ? $t->due_date->lt(now()->addDays(2)) : false,
                ])
                ->toArray();

            // Distribution by Category with period filter (exclude cancelled)
            $distributionQuery = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where('department_id', $departmentId)
                ->where('status', '!=', 'cancelled')
                ->whereHas('participants', fn ($q) => $q->where('user_id', $userId));

            // Apply period filter
            $distributionQuery = $this->applyPeriodFilter($distributionQuery, $distributionPeriod);

            $distribution = $distributionQuery
                ->join('employee_activity_types', 'employee_tasks.activity_type_id', '=', 'employee_activity_types.id')
                ->select('employee_activity_types.name', 'employee_activity_types.color', DB::raw('count(*) as value'))
                ->groupBy('employee_activity_types.id', 'employee_activity_types.name', 'employee_activity_types.color')
                ->get()
                ->toArray();

            return [
                'roadmap' => $roadmap,
                'upcoming' => $upcoming,
                'distribution' => $distribution,
            ];
        } catch (\Exception $e) {
            return [
                'roadmap' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 6),
                'upcoming' => [],
                'distribution' => [],
            ];
        }
    }

    /**
     * Apply period filter to query
     */
    protected function applyPeriodFilter($query, string $period)
    {
        return match ($period) {
            'today' => $query->whereDate('task_date', now()->toDateString()),
            'week' => $query->whereBetween('task_date', [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
            ]),
            'month' => $query->whereBetween('task_date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ]),
            'year' => $query->whereBetween('task_date', [
                now()->startOfYear()->toDateString(),
                now()->endOfYear()->toDateString(),
            ]),
            default => $query, // 'all' - no filter
        };
    }

    /**
     * Get visuals for department dashboard
     */
    protected function getDepartmentVisuals(int $departmentId, int $buId, string $distributionPeriod = 'all'): array
    {
        try {
            $tab = request()->input('dept_tab', 'inprogress');

            // Department Task Roadmap (Paginated) - same as personal but for whole department
            $query = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where('department_id', $departmentId);

            // Tab logic
            if ($tab === 'todo') {
                $query->where('status', 'planned');
            } elseif ($tab === 'inprogress') {
                $query->where('status', 'in_progress');
            } elseif ($tab === 'review') {
                $query->where('status', 'completed');
            } else {
                $query->whereIn('status', ['planned', 'in_progress']);
            }

            $roadmap = $query->with(['activityType', 'subActivity', 'participants.primaryPosition'])
                ->orderBy('due_date', 'asc')
                ->paginate(20, ['*'], 'dept_page')
                ->withQueryString();

            // Upcoming Deadlines (Next 7 days) for department
            $upcoming = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where('department_id', $departmentId)
                ->whereIn('status', ['planned', 'in_progress'])
                ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->orderBy('due_date', 'asc')
                ->take(5)
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->task_title,
                    'due_date' => $t->due_date ? $t->due_date->format('Y-m-d') : null,
                    'is_critical' => $t->due_date ? $t->due_date->lt(now()->addDays(2)) : false,
                ])
                ->toArray();

            // Distribution by Category for department with period filter
            $distributionQuery = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where('department_id', $departmentId);

            // Apply period filter
            $distributionQuery = $this->applyPeriodFilter($distributionQuery, $distributionPeriod);

            $distribution = $distributionQuery
                ->join('employee_activity_types', 'employee_tasks.activity_type_id', '=', 'employee_activity_types.id')
                ->select('employee_activity_types.name', 'employee_activity_types.color', DB::raw('count(*) as value'))
                ->groupBy('employee_activity_types.id', 'employee_activity_types.name', 'employee_activity_types.color')
                ->get()
                ->toArray();

            // Bottleneck (Overdue Tasks)
            $bottleneck = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where('department_id', $departmentId)
                ->where('due_date', '<', now()->toDateString())
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count();

            // Top Category
            $topCategory = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where('department_id', $departmentId)
                ->join('employee_activity_types', 'employee_tasks.activity_type_id', '=', 'employee_activity_types.id')
                ->select('employee_activity_types.name', DB::raw('count(*) as count'))
                ->groupBy('employee_activity_types.id', 'employee_activity_types.name')
                ->orderByDesc('count')
                ->first();

            return [
                'roadmap' => $roadmap,
                'upcoming' => $upcoming,
                'distribution' => $distribution,
                'bottleneck' => $bottleneck,
                'top_category' => $topCategory ? $topCategory->name : '-',
            ];
        } catch (\Exception $e) {
            return [
                'roadmap' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10),
                'upcoming' => [],
                'distribution' => [],
                'bottleneck' => 0,
                'top_category' => '-',
            ];
        }
    }

    /**
     * Display backdate requests page (user's own requests)
     */
    public function backdateRequests(): Response
    {
        abort_unless(config('features.backdate_approval'), 404);

        $user = Auth::user();

        $requests = \App\Models\Modules\Activity\BackdatePermission::forUser($user->id)
            ->with(['approver', 'rejector', 'department'])
            ->latest()
            ->paginate(10);

        $activePermission = $this->backdateService->checkUserPermission($user->id);

        $hasPendingRequest = \App\Models\Modules\Activity\BackdatePermission::forUser($user->id)
            ->pending()
            ->exists();

        return Inertia::render('Activity/Backdate/Requests', [
            'requests' => $requests,
            'activePermission' => $activePermission,
            'hasPendingRequest' => $hasPendingRequest,
        ]);
    }

    /**
     * Display backdate approvals page (for department heads)
     */
    public function backdateApprovals(Request $request): Response
    {
        abort_unless(config('features.backdate_approval'), 404);

        $user = Auth::user();
        $buId = session('current_business_unit_id');
        $departmentId = $user->getCurrentDepartmentId();

        // Check if user is department head or super admin
        $accessLevel = $user->getAccessLevel();
        if (! in_array($accessLevel, ['department_head', 'super_admin', 'executive', 'general_manager'])) {
            abort(403, 'Only department heads can access this page');
        }

        $statusFilter = $request->get('status', 'pending');

        $query = \App\Models\Modules\Activity\BackdatePermission::query()
            ->with(['user', 'approver', 'rejector', 'department'])
            ->where('business_unit_id', $buId);

        // Filter by department (unless super admin)
        if (! $user->isSuperAdmin()) {
            $query->where('department_id', $departmentId);
        }

        // Apply status filter
        if ($statusFilter !== 'all') {
            if ($statusFilter === 'pending') {
                $query->pending();
            } else {
                $query->where('status', $statusFilter);
            }
        }

        $requests = $query->latest()->paginate(15);

        // Get pending count
        $pendingQuery = \App\Models\Modules\Activity\BackdatePermission::pending()
            ->where('business_unit_id', $buId);

        if (! $user->isSuperAdmin()) {
            $pendingQuery->where('department_id', $departmentId);
        }

        $pendingCount = $pendingQuery->count();

        return Inertia::render('Activity/Backdate/Approvals', [
            'requests' => $requests,
            'pendingCount' => $pendingCount,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * Approve a backdate request
     */
    public function approveBackdate(int $id): RedirectResponse
    {
        abort_unless(config('features.backdate_approval'), 404);

        try {
            $request = \App\Models\Modules\Activity\BackdatePermission::findOrFail($id);
            $user = Auth::user();

            // Verify this request is from the user's department
            if ($request->department_id !== $user->getCurrentDepartmentId() && ! $user->isSuperAdmin()) {
                throw new \Exception('You can only approve requests from your department');
            }

            $this->backdateService->approveRequest($request, $user);

            return back()->with('success', 'Backdate request approved successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject a backdate request
     */
    public function rejectBackdate(Request $request, int $id): RedirectResponse
    {
        abort_unless(config('features.backdate_approval'), 404);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10|max:500',
        ], [
            'rejection_reason.required' => 'Please provide a reason for rejection',
            'rejection_reason.min' => 'Rejection reason must be at least 10 characters',
            'rejection_reason.max' => 'Rejection reason cannot exceed 500 characters',
        ]);

        try {
            $backdateRequest = \App\Models\Modules\Activity\BackdatePermission::findOrFail($id);
            $user = Auth::user();

            // Verify this request is from the user's department
            if ($backdateRequest->department_id !== $user->getCurrentDepartmentId() && ! $user->isSuperAdmin()) {
                throw new \Exception('You can only reject requests from your department');
            }

            $this->backdateService->rejectRequest($backdateRequest, $user, $validated['rejection_reason']);

            return back()->with('success', 'Backdate request rejected');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Submit backdate request from React/Inertia
     */
    public function submitBackdateRequest(Request $request): RedirectResponse
    {
        abort_unless(config('features.backdate_approval'), 404);

        $validated = $request->validate([
            'requested_date' => 'required|date|before:today',
            'reason' => 'required|string|min:10|max:500',
        ], [
            'requested_date.required' => 'Please select the date you need to backdate to',
            'requested_date.before' => 'Requested date must be before today',
            'reason.required' => 'Please provide a reason for backdate access',
            'reason.min' => 'Reason must be at least 10 characters',
            'reason.max' => 'Reason cannot exceed 500 characters',
        ]);

        try {
            $user = Auth::user();

            $permission = $this->backdateService->requestPermission([
                'requested_date' => $validated['requested_date'],
                'reason' => $validated['reason'],
            ], $user);

            return back()->with('success', 'Backdate request submitted successfully. Your department head will review it.');
        } catch (\Exception $e) {
            return back()->withErrors(['reason' => $e->getMessage()]);
        }
    }

    /**
     * Clear related caches
     */
    protected function clearCache(int $buId, int $userId): void
    {
        Cache::forget("activity_stats_{$buId}_{$userId}");
    }

    /**
     * Get activity types assigned to a department, ordered by pivot sort_order
     *
     * Queries via department_activity_types pivot table (Requirements 5.1)
     * Orders by department's configured sort_order (Requirements 5.3)
     */
    protected function getDepartmentActivityTypes(?int $departmentId): array
    {
        if (! $departmentId) {
            return [];
        }

        $department = \App\Models\Core\Department::find($departmentId);
        if (! $department) {
            return [];
        }

        return $department->activeActivityTypes()
            ->select(['employee_activity_types.id', 'employee_activity_types.code', 'employee_activity_types.name', 'employee_activity_types.color'])
            ->with(['subActivities' => function ($query) {
                $query->where('is_active', true)
                    ->select(['id', 'activity_type_id', 'code', 'name'])
                    ->orderBy('sort_order')
                    ->orderBy('name');
            }])
            ->get()
            ->map(fn ($type) => [
                'id' => $type->id,
                'code' => $type->code,
                'name' => $type->name,
                'color' => $type->color,
                'sub_activities' => $type->subActivities->map(fn ($sub) => [
                    'id' => $sub->id,
                    'activity_type_id' => $sub->activity_type_id,
                    'code' => $sub->code,
                    'name' => $sub->name,
                ])->values()->toArray(),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get valid activity type IDs for a department
     *
     * Used for validation to ensure selected activity type is assigned to department
     * (Requirements 5.1)
     */
    protected function getValidActivityTypeIds(?int $departmentId): array
    {
        if (! $departmentId) {
            return [];
        }

        $department = \App\Models\Core\Department::find($departmentId);
        if (! $department) {
            return [];
        }

        return $department->activeActivityTypes()
            ->pluck('employee_activity_types.id')
            ->toArray();
    }

    /**
     * Export activities to XLSX
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');
        $scope = $request->get('scope', 'my'); // 'my' or 'department'

        $exportService = app(\App\Services\Modules\Activity\ActivityExportService::class);

        // If scope is 'my', filter by current user; if 'department', get all department tasks
        $userId = $scope === 'my' ? $user->id : null;
        $departmentId = $user->getCurrentDepartmentId();

        return $exportService->exportToXlsx(
            businessUnitId: $buId,
            departmentId: $departmentId,
            userId: $userId,
            dateFrom: $request->get('date_from'),
            dateTo: $request->get('date_to'),
            status: $request->get('status'),
            activityTypeId: $request->get('activity_type_id')
        );
    }

    /**
     * Format prioritized activity types for frontend consumption
     *
     * Returns activity types grouped by priority with sub-activities loaded
     */
    protected function formatPrioritizedActivityTypes(array $prioritized): array
    {
        $format = function ($types, string $priority) {
            return $types->map(function ($type) use ($priority) {
                return [
                    'id' => $type->id,
                    'code' => $type->code,
                    'name' => $type->name,
                    'color' => $type->color,
                    'priority' => $priority,
                    'sub_activities' => $type->subActivities->map(fn ($s) => [
                        'id' => $s->id,
                        'activity_type_id' => $s->activity_type_id,
                        'code' => $s->code,
                        'name' => $s->name,
                    ])->values()->toArray(),
                ];
            });
        };

        return [
            'favorites' => $format($prioritized['favorites'], 'favorite')->values()->toArray(),
            'department' => $format($prioritized['department'], 'department')->values()->toArray(),
            'others' => $format($prioritized['others'], 'other')->values()->toArray(),
        ];
    }
}
