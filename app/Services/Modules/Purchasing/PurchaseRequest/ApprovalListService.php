<?php

namespace App\Services\Modules\Purchasing\PurchaseRequest;

use App\Models\Core\BusinessUnit;
use App\Models\Modules\Purchasing\PurchaseRequest\PrApproval;
use App\Models\Modules\Purchasing\StockRequest\StockApproval;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the props for the combined PR + ST pending approvals listing
 * (the "Approvals" Inertia page).
 *
 * Owns the listing logic, business-unit hierarchy expansion, and PR/ST
 * payload transforms previously inlined in
 * {@see \App\Http\Controllers\Modules\Purchasing\PurchaseRequest\ApprovalController::index()}.
 * Behavior preserved verbatim.
 */
class ApprovalListService
{
    /**
     * Build the full Inertia props payload for the approvals listing page.
     *
     * @return array{
     *     pendingApprovals: array,
     *     recentApprovals: array,
     *     stats: array,
     * }
     */
    public function buildIndexData(int $userId, ?int $businessUnitId): array
    {
        $filterBusinessUnitIds = $this->getFilterBusinessUnitIds($businessUnitId, $userId);

        $prPendingCount = $this->countPrPending($userId, $filterBusinessUnitIds);
        $prHistoryStats = $this->prHistoryStats($userId, $filterBusinessUnitIds);
        $stPendingCount = $this->countStPending($userId, $filterBusinessUnitIds);
        $stHistoryStats = $this->stHistoryStats($userId, $filterBusinessUnitIds);

        $pendingApprovals = $this->buildPendingApprovals($userId, $filterBusinessUnitIds);
        $recentApprovals = $this->buildRecentApprovals($userId, $filterBusinessUnitIds);

        $approvedCount = (int) (($prHistoryStats->approved_count ?? 0) + ($stHistoryStats->approved_count ?? 0));
        $rejectedCount = (int) (($prHistoryStats->rejected_count ?? 0) + ($stHistoryStats->rejected_count ?? 0));
        $pendingCount = $prPendingCount + $stPendingCount;

        return [
            'pendingApprovals' => $pendingApprovals->all(),
            'recentApprovals' => $recentApprovals->all(),
            'stats' => [
                'pending' => $pendingCount,
                'approved' => $approvedCount,
                'rejected' => $rejectedCount,
                'total' => $pendingCount + $approvedCount + $rejectedCount,
                'pr_pending' => $prPendingCount,
                'st_pending' => $stPendingCount,
            ],
        ];
    }

    /**
     * Get business unit IDs to filter approvals by.
     *
     * For c_level/executive users viewing from a parent BU, this returns
     * the parent BU + all descendant BU IDs so they can see approvals
     * from all child business units in one place.
     *
     * @return int[]
     */
    public function getFilterBusinessUnitIds(?int $businessUnitId, int $userId): array
    {
        if (! $businessUnitId) {
            return [];
        }

        $user = \App\Models\Core\User::find($userId);

        if ($user && $user->hasTopManagementAccess()) {
            $bu = BusinessUnit::find($businessUnitId);
            if ($bu) {
                return $bu->getAccessibleBusinessUnits();
            }
        }

        return [$businessUnitId];
    }

    /**
     * Check if the given user can act on this PR approval.
     */
    public function canProcessApproval(PrApproval $approval, int $userId): bool
    {
        if ($approval->approver_id !== $userId) {
            return false;
        }
        if ($approval->status !== 'pending') {
            return false;
        }
        if ($approval->purchaseRequest->status !== 'in_approval') {
            return false;
        }

        $currentApproval = $approval->purchaseRequest->currentApproval();
        if (! $currentApproval || $currentApproval->id !== $approval->id) {
            return false;
        }

        return true;
    }

    /**
     * Check if the given user can act on this stock approval.
     */
    public function canProcessStockApproval(StockApproval $approval, int $userId): bool
    {
        if ($approval->approver_id !== $userId) {
            return false;
        }
        if ($approval->status !== 'pending') {
            return false;
        }
        if ($approval->stockRequest->status !== 'in_approval') {
            return false;
        }

        $currentApproval = $approval->stockRequest->currentApproval();
        if (! $currentApproval || $currentApproval->id !== $approval->id) {
            return false;
        }

        return true;
    }

    /**
     * @param  int[]  $filterBusinessUnitIds
     */
    private function countPrPending(int $userId, array $filterBusinessUnitIds): int
    {
        $query = PrApproval::where('approver_id', $userId)
            ->where('status', 'pending')
            ->whereHas('purchaseRequest', function ($q) {
                $q->where('status', 'in_approval');
            });

        if (! empty($filterBusinessUnitIds)) {
            $query->whereHas('purchaseRequest', function ($q) use ($filterBusinessUnitIds) {
                $q->whereIn('business_unit_id', $filterBusinessUnitIds);
            });
        }

        return $query->count();
    }

