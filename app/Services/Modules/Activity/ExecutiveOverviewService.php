<?php

namespace App\Services\Modules\Activity;

use App\Models\Core\BusinessUnit;
use App\Models\Core\User;
use App\Models\Modules\Activity\EmployeeTask;

/**
 * Executive overview helpers for top-management Activity dashboard.
 *
 * Lifted verbatim from ActivityInertiaController:
 *  - canViewExecutiveDashboard
 *  - getExecutiveOverview
 *  - emptyStats
 */
class ExecutiveOverviewService
{
    /**
     * Check if user can view executive dashboard in the current BU context.
     *
     * Returns true if the user has executive/c_level access in the current BU,
     * or is executive in an ancestor BU.
     */
    public function canViewExecutiveDashboard(User $user, ?int $businessUnitId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $businessUnitId) {
            return false;
        }

        $accessLevel = $user->getAccessLevel($businessUnitId);
        if ($accessLevel === 'executive') {
            return true;
        }

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
     * @return array{aggregate: array, businessUnits: array, topOverdueDepartments: array}
     */
    public function getExecutiveOverview(User $user): array
    {
        try {
            $accessibleBuIds = $user->getAccessibleBusinessUnitIds();

            if (empty($accessibleBuIds)) {
                return ['aggregate' => $this->emptyStats(), 'businessUnits' => [], 'topOverdueDepartments' => []];
            }

            $today = now()->toDateString();
            $thisMonth = now()->startOfMonth();

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
}
