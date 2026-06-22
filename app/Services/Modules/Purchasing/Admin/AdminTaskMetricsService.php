<?php

namespace App\Services\Modules\Purchasing\Admin;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use Illuminate\Database\Eloquent\Builder;

/**
 * Builds props for the PurchasingAdmin dashboard.
 *
 * Extracted from
 * {@see \App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::dashboard()}.
 * Behavior preserved verbatim.
 */
class AdminTaskMetricsService
{
    /**
     * @param  array{date_preset?: string|null, date_from?: string|null, date_to?: string|null}  $filters
     * @return array{
     *     stats: array{pending: int, in_progress: int, done: int},
     *     recentTasks: \Illuminate\Database\Eloquent\Collection,
     *     metrics: array,
     *     savingsTrend: array{labels: array, data: array},
     *     departmentBreakdown: \Illuminate\Support\Collection,
     *     datePreset: string,
     * }
     */
    public function buildDashboardData(User $user, int $buId, array $filters): array
    {
        $datePreset = $filters['date_preset'] ?? 'this_month';
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $baseQuery = AdminTask::where('admin_tasks.business_unit_id', $buId);
        $periodQuery = $this->applyPeriodFilter(clone $baseQuery, $datePreset, $dateFrom, $dateTo);

        return [
            'stats' => $this->buildStats($baseQuery, $user->id),
            'metrics' => $this->buildPerformanceMetrics($periodQuery, $user->id),
            'savingsTrend' => $this->buildSavingsTrend($buId, $user->id),
            'departmentBreakdown' => $this->buildDepartmentBreakdown($periodQuery),
            'recentTasks' => $this->buildRecentTasks($buId, $user->id),
            'datePreset' => $datePreset,
        ];
    }

    private function applyPeriodFilter(Builder $query, string $datePreset, ?string $dateFrom, ?string $dateTo): Builder
    {
        if ($dateFrom && $dateTo) {
            $query->whereBetween('entered_at', [$dateFrom, $dateTo]);
        } elseif ($datePreset === 'this_month') {
            $query->whereMonth('entered_at', now()->month)
                ->whereYear('entered_at', now()->year);
        } elseif ($datePreset === 'last_month') {
            $query->whereMonth('entered_at', now()->subMonth()->month)
                ->whereYear('entered_at', now()->subMonth()->year);
        } elseif ($datePreset === 'this_year') {
            $query->whereYear('entered_at', now()->year);
        }

        return $query;
    }

    /**
     * @return array{pending: int, in_progress: int, done: int}
     */
    private function buildStats(Builder $baseQuery, int $userId): array
    {
        return [
            'pending' => (clone $baseQuery)->where('status', 'pending_followup')->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'in_progress')
                ->where('assigned_admin_id', $userId)->count(),
            'done' => (clone $baseQuery)->where('status', 'done')
                ->where('assigned_admin_id', $userId)->count(),
        ];
    }

    private function buildPerformanceMetrics(Builder $periodQuery, int $userId): array
    {
        $performanceQuery = (clone $periodQuery)
            ->where('assigned_admin_id', $userId)
            ->where('status', 'done');

        $metrics = $performanceQuery->selectRaw('
            COUNT(*) as total_tasks_completed,
            AVG(followup_time_minutes) as avg_followup_time,
            AVG(completion_time_minutes) as avg_completion_time,
            SUM(savings_amount) as total_savings,
            AVG(savings_percentage) as avg_savings_percentage
        ')->first();

        return [
            'total_tasks_completed' => (int) ($metrics->total_tasks_completed ?? 0),
            'avg_followup_time' => (float) ($metrics->avg_followup_time ?? 0),
            'avg_completion_time' => (float) ($metrics->avg_completion_time ?? 0),
            'total_savings' => (float) ($metrics->total_savings ?? 0),
            'avg_savings_percentage' => (float) ($metrics->avg_savings_percentage ?? 0),
        ];
    }

    /**
     * @return array{labels: array, data: array}
     */
    private function buildSavingsTrend(int $buId, int $userId): array
    {
        $trendStart = now()->subMonths(5)->startOfMonth();

        $trendData = AdminTask::where('business_unit_id', $buId)
            ->where('assigned_admin_id', $userId)
            ->where('status', 'done')
            ->where('completed_at', '>=', $trendStart)
            ->selectRaw('DATE_FORMAT(completed_at, "%Y-%m") as month, SUM(savings_amount) as total_savings')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $labels = [];
        $data = [];
        $current = $trendStart->copy();

        while ($current <= now()->endOfMonth()) {
            $monthKey = $current->format('Y-m');
            $labels[] = $current->format('M Y');
            $record = $trendData->firstWhere('month', $monthKey);
            $data[] = $record ? (float) $record->total_savings : 0;
            $current->addMonth();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function buildDepartmentBreakdown(Builder $periodQuery): \Illuminate\Support\Collection
    {
        $deptBreakdownRaw = (clone $periodQuery)
            ->join('departments', 'admin_tasks.department_id', '=', 'departments.id')
            ->selectRaw('departments.name as department_name, COUNT(*) as task_count')
            ->groupBy('departments.name')
            ->orderByDesc('task_count')
            ->limit(5)
            ->get();

        $totalDeptTasks = $deptBreakdownRaw->sum('task_count');

        return $deptBreakdownRaw->map(function ($item) use ($totalDeptTasks) {
            return [
                'department' => $item->department_name,
                'count' => $item->task_count,
                'percentage' => $totalDeptTasks > 0 ? round(($item->task_count / $totalDeptTasks) * 100, 1) : 0,
            ];
        });
    }

    private function buildRecentTasks(int $buId, int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return AdminTask::with(['taskable', 'department'])
            ->where('business_unit_id', $buId)
            ->where('assigned_admin_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();
    }
}
