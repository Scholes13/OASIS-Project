<?php

namespace App\Http\Controllers\Modules\Activity;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\EmployeeTask;
use App\Services\Modules\Activity\TaskService;
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
        protected TaskService $taskService
    ) {
    }

    /**
     * Display the Activity Dashboard (Personal & Department Analytics)
     */
    public function dashboard(): Response
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');
        $departmentId = $user->primary_department_id;

        // Personal stats
        $personalStats = $this->getPersonalStats($user->id, $buId);
        
        // Department stats (if user has permission)
        $departmentStats = null;
        if ($user->can('view-department-analytics')) {
            $departmentStats = $this->getDepartmentStats($departmentId, $buId);
        }

        return Inertia::render('Activity/ActivityDashboard', [
            'personalStats' => $personalStats,
            'departmentStats' => $departmentStats,
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

        return Inertia::render('Activity/TaskForm', [
            'task' => null,
            'activityTypes' => ActivityType::with('subActivities')->get(),
            'departmentUsers' => $departmentUsers,
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
            'due_date' => 'required|date',
            'start_date' => 'nullable|date',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'exists:users,id',
        ]);

        $user = Auth::user();
        $buId = session('current_business_unit_id');

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
                'due_date' => $validated['due_date'],
                'start_date' => $validated['start_date'] ?? null,
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

        return Inertia::render('Activity/TaskForm', [
            'task' => $task,
            'activityTypes' => ActivityType::with('subActivities')->get(),
            'departmentUsers' => $departmentUsers,
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
            'start_date' => 'nullable|date',
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
                'start_date' => $validated['start_date'] ?? null,
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
     * Get stats for dashboard
     */
    protected function getStats(int $buId, int $userId, ?int $departmentId): array
    {
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
    }

    /**
     * Get tasks grouped by activity type
     */
    protected function getByActivityType(int $buId, int $userId, ?int $departmentId): array
    {
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
    }

    /**
     * Get personal stats for analytics
     */
    protected function getPersonalStats(int $userId, int $buId): array
    {
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
    }

    /**
     * Get department stats for analytics
     */
    protected function getDepartmentStats(int $departmentId, int $buId): array
    {
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
    }

    /**
     * Get business unit stats for analytics
     */
    protected function getBusinessUnitStats(int $buId): array
    {
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
    }

    /**
     * Clear related caches
     */
    protected function clearCache(int $buId, int $userId): void
    {
        Cache::forget("activity_stats_{$buId}_{$userId}");
    }
}
