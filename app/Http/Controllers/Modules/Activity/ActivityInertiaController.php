<?php

namespace App\Http\Controllers\Modules\Activity;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\EmployeeTask;
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
        protected BackdatePermissionService $backdateService
    ) {
    }

    /**
     * Display the Activity Dashboard (Personal & Department Analytics)
     * 
     * BOD users (with 'view-reports' permission) are automatically redirected
     * to the BOD Reporting Dashboard for a cleaner UX.
     */
    public function dashboard()
    {
        $user = Auth::user();

        // BOD users should go directly to reporting dashboard
        if ($user->can('view-reports')) {
            return redirect()->route('activity.reporting');
        }

        $buId = session('current_business_unit_id');
        $departmentId = $user->primary_department_id;

        // Personal stats & visuals
        $tab = request()->input('tab', 'todo');
        $personalStats = $this->getPersonalStats($user->id, $buId);
        $personalVisuals = $this->getPersonalVisuals($user->id, $buId, $tab);

        // Department stats & visuals (if user has permission)
        $departmentStats = null;
        $departmentVisuals = null;
        if ($user->can('view-department-analytics')) {
            $departmentStats = $this->getDepartmentStats($departmentId, $buId);
            $departmentVisuals = $this->getDepartmentVisuals($departmentId, $buId);
        }

        return Inertia::render('Activity/ActivityDashboard', [
            'personalStats' => $personalStats,
            'personalVisuals' => $personalVisuals,
            'departmentStats' => $departmentStats,
            'departmentVisuals' => $departmentVisuals,
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
        $departmentId = $user->primary_department_id;

        // Parse filters from request
        $filters = [
            'search' => $request->get('search', ''),
            'activity_type_id' => $request->get('activity_type_id', ''),
            'status' => $request->get('status', ''),
            'date_from' => $request->get('date_from', now()->subMonths(3)->format('Y-m-d')),
            'date_to' => $request->get('date_to', now()->format('Y-m-d')),
        ];

        try {
            // Get cached stats
            $stats = Cache::remember(
                "activity_stats_{$buId}_{$user->id}",
                self::CACHE_TTL,
                fn() => $this->getStats($buId, $user->id, $departmentId)
            );

            // Build base query
            $query = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where(function ($q) use ($user, $departmentId) {
                    $q->where('department_id', $departmentId)
                        ->orWhereHas('participants', fn($q) => $q->where('user_id', $user->id));
                })
                ->when($filters['activity_type_id'], fn($q, $v) => $q->where('activity_type_id', $v))
                ->when($filters['status'], fn($q, $v) => $q->where('status', $v))
                ->when($filters['search'], fn($q, $v) => $q->where('task_title', 'like', "%{$v}%"))
                ->when($filters['date_from'], fn($q, $v) => $q->whereDate('created_at', '>=', $v))
                ->when($filters['date_to'], fn($q, $v) => $q->whereDate('created_at', '<=', $v));

            // Get paginated tasks with relationships
            $tasks = (clone $query)
                ->with(['activityType', 'subActivity', 'participants', 'creator', 'department'])
                ->latest()
                ->paginate(20)
                ->withQueryString();

            // Get by activity type breakdown
            $byActivityType = $this->getByActivityType($buId, $user->id, $departmentId);

            return Inertia::render('Activity/Dashboard', [
                'stats' => $stats,
                'tasks' => $tasks,
                'activityTypes' => ActivityType::all(),
                'filters' => $filters,
                'byActivityType' => $byActivityType,
            ]);
        } catch (\Exception $e) {
            // Table doesn't exist yet (migrations not run)
            return Inertia::render('Activity/Dashboard', [
                'stats' => [
                    'total' => 0,
                    'completed' => 0,
                    'in_progress' => 0,
                    'overdue' => 0,
                ],
                'tasks' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
                'activityTypes' => ActivityType::all(),
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
        $departmentUsers = User::where('primary_department_id', $user->primary_department_id)
            ->where('id', '!=', $user->id)
            ->select(['id', 'name', 'email'])
            ->get();

        // Get backdate permission info
        $backdatePermission = $this->backdateService->checkUserPermission($user->id);
        $allowedDateRange = $this->backdateService->getAllowedDateRange($user);

        return Inertia::render('Activity/TaskForm', [
            'task' => null,
            'activityTypes' => ActivityType::with('subActivities')->get(),
            'departmentUsers' => $departmentUsers,
            'backdatePermission' => $backdatePermission ? [
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
        $validated = $request->validate([
            'task_title' => 'required|string|max:255',
            'task_description' => 'nullable|string',
            'activity_type_id' => 'required|exists:employee_activity_types,id',
            'sub_activity_id' => 'nullable|exists:employee_sub_activities,id',
            'status' => 'required|in:planned,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high',
            'task_date' => 'required|date',
            'due_date' => 'required|date',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'exists:users,id',
        ]);

        $user = Auth::user();
        $buId = session('current_business_unit_id');

        // Validate task_date against backdate permission
        $taskDate = \Carbon\Carbon::parse($validated['task_date']);
        if (!$this->backdateService->canCreateTaskWithDate($user, $taskDate)) {
            $allowedRange = $this->backdateService->getAllowedDateRange($user);
            return back()
                ->withInput()
                ->withErrors([
                    'task_date' => 'Task date is outside allowed range. You can only create tasks from ' .
                        $allowedRange['from']->format('Y-m-d') . ' to ' . 
                        $allowedRange['to']->format('Y-m-d') . '. ' .
                        'Request backdate access if you need to create tasks with older dates.'
                ]);
        }

        DB::beginTransaction();
        try {
            $task = EmployeeTask::create([
                'business_unit_id' => $buId,
                'department_id' => $user->primary_department_id,
                'created_by' => $user->id,
                'activity_type_id' => $validated['activity_type_id'],
                'sub_activity_id' => $validated['sub_activity_id'] ?? null,
                'task_title' => $validated['task_title'],
                'task_description' => $validated['task_description'] ?? null,
                'status' => $validated['status'],
                'priority' => $validated['priority'],
                'task_date' => $validated['task_date'],
                'due_date' => $validated['due_date'],
            ]);

            // Add creator as owner participant
            $task->participants()->attach($user->id, [
                'is_owner' => true,
                'joined_at' => now(),
            ]);

            // Add other participants
            if (!empty($validated['participant_ids'])) {
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
                ->route('activity.task.show', $task)
                ->with('success', 'Task created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create task: ' . $e->getMessage());
        }
    }

    /**
     * Show edit task form
     */
    public function edit(EmployeeTask $task): Response
    {
        $task->load(['activityType', 'subActivity', 'participants']);

        $user = Auth::user();
        $departmentUsers = User::where('primary_department_id', $user->primary_department_id)
            ->where('id', '!=', $user->id)
            ->select(['id', 'name', 'email'])
            ->get();

        // Get backdate permission info
        $backdatePermission = $this->backdateService->checkUserPermission($user->id);
        $allowedDateRange = $this->backdateService->getAllowedDateRange($user);

        return Inertia::render('Activity/TaskForm', [
            'task' => $task,
            'activityTypes' => ActivityType::with('subActivities')->get(),
            'departmentUsers' => $departmentUsers,
            'backdatePermission' => $backdatePermission ? [
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
        $isPartialUpdate = $request->has('due_date') && !$request->has('task_title');
        $isStatusUpdate = $request->has('status') && !$request->has('task_title');

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
                if ($validated['status'] === 'in_progress' && !$task->started_at) {
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
        $validated = $request->validate([
            'task_title' => 'required|string|max:255',
            'task_description' => 'nullable|string',
            'activity_type_id' => 'required|exists:employee_activity_types,id',
            'sub_activity_id' => 'nullable|exists:employee_sub_activities,id',
            'status' => 'required|in:planned,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'required|date',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'exists:users,id',
        ]);

        $user = Auth::user();
        $buId = session('current_business_unit_id');

        DB::beginTransaction();
        try {
            $task->update([
                'activity_type_id' => $validated['activity_type_id'],
                'sub_activity_id' => $validated['sub_activity_id'] ?? null,
                'task_title' => $validated['task_title'],
                'task_description' => $validated['task_description'] ?? null,
                'status' => $validated['status'],
                'priority' => $validated['priority'],
                'due_date' => $validated['due_date'],
                'completed_at' => $validated['status'] === 'completed' ? now() : null,
            ]);

            // Update participants (keep owner, sync others)
            $ownerId = $task->participants()->where('is_owner', true)->first()?->user_id;
            $newParticipants = [$ownerId => ['is_owner' => true, 'joined_at' => now()]];

            if (!empty($validated['participant_ids'])) {
                foreach ($validated['participant_ids'] as $participantId) {
                    if ($participantId != $ownerId) {
                        $newParticipants[$participantId] = ['is_owner' => false, 'joined_at' => now()];
                    }
                }
            }

            $task->participants()->sync($newParticipants);

            // Clear cache
            $this->clearCache($buId, $user->id);

            DB::commit();

            return redirect()
                ->route('activity.task.show', $task)
                ->with('success', 'Task updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update task: ' . $e->getMessage());
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
            return back()->with('error', 'Failed to delete task: ' . $e->getMessage());
        }
    }

    /**
     * Department Tasks page
     */
    public function department(Request $request): Response
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');
        $departmentId = $user->primary_department_id;

        try {
            $tasks = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where('department_id', $departmentId)
                ->whereDoesntHave('participants', fn($q) => $q->where('user_id', $user->id))
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

        // Personal stats
        $stats = $this->getPersonalStats($user->id, $buId);

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
        $departmentId = $user->primary_department_id;

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
            'selectedDepartmentId' => $user->primary_department_id,
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
                        ->orWhereHas('participants', fn($q) => $q->where('user_id', $userId));
                });

            $today = now()->toDateString();

            return [
                'total' => (clone $baseQuery)->count(),
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
     * Get tasks grouped by activity type
     */
    protected function getByActivityType(int $buId, int $userId, ?int $departmentId): array
    {
        try {
            return EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where(function ($q) use ($userId, $departmentId) {
                    $q->where('department_id', $departmentId)
                        ->orWhereHas('participants', fn($q) => $q->where('user_id', $userId));
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
    protected function getPersonalStats(int $userId, int $buId): array
    {
        try {
            $baseQuery = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->whereHas('participants', fn($q) => $q->where('user_id', $userId));

            $today = now()->toDateString();
            $thisMonth = now()->startOfMonth();

            return [
                'total' => (clone $baseQuery)->count(),
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
    protected function getDepartmentStats(int $departmentId, int $buId): array
    {
        try {
            $baseQuery = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where('department_id', $departmentId);

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
     * Get visuals for personal dashboard
     */
    protected function getPersonalVisuals(int $userId, int $buId, string $tab = 'todo'): array
    {
        try {
            // My Task Roadmap (Active Tasks)
            $query = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->whereHas('participants', fn($q) => $q->where('user_id', $userId));

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

            $roadmap = $query->with(['activityType', 'subActivity', 'participants'])
                ->orderBy('due_date', 'asc')
                ->paginate(10)
                ->withQueryString();

            // Upcoming Deadlines (Next 7 days)
            $upcoming = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->whereHas('participants', fn($q) => $q->where('user_id', $userId))
                ->whereIn('status', ['planned', 'in_progress'])
                ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->orderBy('due_date', 'asc')
                ->take(5)
                ->get()
                ->map(fn($t) => [
                    'id' => $t->id,
                    'title' => $t->task_title,
                    'due_date' => $t->due_date->format('Y-m-d'),
                    'is_critical' => $t->due_date->lt(now()->addDays(2)),
                ])
                ->toArray();

            // Distribution by Category
            $distribution = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->whereHas('participants', fn($q) => $q->where('user_id', $userId))
                ->whereIn('status', ['planned', 'in_progress'])
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
                'distribution' => []
            ];
        }
    }

    /**
     * Get visuals for department dashboard
     */
    protected function getDepartmentVisuals(int $departmentId, int $buId): array
    {
        try {
            $tab = request()->input('dept_tab', 'todo');

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

            $roadmap = $query->with(['activityType', 'subActivity', 'participants'])
                ->orderBy('due_date', 'asc')
                ->paginate(10, ['*'], 'dept_page')
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
                ->map(fn($t) => [
                    'id' => $t->id,
                    'title' => $t->task_title,
                    'due_date' => $t->due_date ? $t->due_date->format('Y-m-d') : null,
                    'is_critical' => $t->due_date ? $t->due_date->lt(now()->addDays(2)) : false,
                ])
                ->toArray();

            // Distribution by Category for department
            $distribution = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where('department_id', $departmentId)
                ->whereIn('status', ['planned', 'in_progress'])
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
                'top_category' => '-'
            ];
        }
    }

    /**
     * Submit backdate request from React/Inertia
     */
    public function submitBackdateRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:500',
        ], [
            'reason.required' => 'Please provide a reason for backdate access',
            'reason.min' => 'Reason must be at least 10 characters',
            'reason.max' => 'Reason cannot exceed 500 characters',
        ]);

        try {
            $user = Auth::user();
            
            $permission = $this->backdateService->requestPermission([
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
}
