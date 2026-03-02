<?php

namespace App\Http\Controllers\Modules\Purchasing\PurchaseRequest\Api;

use App\Http\Controllers\Controller;
use App\Models\Modules\Purchasing\PurchaseRequest\PrItem;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Modules\Purchasing\PurchaseRequest\ApprovalWorkflowService;
use App\Services\Modules\Purchasing\PurchaseRequest\UniversalPRNumberingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseRequestController extends Controller
{
    protected UniversalPRNumberingService $numberingService;

    protected ApprovalWorkflowService $workflowService;

    public function __construct(UniversalPRNumberingService $numberingService, ApprovalWorkflowService $workflowService)
    {
        $this->numberingService = $numberingService;
        $this->workflowService = $workflowService;
    }

    /**
     * Display a listing of purchase requests
     */
    public function index(Request $request)
    {
        $query = PurchaseRequest::with(['department', 'user', 'items', 'approvals'])
            ->where('business_unit_id', $request->header('X-Business-Unit-ID'));

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date_of_request', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date_of_request', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100); // Max 100 items per page
        $purchaseRequests = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $purchaseRequests->items(),
            'meta' => [
                'current_page' => $purchaseRequests->currentPage(),
                'last_page' => $purchaseRequests->lastPage(),
                'per_page' => $purchaseRequests->perPage(),
                'total' => $purchaseRequests->total(),
                'from' => $purchaseRequests->firstItem(),
                'to' => $purchaseRequests->lastItem(),
            ],
            'links' => [
                'first' => $purchaseRequests->url(1),
                'last' => $purchaseRequests->url($purchaseRequests->lastPage()),
                'prev' => $purchaseRequests->previousPageUrl(),
                'next' => $purchaseRequests->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Store a newly created purchase request
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'used_for' => 'required|string|max:1000',
            'date_of_request' => 'required|date',
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
        ]);

        DB::beginTransaction();

        try {
            // Generate PR number
            $prNumber = $this->numberingService->generatePRNumber(
                Auth::user(),
                $request->header('X-Business-Unit-ID'),
                null, // departmentId - will use user's primary department
                Carbon::parse($validatedData['date_of_request'])
            );

            // Create purchase request
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => $prNumber['formatted_number'],
                'business_unit_id' => $request->header('X-Business-Unit-ID'),
                'department_id' => Auth::user()->primary_department_id,
                'user_id' => Auth::id(),
                'sequence_id' => $prNumber['sequence_id'],
                'used_for' => $validatedData['used_for'],
                'date_of_request' => $validatedData['date_of_request'],
                'status' => 'draft',
                'currency' => $validatedData['items'][0]['currency'],
                'last_modified_by' => Auth::id(),
            ]);

            // Create PR items
            foreach ($validatedData['items'] as $index => $itemData) {
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

            DB::commit();

            $purchaseRequest->load(['department', 'user', 'items']);

            return response()->json([
                'success' => true,
                'message' => 'Purchase request created successfully',
                'data' => $purchaseRequest,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified purchase request
     */
    public function show(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load([
            'department',
            'user',
            'items.expenseDepartment',
            'approvals.approver',
            'businessUnit',
        ]);

        return response()->json([
            'success' => true,
            'data' => $purchaseRequest,
        ]);
    }

    /**
     * Update the specified purchase request
     */
    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        // Check if user can edit
        if (! $purchaseRequest->canBeEdited()) {
            return response()->json([
                'success' => false,
                'message' => 'This purchase request cannot be edited',
            ], 403);
        }

        // Check if user owns the PR or has admin rights
        if ($purchaseRequest->user_id !== Auth::id() && ! (Auth::user()->hasRole('admin') || Auth::user()->hasRole('super_admin'))) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this purchase request',
            ], 403);
        }

        $validatedData = $request->validate([
            'used_for' => 'required|string|max:1000',
            'date_of_request' => 'required|date',
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
        ]);

        DB::beginTransaction();

        try {
            // Update purchase request
            $purchaseRequest->update([
                'used_for' => $validatedData['used_for'],
                'date_of_request' => $validatedData['date_of_request'],
                'currency' => $validatedData['items'][0]['currency'],
                'last_modified_by' => Auth::id(),
            ]);

            // Delete existing items
            $purchaseRequest->items()->delete();

            // Create new items
            foreach ($validatedData['items'] as $index => $itemData) {
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

            $purchaseRequest->load(['department', 'user', 'items']);

            return response()->json([
                'success' => true,
                'message' => 'Purchase request updated successfully',
                'data' => $purchaseRequest,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update purchase request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit purchase request for approval
     */
    public function submit(PurchaseRequest $purchaseRequest)
    {
        if (! $purchaseRequest->canBeSubmitted()) {
            return response()->json([
                'success' => false,
                'message' => 'This purchase request cannot be submitted',
            ], 400);
        }

        // Check if user owns the PR
        if ($purchaseRequest->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to submit this purchase request',
            ], 403);
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

            $purchaseRequest->load(['approvals.approver']);

            return response()->json([
                'success' => true,
                'message' => 'Purchase request submitted for approval successfully',
                'data' => $purchaseRequest,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit for approval',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Void purchase request
     */
    public function void(Request $request, PurchaseRequest $purchaseRequest)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if (! $purchaseRequest->canBeVoided()) {
            return response()->json([
                'success' => false,
                'message' => 'This purchase request cannot be voided',
            ], 400);
        }

        // Check if user owns the PR or has admin rights
        if ($purchaseRequest->user_id !== Auth::id() && ! (Auth::user()->hasRole('admin') || Auth::user()->hasRole('super_admin'))) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to void this purchase request',
            ], 403);
        }

        $purchaseRequest->void(Auth::user(), $request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Purchase request voided successfully',
            'data' => $purchaseRequest,
        ]);
    }

    /**
     * Remove the specified purchase request
     */
    public function destroy(PurchaseRequest $purchaseRequest)
    {
        if (! $purchaseRequest->canBeEdited()) {
            return response()->json([
                'success' => false,
                'message' => 'This purchase request cannot be deleted',
            ], 400);
        }

        // Check if user owns the PR or has admin rights
        if ($purchaseRequest->user_id !== Auth::id() && ! (Auth::user()->hasRole('admin') || Auth::user()->hasRole('super_admin'))) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this purchase request',
            ], 403);
        }

        $purchaseRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Purchase request deleted successfully',
        ]);
    }

    /**
     * Get workflow status for a purchase request
     */
    public function workflowStatus(PurchaseRequest $purchaseRequest)
    {
        $workflowStatus = $this->workflowService->getWorkflowStatus($purchaseRequest);

        return response()->json([
            'success' => true,
            'data' => $workflowStatus,
        ]);
    }
}