    /**
     * @param  int[]  $filterBusinessUnitIds
     */
    private function prHistoryStats(int $userId, array $filterBusinessUnitIds)
    {
        $query = PrApproval::where('approver_id', $userId)
            ->whereIn('status', ['approved', 'rejected']);

        if (! empty($filterBusinessUnitIds)) {
            $query->whereHas('purchaseRequest', function ($q) use ($filterBusinessUnitIds) {
                $q->whereIn('business_unit_id', $filterBusinessUnitIds);
            });
        }

        return $query->select([
            DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
            DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count"),
        ])->first();
    }

    /**
     * @param  int[]  $filterBusinessUnitIds
     */
    private function countStPending(int $userId, array $filterBusinessUnitIds): int
    {
        $query = StockApproval::where('approver_id', $userId)
            ->where('status', 'pending')
            ->whereHas('stockRequest', function ($q) {
                $q->where('status', 'in_approval');
            });

        if (! empty($filterBusinessUnitIds)) {
            $query->whereHas('stockRequest', function ($q) use ($filterBusinessUnitIds) {
                $q->whereIn('business_unit_id', $filterBusinessUnitIds);
            });
        }

        return $query->count();
    }

    /**
     * @param  int[]  $filterBusinessUnitIds
     */
    private function stHistoryStats(int $userId, array $filterBusinessUnitIds)
    {
        $query = StockApproval::where('approver_id', $userId)
            ->whereIn('status', ['approved', 'rejected']);

        if (! empty($filterBusinessUnitIds)) {
            $query->whereHas('stockRequest', function ($q) use ($filterBusinessUnitIds) {
                $q->whereIn('business_unit_id', $filterBusinessUnitIds);
            });
        }

        return $query->select([
            DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
            DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count"),
        ])->first();
    }

    /**
     * Build the merged + sorted pending approvals collection.
     *
     * @param  int[]  $filterBusinessUnitIds
     */
    private function buildPendingApprovals(int $userId, array $filterBusinessUnitIds): Collection
    {
        $pendingPrQuery = PrApproval::with([
            'purchaseRequest' => function ($query) {
                $query->select('id', 'pr_number', 'user_id', 'department_id', 'business_unit_id',
                    'total_amount', 'currency', 'status', 'used_for', 'created_at', 'updated_at');
            },
            'purchaseRequest.user:id,name,email',
            'purchaseRequest.department:id,name,code',
            'purchaseRequest.businessUnit:id,name,code',
        ])
            ->where('approver_id', $userId)
            ->where('status', 'pending')
            ->whereHas('purchaseRequest', function ($q) {
                $q->where('status', 'in_approval');
            });

        $pendingStQuery = StockApproval::with([
            'stockRequest' => function ($query) {
                $query->select('id', 'st_number', 'user_id', 'department_id', 'business_unit_id',
                    'purpose', 'status', 'created_at', 'updated_at');
            },
            'stockRequest.user:id,name,email',
            'stockRequest.department:id,name,code',
            'stockRequest.businessUnit:id,name,code',
            'stockRequest.items:id,stock_request_id,total',
        ])
            ->where('approver_id', $userId)
            ->where('status', 'pending')
            ->whereHas('stockRequest', function ($q) {
                $q->where('status', 'in_approval');
            });

        if (! empty($filterBusinessUnitIds)) {
            $pendingPrQuery->whereHas('purchaseRequest', function ($q) use ($filterBusinessUnitIds) {
                $q->whereIn('business_unit_id', $filterBusinessUnitIds);
            });
            $pendingStQuery->whereHas('stockRequest', function ($q) use ($filterBusinessUnitIds) {
                $q->whereIn('business_unit_id', $filterBusinessUnitIds);
            });
        }

        $pendingPrApprovals = $pendingPrQuery->orderBy('created_at', 'asc')->get();
        $pendingStApprovals = $pendingStQuery->orderBy('created_at', 'asc')->get();

        $pending = collect();

        foreach ($pendingPrApprovals as $approval) {
            $pending->push($this->transformPrApproval($approval, $userId, includeProcessableActions: true));
        }

        foreach ($pendingStApprovals as $approval) {
            $pending->push($this->transformStApproval($approval, $userId, includeProcessableActions: true));
        }

        return $pending->sortBy('created_at')->values();
    }

