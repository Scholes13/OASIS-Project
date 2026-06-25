<?php

namespace App\Services\Modules\Purchasing\StockRequest;

use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\Purchasing\StockRequest\StockNumberReservation;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

/**
 * Listing/query helpers for Stock Requests.
 *
 * Owns the index pipeline, reservation queries, listing transforms,
 * and show-page authorization helpers previously inlined in
 * StockRequestController. Behavior preserved verbatim.
 */
class StockRequestQueryService
{
    public function __construct(
        private StockRequestDocumentService $documentService,
    ) {}

    /**
     * Build the paginated, filtered "my requests" listing.
     */
    public function paginateForUser(Request $request, User $user, int $businessUnitId): LengthAwarePaginator
    {
        $filters = [
            'search' => $request->get('search', ''),
            'status' => $request->get('status', ''),
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
        ];

        $query = StockRequest::with([
            'department:id,name,code',
            'user:id,name,email',
        ])
            ->withCount('items')
            ->where('business_unit_id', $businessUnitId)
            ->where('user_id', $user->id);

        if ($filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('st_number', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%");
            });
        }

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['date_from']) {
            $query->whereDate('date_of_request', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate('date_of_request', '<=', $filters['date_to']);
        }

        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $stockRequests = $query
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($request->get('per_page', 10))
            ->withQueryString();

        $stockRequests->through(fn ($st) => $this->transformStockRequest($st, $user));

        return $stockRequests;
    }

    public function paginateForGaReview(Request $request, User $user, int $businessUnitId, int $departmentId): LengthAwarePaginator
    {
        $isGaDepartment = Department::query()
            ->where('id', $departmentId)
            ->where('business_unit_id', $businessUnitId)
            ->where('is_ga_stock_review_department', true)
            ->exists();

        abort_unless($isGaDepartment || $user->isSuperAdmin(), 403, 'Only GA review department can access this list.');

        $filters = [
            'search' => $request->get('search', ''),
            'status' => $request->get('status', 'ga_review'),
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
        ];

        $query = StockRequest::with([
            'department:id,name,code',
            'user:id,name,email',
        ])
            ->withCount('items')
            ->where('business_unit_id', $businessUnitId)
            ->whereIn('status', ['ga_review', 'ga_rejected', 'ready_for_purchasing']);

        if ($filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('st_number', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%");
            });
        }

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['date_from']) {
            $query->whereDate('date_of_request', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate('date_of_request', '<=', $filters['date_to']);
        }

        $stockRequests = $query
            ->orderBy($request->get('sort', 'created_at'), $request->get('direction', 'desc'))
            ->paginate($request->get('per_page', 10))
            ->withQueryString();

        $stockRequests->through(fn ($st) => $this->transformStockRequest($st, $user));

        return $stockRequests;
    }

    /**
     * Get reservations for the user.
     */
    public function getReservationsForUser(User $user, int $businessUnitId, string $search = ''): LengthAwarePaginator
    {
        $query = StockNumberReservation::with([
            'user:id,name',
            'department:id,name,code',
        ])
            ->where('business_unit_id', $businessUnitId)
            ->where('user_id', $user->id)
            ->whereNull('stock_request_id') // Only unredeemed reservations
            ->where('status', 'reserved'); // Only active reservations

        if ($search) {
            $query->where('reserved_number', 'like', "%{$search}%");
        }

        return $query->orderBy('created_at', 'desc')->paginate(5);
    }

    /**
     * Transform stock request with authorization props for listings.
     */
    public function transformStockRequest(StockRequest $st, User $user): array
    {
        $data = $st->toArray();

        $isOwner = $st->user_id === $user->id;
        $isSuperAdmin = $user->isSuperAdmin();

        $data['can'] = [
            'view' => true, // All users in BU can view
            'edit' => $isOwner && $st->isEditable(),
            'delete' => $isOwner && $st->status === 'draft',
            'void' => ($isOwner || $isSuperAdmin) && $st->canBeVoided(),
            'resubmit' => $isOwner && $st->status === 'rejected',
        ];

        return $data;
    }

    /**
     * Build the props for the Show Inertia view (eager-loads needed
     * relations and computes the authorization map).
     */
    public function getShowData(StockRequest $stockRequest, User $user, int $currentBusinessUnitId): array
    {
        $approverColumns = implode(',', $this->approverColumns());

        $stockRequest->load([
            'businessUnit:id,name,code',
            'department:id,name,code',
            'user:id,name,email',
            'items',
            "approvals.approver:{$approverColumns}",
            'lastModifiedBy:id,name',
            'gaReviewer:id,name',
            'offlineApprovedBy:id,name',
        ]);

        $authorization = $this->getShowAuthorization($stockRequest, $user, $currentBusinessUnitId);

        return [
            'stockRequest' => array_merge(
                $stockRequest->toArray(),
                [
                    'approval_progress' => $stockRequest->getApprovalProgress(),
                    'total_amount' => $stockRequest->total_amount,
                    'can' => $authorization,
                ]
            ),
            'can' => $authorization,
        ];
    }

    /**
     * Get authorization props for show page.
     */
    public function getShowAuthorization(StockRequest $st, User $user, int $currentBusinessUnitId): array
    {
        $isOwner = $st->user_id === $user->id;
        $isSuperAdmin = $user->isSuperAdmin();
        $currentApproval = $st->currentApproval();
        $canApprove = $currentApproval
            && $currentApproval->approver_id === $user->id
            && $st->status === 'in_approval'
            && $currentApproval->status === 'pending';
        $canResendApprovalEmail = $isOwner
            && $st->status === 'in_approval'
            && $currentApproval
            && $currentApproval->status === 'pending';
        $canGaReview = $st->status === 'ga_review'
            && ($isSuperAdmin || \App\Models\Core\Department::query()
                ->where('id', (int) session('current_department_id'))
                ->where('business_unit_id', $currentBusinessUnitId)
                ->where('is_ga_stock_review_department', true)
                ->exists());

        return [
            'edit' => $isOwner && $st->isEditable(),
            'delete' => $isOwner && $st->status === 'draft',
            'void' => ($isOwner || $isSuperAdmin) && $st->canBeVoided(),
            'resubmit' => $isOwner && in_array($st->status, ['rejected', 'ga_rejected'], true),
            'resendApprovalEmail' => $canResendApprovalEmail,
            'gaReviewApprove' => $canGaReview,
            'gaReviewReject' => $canGaReview,
            'approve' => $canApprove,
            'reject' => $canApprove,
            'downloadPdf' => true, // All users can download PDF
            'markOfflineApproved' => in_array($st->status, ['submitted', 'in_approval']) && $isOwner,
            'offlineApprovalDocument' => $st->offline_approval_document_path !== null
                && $this->documentService->canAccessOfflineApprovalDocument($st, $user, $currentBusinessUnitId),
        ];
    }

    /**
     * Build the props for the Create form Inertia view.
     */
    public function getCreateFormData(
        User $user,
        int $businessUnitId,
        int $departmentId,
        \App\Services\Modules\Purchasing\Shared\RequestFormDataProvider $formDataProvider,
    ): array {
        return [
            'mode' => 'create',
            'stockRequest' => null,
            'departments' => $formDataProvider->getAccessibleDepartments($user, $businessUnitId),
            'businessUnits' => $user->activeBusinessUnits()
                ->with('businessUnit:id,name,code')
                ->get()
                ->pluck('businessUnit')
                ->filter(),
            'availableApprovers' => $formDataProvider->getAvailableApprovers($user, $businessUnitId),
            'requiresSupervisorApproval' => $user->getAccessLevel($businessUnitId) === 'staff',
            'currentBusinessUnitId' => $businessUnitId,
            'currentDepartmentId' => $departmentId,
        ];
    }

    /**
     * Build the props for the Edit form Inertia view.
     */
    public function getEditFormData(
        User $user,
        StockRequest $stockRequest,
        \App\Services\Modules\Purchasing\Shared\RequestFormDataProvider $formDataProvider,
    ): array {
        $businessUnitId = $stockRequest->business_unit_id;
        $approverColumns = implode(',', $this->approverColumns());

        $stockRequest->load([
            'items',
            "approvals.approver:{$approverColumns}",
        ]);

        $approvalWorkflow = $stockRequest->approvals->map(function ($approval) {
            return [
                'approver_id' => $approval->approver_id,
                'task_type' => $approval->approval_type ?? 'approval',
            ];
        })->toArray();

        return [
            'mode' => 'edit',
            'stockRequest' => array_merge($stockRequest->toArray(), [
                'approval_workflow' => $approvalWorkflow,
            ]),
            'departments' => $formDataProvider->getAccessibleDepartments($user, $businessUnitId),
            'businessUnits' => $user->activeBusinessUnits()
                ->with('businessUnit:id,name,code')
                ->get()
                ->pluck('businessUnit')
                ->filter(),
            'availableApprovers' => $formDataProvider->getAvailableApprovers($user, $businessUnitId),
            'requiresSupervisorApproval' => $user->getAccessLevel($businessUnitId) === 'staff',
            'currentBusinessUnitId' => $businessUnitId,
            'currentDepartmentId' => $stockRequest->department_id,
        ];
    }

    private function approverColumns(): array
    {
        $columns = ['id', 'name', 'email'];

        if (Schema::hasColumn('users', 'avatar_url')) {
            $columns[] = 'avatar_url';
        }

        return $columns;
    }
}
