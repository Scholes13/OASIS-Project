<?php

namespace App\Services\Modules\Purchasing\Admin;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use Carbon\Carbon;

/**
 * Builds props for the PurchasingAdmin department + consolidated reports.
 *
 * Extracted from
 * {@see \App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::departmentReport()}
 * and {@see \App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::consolidatedReport()}.
 * Behavior preserved verbatim.
 */
class AdminTaskReportService
{
    private const TREND_COLORS = [
        ['border' => 'rgb(99, 102, 241)', 'bg' => 'rgba(99, 102, 241, 0.1)'],
        ['border' => 'rgb(16, 185, 129)', 'bg' => 'rgba(16, 185, 129, 0.1)'],
        ['border' => 'rgb(59, 130, 246)', 'bg' => 'rgba(59, 130, 246, 0.1)'],
        ['border' => 'rgb(245, 158, 11)', 'bg' => 'rgba(245, 158, 11, 0.1)'],
        ['border' => 'rgb(239, 68, 68)', 'bg' => 'rgba(239, 68, 68, 0.1)'],
    ];

    /**
     * @return array{
     *     department: Department|null,
     *     totalSavings: float,
     *     averageFollowupTime: float,
     *     averageCompletionTime: float,
     *     totalTasksCompleted: int,
     *     savingsBreakdown: array{purchase_request: float, stock_request: float},
     *     adminPerformance: array,
     *     departmentTrendData: array{labels: array, data: array},
     * }
     */
    public function buildDepartmentReportData(User $user, int $buId): array
    {
        $userBu = UserBusinessUnit::where('user_id', $user->id)
            ->where('business_unit_id', $buId)
            ->first();

        $departmentId = $userBu?->department_id;
        $department = $departmentId ? Department::find($departmentId) : null;

        if (! $departmentId) {
            return $this->emptyDepartmentReport();
        }

        $stats = AdminTask::where('business_unit_id', $buId)
            ->where('department_id', $departmentId)
            ->where('status', 'done')
            ->selectRaw('
                SUM(savings_amount) as total_savings,
                AVG(followup_time_minutes) as avg_followup_time,
                AVG(completion_time_minutes) as avg_completion_time,
                COUNT(*) as total_completed
            ')
            ->first();

        $prSavings = AdminTask::where('business_unit_id', $buId)
            ->where('department_id', $departmentId)
            ->where('status', 'done')
            ->where('taskable_type', 'like', '%PurchaseRequest%')
            ->sum('savings_amount') ?? 0;

        $stSavings = AdminTask::where('business_unit_id', $buId)
            ->where('department_id', $departmentId)
            ->where('status', 'done')
            ->where('taskable_type', 'like', '%StockRequest%')
            ->sum('savings_amount') ?? 0;

        $adminPerformance = $this->buildAdminPerformance($buId, $departmentId);
        $trendData = $this->buildDepartmentTrend($buId, $departmentId);

        return [
            'department' => $department,
            'totalSavings' => (float) ($stats->total_savings ?? 0),
            'averageFollowupTime' => (float) ($stats->avg_followup_time ?? 0),
            'averageCompletionTime' => (float) ($stats->avg_completion_time ?? 0),
            'totalTasksCompleted' => (int) ($stats->total_completed ?? 0),
            'savingsBreakdown' => [
                'purchase_request' => (float) $prSavings,
                'stock_request' => (float) $stSavings,
            ],
            'adminPerformance' => $adminPerformance,
            'departmentTrendData' => $trendData,
        ];
    }

    private function emptyDepartmentReport(): array
    {
        return [
            'department' => null,
            'totalSavings' => 0,
            'averageFollowupTime' => 0,
            'averageCompletionTime' => 0,
            'totalTasksCompleted' => 0,
            'savingsBreakdown' => ['purchase_request' => 0, 'stock_request' => 0],
            'adminPerformance' => [],
            'departmentTrendData' => ['labels' => [], 'data' => []],
        ];
    }

    private function buildAdminPerformance(int $buId, int $departmentId): array
    {
        $admins = UserBusinessUnit::with('user')
            ->where('business_unit_id', $buId)
            ->where('department_id', $departmentId)
            ->where('is_purchasing_admin', true)
            ->get();

        return $admins->map(function ($userBu) use ($buId) {
            $userId = $userBu->user_id;
            $adminStats = AdminTask::where('business_unit_id', $buId)
                ->where('assigned_admin_id', $userId)
                ->where('status', 'done')
                ->selectRaw('
                    COUNT(*) as tasks_completed,
                    SUM(savings_amount) as total_savings,
                    AVG(savings_percentage) as avg_savings_percentage,
                    AVG(followup_time_minutes) as avg_followup_time,
                    AVG(completion_time_minutes) as avg_completion_time
                ')
                ->first();

            return [
                'name' => $userBu->user->name,
                'tasks_completed' => (int) ($adminStats->tasks_completed ?? 0),
                'total_savings' => (float) ($adminStats->total_savings ?? 0),
                'avg_savings_percentage' => (float) ($adminStats->avg_savings_percentage ?? 0),
                'avg_followup_time' => (float) ($adminStats->avg_followup_time ?? 0),
                'avg_completion_time' => (float) ($adminStats->avg_completion_time ?? 0),
            ];
        })->sortByDesc('total_savings')->values()->toArray();
    }

    /**
     * @return array{labels: array, data: array}
     */
    private function buildDepartmentTrend(int $buId, int $departmentId): array
    {
        $trendData = AdminTask::where('business_unit_id', $buId)
            ->where('department_id', $departmentId)
            ->where('status', 'done')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subMonths(12))
            ->selectRaw('DATE_FORMAT(completed_at, "%Y-%m") as month, SUM(savings_amount) as total_savings')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $labels = [];
        $data = [];
        foreach ($trendData as $item) {
            $date = Carbon::createFromFormat('Y-m', $item->month);
            $labels[] = $date->format('M Y');
            $data[] = round($item->total_savings, 2);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * @return array{
     *     childBusinessUnits: array,
     *     businessUnitMetrics: array,
     *     overallMetrics: array,
     *     comparativeTrendData: array{labels: array, datasets: array},
     * }
     */
    public function buildConsolidatedReportData(int $buId): array
    {
        $currentBu = BusinessUnit::find($buId);

        $childBUs = collect();
        if ($currentBu && $currentBu->parent_id === null) {
            $childBUs = BusinessUnit::where('parent_id', $currentBu->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        if ($childBUs->isEmpty()) {
            return $this->emptyConsolidatedReport();
        }

        $buIds = $childBUs->pluck('id')->toArray();

        $businessUnitMetrics = $childBUs->map(function ($bu) {
            $stats = AdminTask::where('business_unit_id', $bu->id)
                ->where('status', 'done')
                ->selectRaw('
                    COUNT(*) as total_tasks,
                    SUM(savings_amount) as total_savings,
                    AVG(savings_percentage) as avg_savings_percentage,
                    AVG(followup_time_minutes) as avg_followup_time,
                    AVG(completion_time_minutes) as avg_completion_time
                ')
                ->first();

            return [
                'id' => $bu->id,
                'code' => $bu->code,
                'name' => $bu->name,
                'total_tasks' => (int) ($stats->total_tasks ?? 0),
                'total_savings' => (float) ($stats->total_savings ?? 0),
                'avg_savings_percentage' => (float) ($stats->avg_savings_percentage ?? 0),
                'avg_followup_time' => (float) ($stats->avg_followup_time ?? 0),
                'avg_completion_time' => (float) ($stats->avg_completion_time ?? 0),
            ];
        })->sortByDesc('total_savings')->values()->toArray();

        $overallStats = AdminTask::whereIn('business_unit_id', $buIds)
            ->where('status', 'done')
            ->selectRaw('
                COUNT(*) as total_tasks,
                SUM(savings_amount) as total_savings,
                AVG(savings_percentage) as avg_savings_percentage,
                AVG(followup_time_minutes) as avg_followup_time,
                AVG(completion_time_minutes) as avg_completion_time
            ')
            ->first();

        $comparativeTrendData = $this->buildComparativeTrend($childBUs, $buIds);

        return [
            'childBusinessUnits' => $childBUs->map(fn ($bu) => ['id' => $bu->id, 'code' => $bu->code, 'name' => $bu->name])->toArray(),
            'businessUnitMetrics' => $businessUnitMetrics,
            'overallMetrics' => [
                'total_tasks' => (int) ($overallStats->total_tasks ?? 0),
                'total_savings' => (float) ($overallStats->total_savings ?? 0),
                'avg_savings_percentage' => (float) ($overallStats->avg_savings_percentage ?? 0),
                'avg_followup_time' => (float) ($overallStats->avg_followup_time ?? 0),
                'avg_completion_time' => (float) ($overallStats->avg_completion_time ?? 0),
            ],
            'comparativeTrendData' => $comparativeTrendData,
        ];
    }

    private function emptyConsolidatedReport(): array
    {
        return [
            'childBusinessUnits' => [],
            'businessUnitMetrics' => [],
            'overallMetrics' => [
                'total_tasks' => 0,
                'total_savings' => 0,
                'avg_savings_percentage' => 0,
                'avg_followup_time' => 0,
                'avg_completion_time' => 0,
            ],
            'comparativeTrendData' => ['labels' => [], 'datasets' => []],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, BusinessUnit>  $childBUs
     * @param  int[]  $buIds
     * @return array{labels: array, datasets: array}
     */
    private function buildComparativeTrend($childBUs, array $buIds): array
    {
        $trendData = AdminTask::whereIn('business_unit_id', $buIds)
            ->where('status', 'done')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subMonths(12))
            ->selectRaw('business_unit_id, DATE_FORMAT(completed_at, "%Y-%m") as month, SUM(savings_amount) as total_savings')
            ->groupBy('business_unit_id', 'month')
            ->orderBy('month')
            ->get();

        $months = $trendData->pluck('month')->unique()->sort()->values();
        $labels = $months->map(fn ($m) => Carbon::createFromFormat('Y-m', $m)->format('M Y'))->toArray();

        $datasets = $childBUs->values()->map(function ($bu, $index) use ($trendData, $months) {
            $data = $months->map(function ($month) use ($bu, $trendData) {
                $record = $trendData->where('business_unit_id', $bu->id)->where('month', $month)->first();

                return $record ? round($record->total_savings, 2) : 0;
            })->toArray();

            $color = self::TREND_COLORS[$index % count(self::TREND_COLORS)];

            return [
                'label' => $bu->name,
                'data' => $data,
                'borderColor' => $color['border'],
                'backgroundColor' => $color['bg'],
            ];
        })->toArray();

        return ['labels' => $labels, 'datasets' => $datasets];
    }
}
