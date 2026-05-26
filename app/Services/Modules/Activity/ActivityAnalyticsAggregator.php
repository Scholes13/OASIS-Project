<?php

namespace App\Services\Modules\Activity;

use App\Models\Core\Department;
use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates activity-related stats for personal, scoped, department,
 * business-unit and executive analytics views.
 *
 * Lifted verbatim from ActivityInertiaController:
 *  - getStats / getStatsForScope
 *  - getByActivityType
 *  - getPersonalStats / getDepartmentStats / getBusinessUnitStats
 *  - getExecutiveOverview / canViewExecutiveDashboard / emptyStats
 *  - applyPeriodFilter (helper)
 */
class ActivityAnalyticsAggregator
{
    public function __construct(
        protected ActivityMemberFocusService $memberFocusService,
        protected TaskScopeResolver $scopeResolver
    ) {}

    /**
     * Get stats for a specific scope (my tasks vs department).
     */
    public function getStatsForScope(int $buId, int $userId, ?int $departmentId, string $scope, ?int $memberUserId = null): array
    {
        try {
            $baseQuery = $this->scopeResolver->buildTaskScopeQuery($buId, $userId, $departmentId, $scope);
            $baseQuery = $this->memberFocusService->applyMemberFocus($baseQuery, $memberUserId);

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
     * Get tasks grouped by activity type.
     */
    public function getByActivityType(int $buId, int $userId, ?int $departmentId): array
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
            return [];
        }
    }

    /**
     * Get personal stats for analytics.
     */
    public function getPersonalStats(int $userId, int $buId, ?int $departmentId, string $distributionPeriod = 'all'): array
    {
        try {
            $baseQuery = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where('department_id', $departmentId)
                ->whereHas('participants', fn ($q) => $q->where('user_id', $userId));

            $baseQuery = $this->applyPeriodFilter($baseQuery, $distributionPeriod);

            $today = now()->toDateString();
            $thisMonth = now()->startOfMonth();

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
     * Get department stats for analytics.
     */
    public function getDepartmentStats(int $departmentId, int $buId, string $distributionPeriod = 'all', ?int $memberUserId = null): array
    {
        try {
            $scopeIds = Department::scopeIdsForId($departmentId);

            $baseQuery = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->whereIn('department_id', $scopeIds);

            $baseQuery = $this->memberFocusService->applyMemberFocus($baseQuery, $memberUserId);
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
            return [
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'overdue' => 0,
            ];
        }
    }

    /**
     * Get business unit stats for analytics.
     */
    public function getBusinessUnitStats(int $buId): array
    {
        try {
            $baseQuery = EmployeeTask::query()->where('business_unit_id', $buId);

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
     * Apply period filter to query (today/week/month/year/all).
     *
     * @template T of \Illuminate\Database\Eloquent\Builder
     *
     * @param  T  $query
     * @return T
     */
    public function applyPeriodFilter($query, string $period)
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
            default => $query,
        };
    }
}
