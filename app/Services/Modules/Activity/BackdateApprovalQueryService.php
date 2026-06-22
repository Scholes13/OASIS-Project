<?php

namespace App\Services\Modules\Activity;

use App\Models\Core\User;
use App\Models\Modules\Activity\BackdatePermission;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Listing/query helpers for backdate permission requests + approvals.
 *
 * Lifted verbatim from ActivityInertiaController:
 *  - backdateRequests query pipeline
 *  - backdateApprovals query pipeline (status filter, dept guard, paginated)
 */
class BackdateApprovalQueryService
{
    /**
     * Paginate the requesting user's own backdate permission requests.
     */
    public function paginateUserRequests(User $user): LengthAwarePaginator
    {
        return BackdatePermission::forUser($user->id)
            ->with(['approver', 'rejector', 'department'])
            ->latest()
            ->paginate(10);
    }

    public function userHasPendingRequest(User $user): bool
    {
        return BackdatePermission::forUser($user->id)
            ->pending()
            ->exists();
    }

    /**
     * Paginate department-head facing approvals queue with optional status filter.
     */
    public function paginateApprovals(Request $request, User $user, ?int $buId, ?int $departmentId): LengthAwarePaginator
    {
        $statusFilter = $request->get('status', 'pending');

        $query = BackdatePermission::query()
            ->with(['user', 'approver', 'rejector', 'department'])
            ->where('business_unit_id', $buId);

        if (! $user->isSuperAdmin()) {
            $query->where('department_id', $departmentId);
        }

        if ($statusFilter !== 'all') {
            if ($statusFilter === 'pending') {
                $query->pending();
            } else {
                $query->where('status', $statusFilter);
            }
        }

        return $query->latest()->paginate(15);
    }

    public function pendingCount(User $user, ?int $buId, ?int $departmentId): int
    {
        $pendingQuery = BackdatePermission::pending()
            ->where('business_unit_id', $buId);

        if (! $user->isSuperAdmin()) {
            $pendingQuery->where('department_id', $departmentId);
        }

        return $pendingQuery->count();
    }
}
