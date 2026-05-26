<?php

namespace App\Services\Modules\Activity;

use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

/**
 * Builds the Task index listing pipeline (filters + paginated query +
 * view-mode shaping + breakdown). Returns shaped data for the controller
 * to feed straight into Inertia::render.
 *
 * Lifted verbatim from ActivityInertiaController::index() to preserve
 * pagination, filter and view-mode behavior.
 */
class TaskQueryBuilder
{
    public function __construct(
        protected ActivityMemberFocusService $memberFocusService,
        protected TaskScopeResolver $scopeResolver,
        protected TaskPresenter $presenter,
        protected ActivityAnalyticsAggregator $analyticsAggregator
    ) {}

    /**
     * Resolve the parsed filters array (with sanitized member focus).
     *
     * @return array<string, mixed>
     */
    public function resolveFilters(Request $request, ?int $buId, ?int $departmentId, int $userId): array
    {
        $scope = $request->get('scope', 'my');

        return [
            'search' => $request->get('search', ''),
            'activity_type_id' => $request->get('activity_type_id', ''),
            'status' => $request->get('status', ''),
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
            'scope' => $scope,
            'member_user_id' => '',
        ];
    }

    /**
     * Resolve team members and the sanitized member_user_id for the scope.
     *
     * @return array{teamMembers: array<int, array<string, mixed>>, memberUserId: ?int}
     */
    public function resolveTeamMembers(Request $request, ?int $buId, ?int $departmentId, string $scope): array
    {
        $teamMembers = $this->memberFocusService->resolveDepartmentMembers($buId, $departmentId);
        $memberUserId = $scope === 'department'
            ? $this->memberFocusService->sanitizeRequestedMember($request->query('member_user_id'), $teamMembers)
            : null;

        return ['teamMembers' => $teamMembers, 'memberUserId' => $memberUserId];
    }

    /**
     * Build the filtered task query (filters + member focus + date range).
     *
     * @param  array<string, mixed>  $filters
     */
    public function buildFilteredQuery(int $buId, int $userId, ?int $departmentId, string $scope, ?int $memberUserId, array $filters): Builder
    {
        $query = $this->scopeResolver->buildTaskScopeQuery($buId, $userId, $departmentId, $scope);
        $query = $this->memberFocusService->applyMemberFocus($query, $memberUserId);

        $query->when($filters['activity_type_id'], fn ($q, $v) => $q->where('activity_type_id', $v))
            ->when($filters['status'], fn ($q, $v) => $q->where('status', $v))
            ->when($filters['search'], fn ($q, $v) => $q->where('task_title', 'like', "%{$v}%"));

        if ($filters['date_from'] || $filters['date_to']) {
            $query->where(function ($q) use ($filters): void {
                $q->where(function ($sub) use ($filters): void {
                    $sub->where('status', '!=', 'completed');
                    if ($filters['date_from']) {
                        $sub->whereDate('task_date', '>=', $filters['date_from']);
                    }
                    if ($filters['date_to']) {
                        $sub->whereDate('task_date', '<=', $filters['date_to']);
                    }
                });
                $q->orWhere(function ($sub) use ($filters): void {
                    $sub->where('status', 'completed')
                        ->whereNotNull('completed_at');
                    if ($filters['date_from']) {
                        $sub->whereDate('completed_at', '>=', $filters['date_from']);
                    }
                    if ($filters['date_to']) {
                        $sub->whereDate('completed_at', '<=', $filters['date_to']);
                    }
                });
            });
        }

        return $query;
    }

    /**
     * Materialize the task collection (paginated for list view, capped 200
     * for board/calendar/timeline) and hydrate avatars.
     */
    public function paginateForView(Builder $query, string $view): LengthAwarePaginator
    {
        $userColumns = ['id', 'name'];
        if (Schema::hasColumn('users', 'avatar_url')) {
            $userColumns[] = 'avatar_url';
        }

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
                'participants:'.implode(',', $userColumns),
                'creator:'.implode(',', $userColumns),
                'department:id,name,code',
            ])
            ->latest('id');

        if (in_array($view, ['board', 'calendar', 'timeline'])) {
            $allTasks = $taskQuery->whereNotIn('status', ['cancelled'])->limit(200)->get();
            $allTasks->each(fn (EmployeeTask $task) => $this->presenter->hydrateTaskAvatars($task));

            return new LengthAwarePaginator(
                $allTasks,
                $allTasks->count(),
                $allTasks->count() ?: 1,
                1
            );
        }

        $tasks = $taskQuery->paginate(20)->withQueryString();
        $tasks->getCollection()->each(fn (EmployeeTask $task) => $this->presenter->hydrateTaskAvatars($task));

        return $tasks;
    }
}
