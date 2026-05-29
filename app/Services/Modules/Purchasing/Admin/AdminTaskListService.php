<?php

namespace App\Services\Modules\Purchasing\Admin;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Builds props for the PurchasingAdmin tasks listing page.
 *
 * Extracted from
 * {@see \App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::tasks()}.
 * Behavior preserved verbatim.
 */
class AdminTaskListService
{
    private const TYPE_MAP = [
        'purchase_request' => PurchaseRequest::class,
        'stock_request' => StockRequest::class,
    ];

    /**
     * @param  array{status?: string, type?: string, date?: string, search?: string}  $filters
     * @return array{
     *     tasks: LengthAwarePaginator,
     *     allTasks: Collection,
     *     filters: array,
     *     counts: array{pending: int, in_progress: int, completed: int},
     * }
     */
    public function buildTasksPageData(User $user, int $buId, array $filters): array
    {
        $filters = [
            'status' => $filters['status'] ?? 'pending',
            'type' => $filters['type'] ?? '',
            'date' => $filters['date'] ?? 'all',
            'search' => $filters['search'] ?? '',
        ];

        $tasks = $this->buildPaginatedTasks($user, $buId, $filters);
        $allTasks = $this->buildAllTasksForBoard($buId, $filters);
        $counts = $this->buildTabCounts($user, $buId);

        return [
            'tasks' => $tasks,
            'allTasks' => $allTasks,
            'filters' => $filters,
            'counts' => $counts,
        ];
    }

    /**
     * @param  array{status: string, type: string, date: string, search: string}  $filters
     */
    private function buildPaginatedTasks(User $user, int $buId, array $filters): LengthAwarePaginator
    {
        $query = AdminTask::with(['taskable', 'assignedAdmin:id,name', 'department:id,name'])
            ->where('business_unit_id', $buId);

        $this->applyStatusFilter($query, $filters['status'], $user->id);
        $this->applyDateFilter($query, $filters['date']);
        $this->applyTypeFilter($query, $filters['type']);
        $this->applySearchFilter($query, $filters['search']);

        return $query->orderBy('entered_at', 'desc')->paginate(10)->withQueryString();
    }

    /**
     * @param  array{status: string, type: string, date: string, search: string}  $filters
     */
    private function buildAllTasksForBoard(int $buId, array $filters): Collection
    {
        $query = AdminTask::with(['taskable', 'department', 'assignedAdmin'])
            ->where('business_unit_id', $buId);

        if (! empty($filters['type'])) {
            $query->where('taskable_type', 'like', '%'.ucfirst(str_replace('_', '', $filters['type'])).'%');
        }

        if (! empty($filters['date']) && $filters['date'] !== 'all') {
            if ($filters['date'] === 'today') {
                $query->whereDate('entered_at', now()->toDateString());
            } elseif ($filters['date'] === 'last_30_days') {
                $query->where('entered_at', '>=', now()->subDays(30));
            }
        }

        if (! empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->whereHas('taskable', function ($sub) use ($searchTerm) {
                $sub->where(function ($q) use ($searchTerm) {
                    $q->where('pr_number', 'like', "%{$searchTerm}%")
                        ->orWhere('st_number', 'like', "%{$searchTerm}%");
                });
            });
        }

        return $query->orderBy('entered_at', 'desc')->get();
    }

    /**
     * @return array{pending: int, in_progress: int, completed: int}
     */
    private function buildTabCounts(User $user, int $buId): array
    {
        $statsQuery = AdminTask::where('business_unit_id', $buId);

        return [
            'pending' => (clone $statsQuery)->where('status', 'pending_followup')
                ->where(function ($q) use ($user) {
                    $q->whereNull('assigned_admin_id')->orWhere('assigned_admin_id', $user->id);
                })->count(),
            'in_progress' => (clone $statsQuery)->where('status', 'in_progress')
                ->where('assigned_admin_id', $user->id)->count(),
            'completed' => (clone $statsQuery)->where('status', 'done')
                ->where('assigned_admin_id', $user->id)->count(),
        ];
    }

    private function applyStatusFilter(Builder $query, string $status, int $userId): void
    {
        switch ($status) {
            case 'pending':
                $query->where('status', 'pending_followup')
                    ->where(function ($q) use ($userId) {
                        $q->whereNull('assigned_admin_id')
                            ->orWhere('assigned_admin_id', $userId);
                    });
                break;
            case 'in_progress':
                $query->where('status', 'in_progress')
                    ->where('assigned_admin_id', $userId);
                break;
            case 'completed':
                $query->where('status', 'done')
                    ->where('assigned_admin_id', $userId);
                break;
        }
    }

    private function applyDateFilter(Builder $query, string $date): void
    {
        if ($date === 'all') {
            return;
        }

        $dateQuery = match ($date) {
            'today' => now()->startOfDay(),
            'last_30_days' => now()->subDays(30)->startOfDay(),
            default => null,
        };

        if ($dateQuery) {
            $query->where('entered_at', '>=', $dateQuery);
        }
    }

    private function applyTypeFilter(Builder $query, string $type): void
    {
        if (empty($type) || ! isset(self::TYPE_MAP[$type])) {
            return;
        }

        $query->where('taskable_type', self::TYPE_MAP[$type]);
    }

    private function applySearchFilter(Builder $query, string $search): void
    {
        if (empty($search)) {
            return;
        }

        $query->where(function ($q) use ($search) {
            $q->whereHasMorph('taskable', [PurchaseRequest::class], function ($sub) use ($search) {
                $sub->where('pr_number', 'like', "%{$search}%");
            })->orWhereHasMorph('taskable', [StockRequest::class], function ($sub) use ($search) {
                $sub->where('st_number', 'like', "%{$search}%");
            });
        });
    }
}
