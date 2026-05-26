<?php

namespace App\Services\Modules\Purchasing\PurchaseRequest;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\PurchaseRequest\PrNumberReservation;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Listing/query helpers for Purchase Requests.
 *
 * Owns the index/all listing pipelines, reservation queries,
 * and view-model transforms previously inlined in
 * PurchaseRequestController. Behavior preserved verbatim.
 */
class PurchaseRequestQueryService
{
    /**
     * Build the paginated, filtered "my requests" listing.
     */
    public function paginateForUser(Request $request, User $user, int $businessUnitId): LengthAwarePaginator
    {
        $filters = $this->parseFilters($request, includeDepartment: false);

        $query = PurchaseRequest::with([
            'department:id,name,code',
            'user:id,name,email',
            'category:id,name,code,color',
        ])
            ->withCount('items')
            ->withCount('approvals')
            ->withCount(['approvals as approved_approvals_count' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->where('business_unit_id', $businessUnitId)
            ->where('user_id', $user->id);

        $this->applyCommonFilters($query, $filters);

        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $purchaseRequests = $query
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($request->get('per_page', 10))
            ->withQueryString();

        $purchaseRequests->through(fn ($pr) => $this->transformPurchaseRequest($pr, $user));

        return $purchaseRequests;
    }

    /**
     * Build the paginated, filtered "all in BU" listing.
     *
     * @param  array<int>  $filterBusinessUnitIds
     */
    public function paginateForBusinessUnits(
        Request $request,
        User $user,
        array $filterBusinessUnitIds,
    ): LengthAwarePaginator {
        $filters = $this->parseFilters($request, includeDepartment: true);

        $query = PurchaseRequest::with([
            'department:id,name,code',
            'user:id,name,email',
            'category:id,name,code,color',
        ])
            ->withCount('items')
            ->withCount('approvals')
            ->withCount(['approvals as approved_approvals_count' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->whereIn('business_unit_id', $filterBusinessUnitIds);

        $this->applyCommonFilters($query, $filters);

        if ($filters['department_id']) {
            $query->where('department_id', $filters['department_id']);
        }

        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $purchaseRequests = $query
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        $purchaseRequests->through(fn ($pr) => $this->transformPurchaseRequest($pr, $user));

        return $purchaseRequests;
    }

    /**
     * Get reservations for the user (paginated, filtered by search).
     */
    public function getReservationsForUser(User $user, int $businessUnitId, string $search = ''): LengthAwarePaginator
    {
        $query = PrNumberReservation::with([
            'businessUnit:id,name,code',
            'department:id,name,code',
            'user:id,name,email',
        ])
            ->where('business_unit_id', $businessUnitId)
            ->where('user_id', $user->id)
            ->where('status', 'reserved');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('pr_number', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%");
            });
        }

        return $query->latest('reserved_at')->paginate(5, ['*'], 'reservations_page');
    }

    /**
     * Transform a purchase request for the listing view.
     */
    public function transformPurchaseRequest(PurchaseRequest $pr, User $user): array
    {
        $data = $pr->toArray();

        $data['can'] = [
            'view' => true, // User can always view their own PRs
            'edit' => $pr->canBeEdited() && $pr->user_id === $user->id,
            'delete' => $pr->canBeEdited() && $pr->user_id === $user->id,
            'void' => $pr->canBeVoided() && (
                $pr->user_id === $user->id ||
                in_array($user->getAccessLevel(), ['super_admin', 'executive', 'general_manager'])
            ),
            'resubmit' => $pr->status === 'rejected' && $pr->user_id === $user->id,
        ];

        $data['current_approval_step'] = null;
        $data['total_approval_steps'] = $pr->approvals_count ?? 0;

        $data['approval_progress'] = [
            'approved' => $pr->approved_approvals_count ?? 0,
            'total' => $pr->approvals_count ?? 0,
        ];

        return $data;
    }

    /**
     * Build the props for the Show Inertia view (eager-loads needed relations
     * and computes the authorization map). Behavior preserved verbatim.
     */
    public function getShowData(
        PurchaseRequest $purchaseRequest,
        User $user,
        int $currentBusinessUnitId,
        PurchaseRequestDocumentService $documentService,
    ): array {
        $purchaseRequest->load([
            'businessUnit:id,name,code',
            'department:id,name,code',
            'category:id,name,code,color',
            'user:id,name,email',
            'items.expenseDepartment:id,name,code',
            'approvals.approver:id,name,email',
            'lastModifiedBy:id,name',
            'offlineApprovedBy:id,name',
        ]);

        $authorization = $this->getShowAuthorization(
            $purchaseRequest,
            $user,
            $currentBusinessUnitId,
            $documentService,
        );

        return [
            'purchaseRequest' => array_merge(
                $purchaseRequest->toArray(),
                [
                    'approval_progress' => $purchaseRequest->getApprovalProgress(),
                    'can' => $authorization,
                ]
            ),
            'can' => $authorization,
        ];
    }

    /**
     * Get authorization props for the show page.
     * Behavior preserved verbatim from PurchaseRequestController.
     */
    public function getShowAuthorization(
        PurchaseRequest $pr,
        User $user,
        int $currentBusinessUnitId,
        PurchaseRequestDocumentService $documentService,
    ): array {
        $isOwner = $pr->user_id === $user->id;
        $isAdmin = in_array($user->getAccessLevel(), ['super_admin', 'executive', 'general_manager']);

        $currentApproval = $pr->currentApproval();
        $canApprove = $currentApproval && $currentApproval->approver_id === $user->id;
        $canReject = $canApprove; // Same logic for reject
        $canResendApprovalEmail = $isOwner
            && $pr->status === 'in_approval'
            && $currentApproval
            && $currentApproval->status === 'pending';

        return [
            'edit' => $pr->canBeEdited() && $isOwner,
            'delete' => $pr->canBeEdited() && $isOwner,
            'void' => $pr->canBeVoided() && ($isOwner || $isAdmin),
            'resubmit' => $pr->status === 'rejected' && $isOwner,
            'resendApprovalEmail' => $canResendApprovalEmail,
            'approve' => $canApprove,
            'reject' => $canReject,
            'downloadPdf' => in_array($pr->status, ['submitted', 'in_approval', 'approved']),
            'markOfflineApproved' => in_array($pr->status, ['submitted', 'in_approval']) && $isOwner,
            'supportingDocument' => $pr->supporting_document_path !== null
                && $documentService->canAccessSupportingDocument($pr, $user, $currentBusinessUnitId),
        ];
    }

    /**
     * Available status filter options for the listing dropdown.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function statusFilterOptions(): array
    {
        return [
            ['value' => 'draft', 'label' => 'Draft'],
            ['value' => 'submitted', 'label' => 'Submitted'],
            ['value' => 'in_approval', 'label' => 'In Approval'],
            ['value' => 'approved', 'label' => 'Approved'],
            ['value' => 'rejected', 'label' => 'Rejected'],
            ['value' => 'voided', 'label' => 'Voided'],
        ];
    }

    /**
     * Build the props for the Create form Inertia view.
     *
     * Note: PR `create` historically excluded super_admin accounts.
     */
    public function getCreateFormData(
        User $user,
        int $businessUnitId,
        int $departmentId,
        \App\Services\Modules\Purchasing\Shared\RequestFormDataProvider $formDataProvider,
    ): array {
        return [
            'categories' => $formDataProvider->getPrCategories($businessUnitId),
            'departments' => $formDataProvider->getAccessibleDepartments($user, $businessUnitId),
            'businessUnits' => $user->activeBusinessUnits()
                ->with('businessUnit:id,name,code')
                ->get()
                ->pluck('businessUnit')
                ->filter(),
            'availableApprovers' => $formDataProvider->getAvailableApprovers(
                $user,
                $businessUnitId,
                excludeSuperAdmin: true,
            ),
            'currentBusinessUnitId' => $businessUnitId,
            'currentDepartmentId' => $departmentId,
        ];
    }

    /**
     * Build the props for the Edit form Inertia view.
     *
     * Note: PR `editInertia` historically did NOT exclude super_admin.
     */
    public function getEditFormData(
        User $user,
        PurchaseRequest $purchaseRequest,
        \App\Services\Modules\Purchasing\Shared\RequestFormDataProvider $formDataProvider,
    ): array {
        $businessUnitId = $purchaseRequest->business_unit_id;

        $purchaseRequest->load([
            'items.expenseDepartment:id,name,code',
            'category:id,name,code,color',
            'approvals.approver:id,name,email',
        ]);

        $approvalWorkflow = $purchaseRequest->approvals->map(function ($approval) {
            return [
                'approver_id' => $approval->approver_id,
                'task_type' => $approval->approval_type ?? 'approval',
            ];
        })->toArray();

        return [
            'mode' => 'edit',
            'purchaseRequest' => array_merge($purchaseRequest->toArray(), [
                'approval_workflow' => $approvalWorkflow,
            ]),
            'categories' => $formDataProvider->getPrCategories($businessUnitId),
            'departments' => $formDataProvider->getAccessibleDepartments($user, $businessUnitId),
            'businessUnits' => $user->activeBusinessUnits()
                ->with('businessUnit:id,name,code')
                ->get()
                ->pluck('businessUnit')
                ->filter(),
            'availableApprovers' => $formDataProvider->getAvailableApprovers(
                $user,
                $businessUnitId,
            ),
            'currentBusinessUnitId' => $businessUnitId,
            'currentDepartmentId' => $purchaseRequest->department_id,
        ];
    }

    /**
     * Pull supported listing filters from the request.
     */
    private function parseFilters(Request $request, bool $includeDepartment): array
    {
        $filters = [
            'search' => $request->get('search', ''),
            'status' => $request->get('status', ''),
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
        ];

        if ($includeDepartment) {
            $filters['department_id'] = $request->get('department_id', '');
        }

        return $filters;
    }

    /**
     * Apply the search/status/date filters shared by index and all listings.
     */
    private function applyCommonFilters($query, array $filters): void
    {
        if ($filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('pr_number', 'like', "%{$search}%")
                    ->orWhere('used_for', 'like', "%{$search}%");
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
    }
}
