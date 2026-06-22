<?php

namespace App\Services\Modules\Purchasing\PurchaseRequest;

use App\Models\Core\Department;
use App\Models\Modules\Purchasing\PurchaseRequest\PrItem;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseRequestService
{
    protected UniversalPRNumberingService $numberingService;

    protected ApprovalWorkflowService $workflowService;

    protected PrStatusTransitioner $statusTransitioner;

    protected PrCacheManager $cacheManager;

    protected PrAccessibleQueryBuilder $accessibleQueryBuilder;

    public function __construct(
        UniversalPRNumberingService $numberingService,
        ApprovalWorkflowService $workflowService,
        ?PrStatusTransitioner $statusTransitioner = null,
        ?PrCacheManager $cacheManager = null,
        ?PrAccessibleQueryBuilder $accessibleQueryBuilder = null,
    ) {
        $this->numberingService = $numberingService;
        $this->workflowService = $workflowService;
        $this->statusTransitioner = $statusTransitioner ?? app(PrStatusTransitioner::class);
        $this->cacheManager = $cacheManager ?? app(PrCacheManager::class);
        $this->accessibleQueryBuilder = $accessibleQueryBuilder ?? app(PrAccessibleQueryBuilder::class);
    }

    /**
     * Get Purchase Requests based on user hierarchy and filters.
     *
     * Delegates to {@see PrAccessibleQueryBuilder::forCurrentUser()}.
     */
    public function getPurchaseRequestsQuery(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        return $this->accessibleQueryBuilder->forCurrentUser($filters);
    }

    /**
     * Get ALL Purchase Requests in current business unit without hierarchy filtering.
     *
     * Delegates to {@see PrAccessibleQueryBuilder::forCurrentBusinessUnit()}.
     *
     * @param  array  $filters  Optional filters (status, date_from, date_to, etc.)
     */
    public function getAllPurchaseRequestsQuery(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        return $this->accessibleQueryBuilder->forCurrentBusinessUnit($filters);
    }

    /**
     * Create a new Purchase Request
     */
    public function createPurchaseRequest(array $data): PurchaseRequest
    {
        return DB::transaction(function () use ($data) {
            // Generate PR number
            $prNumber = $this->numberingService->generatePRNumber(
                Auth::user(),
                session('current_business_unit_id'),
                null,
                Carbon::parse($data['date_of_request'])
            );

            // Create purchase request
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => $prNumber['formatted_number'],
                'business_unit_id' => session('current_business_unit_id'),
                'department_id' => session('current_department_id'),
                'user_id' => Auth::id(),
                'sequence_id' => $prNumber['sequence_id'],
                'used_for' => $data['used_for'],
                'date_of_request' => $data['date_of_request'],
                'designated_date' => $data['designated_date'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'currency' => $data['items'][0]['currency'] ?? 'IDR',
                'last_modified_by' => Auth::id(),
            ]);

            // Create PR items
            foreach ($data['items'] as $index => $itemData) {
                PrItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'item_order' => $index + 1,
                    'item_name' => $itemData['item_name'],
                    'brand_name' => $itemData['brand_name'] ?? null,
                    'item_description' => $itemData['item_description'] ?? null,
                    'supplier_name' => $itemData['supplier_name'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'unit_price' => $itemData['unit_price'],
                    'currency' => $itemData['currency'],
                    'expense_department_id' => $itemData['expense_department_id'],
                ]);
            }

            // Update total amount
            $purchaseRequest->updateTotalAmount();

            // Set submitted_at if status is submitted
            if (($data['status'] ?? 'draft') === 'submitted') {
                $purchaseRequest->update(['submitted_at' => now()]);
            }

            return $purchaseRequest;
        });
    }

    /**
     * Update an existing Purchase Request
     */
    public function updatePurchaseRequest(PurchaseRequest $purchaseRequest, array $data): PurchaseRequest
    {
        if (! $purchaseRequest->canBeEdited()) {
            throw new \Exception('This purchase request cannot be edited.');
        }

        return DB::transaction(function () use ($purchaseRequest, $data) {
            // Update purchase request
            $purchaseRequest->update([
                'used_for' => $data['used_for'],
                'category_id' => $data['category_id'] ?? $purchaseRequest->category_id,
                'date_of_request' => $data['date_of_request'],
                'currency' => $data['items'][0]['currency'] ?? $purchaseRequest->currency,
                'last_modified_by' => Auth::id(),
            ]);

            // Delete existing items
            $purchaseRequest->items()->delete();

            // Create new items
            foreach ($data['items'] as $index => $itemData) {
                PrItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'item_order' => $index + 1,
                    'item_name' => $itemData['item_name'],
                    'brand_name' => $itemData['brand_name'] ?? null,
                    'item_description' => $itemData['item_description'] ?? null,
                    'supplier_name' => $itemData['supplier_name'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'unit_price' => $itemData['unit_price'],
                    'currency' => $itemData['currency'],
                    'expense_department_id' => $itemData['expense_department_id'],
                ]);
            }

            // Update total amount
            $purchaseRequest->updateTotalAmount();

            // Reset approvals if not in draft
            $purchaseRequest->resetApprovals(Auth::user());

            return $purchaseRequest;
        });
    }

    /**
     * Submit an EXISTING DRAFT Purchase Request for approval.
     *
     * Delegates to {@see PrStatusTransitioner::submit()}.
     *
     * @throws \Exception If PR not in submittable state
     */
    public function submitPurchaseRequest(PurchaseRequest $purchaseRequest): PurchaseRequest
    {
        return $this->statusTransitioner->submit($purchaseRequest);
    }

    /**
     * Resubmit a rejected Purchase Request (reset workflow).
     *
     * Delegates to {@see PrStatusTransitioner::resubmit()}.
     */
    public function resubmitPurchaseRequest(PurchaseRequest $purchaseRequest): PurchaseRequest
    {
        return $this->statusTransitioner->resubmit($purchaseRequest);
    }

    /**
     * Void a Purchase Request.
     *
     * Delegates to {@see PrStatusTransitioner::void()}.
     */
    public function voidPurchaseRequest(PurchaseRequest $purchaseRequest, string $reason): PurchaseRequest
    {
        return $this->statusTransitioner->void($purchaseRequest, $reason);
    }

    /**
     * Get departments for business unit
     */
    public function getDepartments(): \Illuminate\Database\Eloquent\Collection
    {
        return Department::where('business_unit_id', session('current_business_unit_id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get validation rules for Purchase Request
     */
    public function getValidationRules(): array
    {
        return [
            'used_for' => 'required|string|max:1000',
            'date_of_request' => 'required|date',
            'designated_date' => 'nullable|date|after_or_equal:date_of_request',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.brand_name' => 'nullable|string|max:255',
            'items.*.item_description' => 'nullable|string|max:1000',
            'items.*.supplier_name' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.currency' => 'required|string|in:IDR,USD,EUR',
            'items.*.expense_department_id' => 'required|exists:departments,id',
        ];
    }

    /**
     * Get workflow status for API response
     */
    public function getWorkflowStatus(PurchaseRequest $purchaseRequest): array
    {
        $approvals = $purchaseRequest->approvals()
            ->with('approver:id,name,email')
            ->orderBy('step_order')
            ->get();

        $currentStep = $purchaseRequest->currentApproval();
        $nextApprovers = $purchaseRequest->getNextApprovers();

        return [
            'status' => $purchaseRequest->status,
            'current_step' => $currentStep ? $currentStep->step_order : null,
            'current_approver' => $currentStep ? $currentStep->approver->name : null,
            'total_steps' => $approvals->count(),
            'completed_steps' => $approvals->where('status', 'approved')->count(),
            'workflow_progress' => $approvals->count() > 0
                ? ($approvals->where('status', 'approved')->count() / $approvals->count()) * 100
                : 0,
            'approvals' => $approvals->map(function ($approval) {
                return [
                    'id' => $approval->id,
                    'step_order' => $approval->step_order,
                    'approver_name' => $approval->approver->name,
                    'approver_email' => $approval->approver->email,
                    'approval_type' => $approval->approval_type,
                    'status' => $approval->status,
                    'assigned_at' => $approval->assigned_at,
                    'responded_at' => $approval->responded_at,
                    'notes' => $approval->notes,
                ];
            }),
            'next_approvers' => $nextApprovers->map(function ($approver) {
                return [
                    'id' => $approver->id,
                    'name' => $approver->name,
                    'email' => $approver->email,
                ];
            }),
        ];
    }

    /**
     * Clear dashboard cache for affected users when PR is created/updated.
     *
     * Delegates to {@see PrCacheManager::clearForPurchaseRequest()}.
     *
     * @param  PurchaseRequest  $pr  The purchase request that was modified
     */
    public function clearDashboardCache(PurchaseRequest $pr): void
    {
        $this->cacheManager->clearForPurchaseRequest($pr);
    }

    /**
     * Mark a Purchase Request as approved offline/manually.
     *
     * Delegates to {@see PrStatusTransitioner::markAsOfflineApproved()}.
     *
     * @param  string|null  $notes  Optional notes explaining why offline approval was used
     * @param  string|null  $documentPath  Path to uploaded offline approval document
     * @param  string|null  $documentName  Original name of uploaded document
     */
    public function markAsOfflineApproved(PurchaseRequest $purchaseRequest, ?string $notes = null, ?string $documentPath = null, ?string $documentName = null): PurchaseRequest
    {
        return $this->statusTransitioner->markAsOfflineApproved(
            $purchaseRequest,
            $notes,
            $documentPath,
            $documentName
        );
    }
}
