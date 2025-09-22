<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Modules\WNS\PrItem;
use App\Models\Modules\WNS\PurchaseRequest;
use App\Services\Modules\WNS\ApprovalWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseRequestService
{
    protected UniversalPRNumberingService $numberingService;

    protected ApprovalWorkflowService $workflowService;

    public function __construct(
        UniversalPRNumberingService $numberingService,
        ApprovalWorkflowService $workflowService
    ) {
        $this->numberingService = $numberingService;
        $this->workflowService = $workflowService;
    }

    /**
     * Get Purchase Requests based on user hierarchy and filters
     */
    public function getPurchaseRequestsQuery(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $user = Auth::user();
        $accessLevel = $user->getAccessLevel();

        // Base query
        $query = PurchaseRequest::with(['department', 'user', 'items', 'approvals'])
            ->where('business_unit_id', session('current_business_unit_id'));

        // Apply hierarchy-based filtering
        switch ($accessLevel) {
            case 'super_admin':
            case 'director':
                // Can see all PRs in the business unit
                break;

            case 'department_head':
                // Department head can see all PRs in their department
                $query->where('department_id', $user->primary_department_id);
                break;

            case 'team_leader':
                // Team leader can see their own + subordinates' PRs
                $subordinateIds = $user->activeSubordinates()->pluck('id')->toArray();
                $subordinateIds[] = $user->id; // Include own PRs
                $query->whereIn('user_id', $subordinateIds);
                break;

            case 'staff':
            default:
                // Staff can only see their own PRs
                $query->byUser($user->id);
                break;
        }

        // Apply additional filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('date_of_request', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('date_of_request', '<=', $filters['date_to']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query;
    }

    /**
     * Create a new Purchase Request
     */
    public function createPurchaseRequest(array $data): PurchaseRequest
    {
        DB::beginTransaction();

        try {
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
                'keperluan' => $data['keperluan'],
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

            DB::commit();

            return $purchaseRequest;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing Purchase Request
     */
    public function updatePurchaseRequest(PurchaseRequest $purchaseRequest, array $data): PurchaseRequest
    {
        if (! $purchaseRequest->canBeEdited()) {
            throw new \Exception('This purchase request cannot be edited.');
        }

        DB::beginTransaction();

        try {
            // Update purchase request
            $purchaseRequest->update([
                'keperluan' => $data['keperluan'],
                'used_for' => $data['used_for'],
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

            DB::commit();

            return $purchaseRequest;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Submit Purchase Request for approval
     */
    public function submitPurchaseRequest(PurchaseRequest $purchaseRequest): PurchaseRequest
    {
        if (! $purchaseRequest->canBeSubmitted()) {
            throw new \Exception('This purchase request cannot be submitted.');
        }

        DB::beginTransaction();

        try {
            // Update status to submitted
            $purchaseRequest->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            // Create approval workflow
            $this->workflowService->createWorkflow($purchaseRequest);

            DB::commit();

            return $purchaseRequest;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Void a Purchase Request
     */
    public function voidPurchaseRequest(PurchaseRequest $purchaseRequest, string $reason): PurchaseRequest
    {
        if (! $purchaseRequest->canBeVoided()) {
            throw new \Exception('This purchase request cannot be voided.');
        }

        $purchaseRequest->void(Auth::user(), $reason);

        return $purchaseRequest;
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
            'keperluan' => 'required|string|max:500',
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
}
