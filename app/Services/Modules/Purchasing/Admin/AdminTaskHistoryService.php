<?php

namespace App\Services\Modules\Purchasing\Admin;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Builds props for the PurchasingAdmin history pages
 * (personal, audit, department audit, management).
 *
 * Extracted from
 * {@see \App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController}.
 * Behavior preserved verbatim.
 */
class AdminTaskHistoryService
{
    /**
     * Personal task history (the user's own assigned tasks).
     *
     * @param  array<string, string|null>  $filters
     * @return array{
     *     tasks: LengthAwarePaginator,
     *     statistics: array,
     *     filters: array,
     * }
     */
    public function buildTaskHistoryData(User $user, array $filters): array
    {
        $filters = [
            'date_from' => $filters['date_from'] ?? now()->startOfMonth()->format('Y-m-d'),
            'date_to' => $filters['date_to'] ?? '',
            'status' => $filters['status'] ?? 'all',
            'type' => $filters['type'] ?? 'all',
        ];

        $query = AdminTask::with(['taskable', 'businessUnit', 'department'])
            ->where('assigned_admin_id', $user->id)
            ->orderBy('entered_at', 'desc');

        $this->applyDateRange($query, $filters['date_from'], $filters['date_to']);
        $this->applyStatusFilter($query, $filters['status']);
        $this->applyTypeFilter($query, $filters['type']);

        $tasks = $query->paginate(10)->withQueryString();

        $statsQuery = AdminTask::where('assigned_admin_id', $user->id)
            ->where('status', 'done');
        $this->applyDateRange($statsQuery, $filters['date_from'], $filters['date_to']);

        $statistics = $statsQuery->selectRaw('
            COUNT(*) as total_completed,
            AVG(followup_time_minutes) as avg_followup_time,
            AVG(completion_time_minutes) as avg_completion_time,
            SUM(savings_amount) as total_savings,
            AVG(savings_percentage) as avg_savings_percentage
        ')->first()->toArray();

        return [
            'tasks' => $tasks,
            'statistics' => $statistics,
            'filters' => $filters,
        ];
    }

    /**
     * Personal task history v2 (Purchasing Admin page).
     *
     * @param  array<string, string|null>  $filters
     * @return array{
     *     tasks: LengthAwarePaginator,
     *     statistics: array,
     *     filters: array,
     * }
     */
    public function buildPersonalTaskHistoryData(User $user, array $filters): array
    {
        $filters = [
            'date_from' => $filters['date_from'] ?? '',
            'date_to' => $filters['date_to'] ?? '',
            'status' => $filters['status'] ?? 'all',
            'type' => $filters['type'] ?? 'all',
        ];

        $query = AdminTask::with(['taskable', 'businessUnit', 'department'])
            ->where('assigned_admin_id', $user->id)
            ->orderBy('entered_at', 'desc');

        $this->applyDateRange($query, $filters['date_from'], $filters['date_to']);
        $this->applyStatusFilter($query, $filters['status']);
        $this->applyTypeFilter($query, $filters['type']);

        $tasks = $query->paginate(10)->withQueryString();

        $statsQuery = AdminTask::where('assigned_admin_id', $user->id)
            ->where('status', 'done');
        $this->applyDateRange($statsQuery, $filters['date_from'], $filters['date_to']);

        $stats = $statsQuery->selectRaw('
            COUNT(*) as total_completed,
            AVG(followup_time_minutes) as avg_followup_time,
            AVG(completion_time_minutes) as avg_completion_time,
            SUM(savings_amount) as total_savings,
            AVG(savings_percentage) as avg_savings_percentage
        ')->first();

        return [
            'tasks' => $tasks,
            'statistics' => [
                'total_completed' => (int) ($stats->total_completed ?? 0),
                'avg_followup_time' => (float) ($stats->avg_followup_time ?? 0),
                'avg_completion_time' => (float) ($stats->avg_completion_time ?? 0),
                'total_savings' => (float) ($stats->total_savings ?? 0),
                'avg_savings_percentage' => (float) ($stats->avg_savings_percentage ?? 0),
            ],
            'filters' => $filters,
        ];
    }

    /**
     * Super-admin audit history (all tasks, all BUs).
     *
     * @param  array<string, string|null>  $filters
     * @return array{
     *     tasks: LengthAwarePaginator,
     *     admins: \Illuminate\Support\Collection,
     *     departments: \Illuminate\Support\Collection,
     *     filters: array,
     * }
     */
    public function buildAuditHistoryData(array $filters): array
    {
        $filters = [
            'date_from' => $filters['date_from'] ?? now()->subDays(30)->format('Y-m-d'),
            'date_to' => $filters['date_to'] ?? now()->format('Y-m-d'),
            'status' => $filters['status'] ?? 'all',
            'type' => $filters['type'] ?? 'all',
            'admin' => $filters['admin'] ?? 'all',
            'department' => $filters['department'] ?? 'all',
        ];

        $query = AdminTask::with(['taskable', 'businessUnit', 'department', 'assignedAdmin'])
            ->orderBy('entered_at', 'desc');

        $this->applyDateRange($query, $filters['date_from'], $filters['date_to']);
        $this->applyStatusFilter($query, $filters['status']);
        $this->applyTypeFilter($query, $filters['type']);

        if ($filters['admin'] !== 'all') {
            $query->where('assigned_admin_id', $filters['admin']);
        }
        if ($filters['department'] !== 'all') {
            $query->where('department_id', $filters['department']);
        }

        $tasks = $query->paginate(20)->withQueryString();

        $admins = User::whereHas('businessUnits', function ($q) {
            $q->where('is_purchasing_admin', true);
        })->orderBy('name')->get(['id', 'name']);

        $departments = Department::where('is_purchasing_department', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'tasks' => $tasks,
            'admins' => $admins,
            'departments' => $departments,
            'filters' => $filters,
        ];
    }

    /**
     * Department-manager audit history (tasks for the manager's department).
     *
     * @param  array<string, string|null>  $filters
     * @return array{
     *     tasks: LengthAwarePaginator|array,
     *     admins: \Illuminate\Support\Collection|array,
     *     filters: array,
     * }
     */
    public function buildDepartmentAuditHistoryData(User $user, int $buId, array $filters): array
    {
        $userBu = UserBusinessUnit::where('user_id', $user->id)
            ->where('business_unit_id', $buId)
            ->first();

        $departmentId = $userBu?->department_id;

        $filters = [
            'date_from' => $filters['date_from'] ?? now()->subDays(30)->format('Y-m-d'),
            'date_to' => $filters['date_to'] ?? now()->format('Y-m-d'),
            'status' => $filters['status'] ?? 'all',
            'type' => $filters['type'] ?? 'all',
            'admin' => $filters['admin'] ?? 'all',
        ];

        if (! $departmentId) {
            return [
                'tasks' => ['data' => [], 'links' => [], 'current_page' => 1, 'last_page' => 1, 'total' => 0, 'from' => 0, 'to' => 0],
                'admins' => [],
                'filters' => $filters,
            ];
        }

        $query = AdminTask::with(['taskable', 'businessUnit', 'department', 'assignedAdmin'])
            ->where('department_id', $departmentId)
            ->orderBy('entered_at', 'desc');

        $this->applyDateRange($query, $filters['date_from'], $filters['date_to']);
        $this->applyStatusFilter($query, $filters['status']);
        $this->applyTypeFilter($query, $filters['type']);

        if ($filters['admin'] !== 'all') {
            $query->where('assigned_admin_id', $filters['admin']);
        }

        $tasks = $query->paginate(20)->withQueryString();

        $admins = User::whereHas('businessUnits', function ($q) use ($departmentId) {
            $q->where('is_purchasing_admin', true)
                ->where('department_id', $departmentId);
        })->orderBy('name')->get(['id', 'name']);

        return [
            'tasks' => $tasks,
            'admins' => $admins,
            'filters' => $filters,
        ];
    }

    /**
     * Management history (tasks across BU + child BUs, assigned to any admin).
     *
     * @param  array<string, string|null>  $filters
     * @return array{
     *     tasks: LengthAwarePaginator,
     *     statistics: array,
     *     adminList: \Illuminate\Support\Collection,
     *     filters: array,
     * }
     */
    public function buildManagementHistoryData(int $buId, array $filters): array
    {
        $businessUnit = BusinessUnit::with('children')->find($buId);

        $buIds = [$buId];
        if ($businessUnit && $businessUnit->children->isNotEmpty()) {
            $buIds = array_merge($buIds, $businessUnit->children->pluck('id')->toArray());
        }

        $filters = [
            'date_from' => $filters['date_from'] ?? '',
            'date_to' => $filters['date_to'] ?? '',
            'status' => $filters['status'] ?? 'all',
            'type' => $filters['type'] ?? 'all',
            'admin' => $filters['admin'] ?? 'all',
        ];

        $query = AdminTask::with(['taskable', 'businessUnit', 'department', 'assignedAdmin'])
            ->whereIn('business_unit_id', $buIds)
            ->whereNotNull('assigned_admin_id')
            ->orderBy('entered_at', 'desc');

        $this->applyDateRange($query, $filters['date_from'], $filters['date_to']);
        $this->applyStatusFilter($query, $filters['status']);
        $this->applyTypeFilter($query, $filters['type']);

        if ($filters['admin'] !== 'all') {
            $query->where('assigned_admin_id', $filters['admin']);
        }

        $tasks = $query->paginate(10)->withQueryString();

        $statsQuery = AdminTask::whereIn('business_unit_id', $buIds)
            ->where('status', 'done');
        $this->applyDateRange($statsQuery, $filters['date_from'], $filters['date_to']);
        if ($filters['admin'] !== 'all') {
            $statsQuery->where('assigned_admin_id', $filters['admin']);
        }

        $stats = $statsQuery->selectRaw('
            COUNT(*) as total_completed,
            AVG(followup_time_minutes) as avg_followup_time,
            AVG(completion_time_minutes) as avg_completion_time,
            SUM(savings_amount) as total_savings,
            AVG(savings_percentage) as avg_savings_percentage
        ')->first();

        $adminIds = AdminTask::whereIn('business_unit_id', $buIds)
            ->whereNotNull('assigned_admin_id')
            ->distinct()
            ->pluck('assigned_admin_id');

        $adminList = User::whereIn('id', $adminIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'tasks' => $tasks,
            'statistics' => [
                'total_completed' => (int) ($stats->total_completed ?? 0),
                'avg_followup_time' => (float) ($stats->avg_followup_time ?? 0),
                'avg_completion_time' => (float) ($stats->avg_completion_time ?? 0),
                'total_savings' => (float) ($stats->total_savings ?? 0),
                'avg_savings_percentage' => (float) ($stats->avg_savings_percentage ?? 0),
            ],
            'adminList' => $adminList,
            'filters' => $filters,
        ];
    }

    private function applyDateRange(Builder $query, ?string $dateFrom, ?string $dateTo): void
    {
        if (! empty($dateFrom)) {
            $query->whereDate('entered_at', '>=', $dateFrom);
        }
        if (! empty($dateTo)) {
            $query->whereDate('entered_at', '<=', $dateTo);
        }
    }

    private function applyStatusFilter(Builder $query, string $status): void
    {
        if ($status !== 'all') {
            $query->where('status', $status);
        }
    }

    private function applyTypeFilter(Builder $query, string $type): void
    {
        if ($type === 'all') {
            return;
        }

        if ($type === 'purchase_request') {
            $query->where('taskable_type', 'like', '%PurchaseRequest%');
        } elseif ($type === 'stock_request') {
            $query->where('taskable_type', 'like', '%StockRequest%');
        }
    }
}
