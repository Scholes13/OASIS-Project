<?php

namespace App\Services\Modules\Activity;

use App\Models\Core\Department;
use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Builds visual payloads (roadmap, distribution, deadlines, bottleneck, top
 * category) for the personal and department dashboards.
 *
 * Lifted verbatim from ActivityInertiaController:
 *  - getPersonalVisuals
 *  - getDepartmentVisuals
 */
class ActivityVisualsBuilder
{
    public function __construct(
        protected ActivityMemberFocusService $memberFocusService,
        protected ActivityReportAggregationService $reportAggregationService,
        protected ActivityAnalyticsAggregator $analyticsAggregator
    ) {}

    /**
     * Get visuals for personal dashboard.
     */
    public function getPersonalVisuals(int $userId, int $buId, ?int $departmentId, string $tab = 'todo', string $distributionPeriod = 'all'): array
    {
        try {
            $query = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where('department_id', $departmentId)
                ->whereHas('participants', fn ($q) => $q->where('user_id', $userId));

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
                ->paginate(10)
                ->withQueryString();

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

            $distributionQuery = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->where('department_id', $departmentId)
                ->where('status', '!=', 'cancelled')
                ->whereHas('participants', fn ($q) => $q->where('user_id', $userId));

            $distributionQuery = $this->analyticsAggregator->applyPeriodFilter($distributionQuery, $distributionPeriod);

            $distributionTasks = $distributionQuery
                ->with(['activityType', 'subActivity'])
                ->get();

            $distribution = $this->reportAggregationService->buildDistribution($distributionTasks);
            $focusBreakdown = $this->reportAggregationService->buildFocusBreakdown($distributionTasks);

            return [
                'roadmap' => $roadmap,
                'upcoming' => $upcoming,
                'distribution' => $distribution,
                'focus_breakdown' => $focusBreakdown,
            ];
        } catch (\Exception $e) {
            return [
                'roadmap' => new LengthAwarePaginator([], 0, 6),
                'upcoming' => [],
                'distribution' => [],
                'focus_breakdown' => $this->reportAggregationService->buildFocusBreakdown(collect()),
            ];
        }
    }

    /**
     * Get visuals for department dashboard.
     */
    public function getDepartmentVisuals(int $departmentId, int $buId, string $distributionPeriod = 'all', ?int $memberUserId = null): array
    {
        try {
            $tab = request()->input('dept_tab', 'inprogress');
            $scopeIds = Department::scopeIdsForId($departmentId);

            $query = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->whereIn('department_id', $scopeIds);
            $query = $this->memberFocusService->applyMemberFocus($query, $memberUserId);

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

            $upcoming = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->whereIn('department_id', $scopeIds)
                ->whereIn('status', ['planned', 'in_progress'])
                ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->orderBy('due_date', 'asc')
                ->take(5)
                ->tap(fn ($query) => $this->memberFocusService->applyMemberFocus($query, $memberUserId))
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->task_title,
                    'due_date' => $t->due_date ? $t->due_date->format('Y-m-d') : null,
                    'is_critical' => $t->due_date ? $t->due_date->lt(now()->addDays(2)) : false,
                ])
                ->toArray();

            $distributionQuery = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->whereIn('department_id', $scopeIds);
            $distributionQuery = $this->memberFocusService->applyMemberFocus($distributionQuery, $memberUserId);
            $distributionQuery = $this->analyticsAggregator->applyPeriodFilter($distributionQuery, $distributionPeriod);

            $distributionTasks = $distributionQuery
                ->with(['activityType', 'subActivity'])
                ->get();

            $distribution = $this->reportAggregationService->buildDistribution($distributionTasks);
            $focusBreakdown = $this->reportAggregationService->buildFocusBreakdown($distributionTasks);

            $bottleneck = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->whereIn('department_id', $scopeIds)
                ->where('due_date', '<', now()->toDateString())
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->tap(fn ($query) => $this->memberFocusService->applyMemberFocus($query, $memberUserId))
                ->count();

            $topCategory = EmployeeTask::query()
                ->where('business_unit_id', $buId)
                ->whereIn('department_id', $scopeIds)
                ->tap(fn ($query) => $this->memberFocusService->applyMemberFocus($query, $memberUserId))
                ->join('employee_activity_types', 'employee_tasks.activity_type_id', '=', 'employee_activity_types.id')
                ->select('employee_activity_types.name', DB::raw('count(*) as count'))
                ->groupBy('employee_activity_types.id', 'employee_activity_types.name')
                ->orderByDesc('count')
                ->first();

            return [
                'roadmap' => $roadmap,
                'upcoming' => $upcoming,
                'distribution' => $distribution,
                'focus_breakdown' => $focusBreakdown,
                'bottleneck' => $bottleneck,
                'top_category' => $topCategory ? $topCategory->name : '-',
            ];
        } catch (\Exception $e) {
            return [
                'roadmap' => new LengthAwarePaginator([], 0, 10),
                'upcoming' => [],
                'distribution' => [],
                'focus_breakdown' => $this->reportAggregationService->buildFocusBreakdown(collect()),
                'bottleneck' => 0,
                'top_category' => '-',
            ];
        }
    }
}