    /**
     * Build the merged + sorted recent (approved/rejected) approvals collection.
     *
     * @param  int[]  $filterBusinessUnitIds
     */
    private function buildRecentApprovals(int $userId, array $filterBusinessUnitIds): Collection
    {
        $recentPrQuery = PrApproval::with([
            'purchaseRequest' => function ($query) {
                $query->select('id', 'pr_number', 'user_id', 'department_id', 'business_unit_id',
                    'total_amount', 'currency', 'status', 'used_for', 'created_at', 'updated_at');
            },
            'purchaseRequest.user:id,name,email',
            'purchaseRequest.department:id,name,code',
            'purchaseRequest.businessUnit:id,name,code',
        ])
            ->where('approver_id', $userId)
            ->whereIn('status', ['approved', 'rejected']);

        $recentStQuery = StockApproval::with([
            'stockRequest' => function ($query) {
                $query->select('id', 'st_number', 'user_id', 'department_id', 'business_unit_id',
                    'purpose', 'status', 'created_at', 'updated_at');
            },
            'stockRequest.user:id,name,email',
            'stockRequest.department:id,name,code',
            'stockRequest.businessUnit:id,name,code',
            'stockRequest.items:id,stock_request_id,total',
        ])
            ->where('approver_id', $userId)
            ->whereIn('status', ['approved', 'rejected']);

        if (! empty($filterBusinessUnitIds)) {
            $recentPrQuery->whereHas('purchaseRequest', function ($q) use ($filterBusinessUnitIds) {
                $q->whereIn('business_unit_id', $filterBusinessUnitIds);
            });
            $recentStQuery->whereHas('stockRequest', function ($q) use ($filterBusinessUnitIds) {
                $q->whereIn('business_unit_id', $filterBusinessUnitIds);
            });
        }

        $recentPrApprovals = $recentPrQuery->orderBy('responded_at', 'desc')->limit(10)->get();
        $recentStApprovals = $recentStQuery->orderBy('responded_at', 'desc')->limit(10)->get();

        $recent = collect();

        foreach ($recentPrApprovals as $approval) {
            $recent->push($this->transformPrApproval($approval, $userId, includeProcessableActions: false));
        }

        foreach ($recentStApprovals as $approval) {
            $recent->push($this->transformStApproval($approval, $userId, includeProcessableActions: false));
        }

        return $recent->sortByDesc('responded_at')->take(10)->values();
    }

    /**
     * Transform a PrApproval row to the listing payload shape.
     */
    private function transformPrApproval(PrApproval $approval, int $userId, bool $includeProcessableActions): array
    {
        $can = $includeProcessableActions
            ? [
                'approve' => $this->canProcessApproval($approval, $userId),
                'reject' => $this->canProcessApproval($approval, $userId),
            ]
            : ['approve' => false, 'reject' => false];

        $payload = [
            'id' => $approval->id,
            'type' => 'PR',
            'request_number' => $approval->purchaseRequest->pr_number,
            'request_id' => $approval->purchaseRequest->id,
            'used_for' => $approval->purchaseRequest->used_for,
            'total_amount' => $approval->purchaseRequest->total_amount,
            'currency' => $approval->purchaseRequest->currency ?? 'IDR',
            'user' => $approval->purchaseRequest->user ?? ['id' => 0, 'name' => 'Unknown User', 'email' => ''],
            'department' => $approval->purchaseRequest->department ?? ['id' => 0, 'name' => 'Unknown Department', 'code' => '-'],
            'business_unit' => $approval->purchaseRequest->businessUnit ?? ['id' => 0, 'name' => 'Unknown BU', 'code' => '-'],
            'step_order' => $approval->step_order,
            'approval_type' => $approval->approval_type ?? $approval->task_type,
            'status' => $approval->status,
            'waiting_since' => $approval->created_at->toISOString(),
            'can' => $can,
        ];

        if ($includeProcessableActions) {
            $payload['created_at'] = $approval->created_at;
        } else {
            $payload['responded_at'] = $approval->responded_at;
        }

        return $payload;
    }

    /**
     * Transform a StockApproval row to the listing payload shape.
     */
    private function transformStApproval(StockApproval $approval, int $userId, bool $includeProcessableActions): array
    {
        $totalAmount = $approval->stockRequest->items->sum('total');

        $can = $includeProcessableActions
            ? [
                'approve' => $this->canProcessStockApproval($approval, $userId),
                'reject' => $this->canProcessStockApproval($approval, $userId),
            ]
            : ['approve' => false, 'reject' => false];

        $payload = [
            'id' => $approval->id,
            'type' => 'ST',
            'request_number' => $approval->stockRequest->st_number,
            'request_id' => $approval->stockRequest->id,
            'used_for' => $approval->stockRequest->purpose,
            'total_amount' => $totalAmount,
            'currency' => 'IDR',
            'user' => $approval->stockRequest->user ?? ['id' => 0, 'name' => 'Unknown User', 'email' => ''],
            'department' => $approval->stockRequest->department ?? ['id' => 0, 'name' => 'Unknown Department', 'code' => '-'],
            'business_unit' => $approval->stockRequest->businessUnit ?? ['id' => 0, 'name' => 'Unknown BU', 'code' => '-'],
            'step_order' => $approval->step_order,
            'approval_type' => $approval->approval_type ?? $approval->task_type,
            'status' => $approval->status,
            'waiting_since' => $approval->created_at->toISOString(),
            'can' => $can,
        ];

        if ($includeProcessableActions) {
            $payload['created_at'] = $approval->created_at;
        } else {
            $payload['responded_at'] = $approval->responded_at;
        }

        return $payload;
    }
}
