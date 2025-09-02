<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Modules\WNS\PrApproval;
use App\Models\Modules\WNS\PurchaseRequest;
use App\Services\Modules\WNS\ApprovalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ApprovalController extends Controller
{
    protected ApprovalWorkflowService $workflowService;
    
    public function __construct(ApprovalWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Display pending approvals for the current user
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = PrApproval::with([
            'purchaseRequest.user',
            'purchaseRequest.department',
            'purchaseRequest.items'
        ])
        ->where('approver_id', $user->id)
        ->where('status', 'pending')
        ->whereHas('purchaseRequest', function ($q) use ($request) {
            $q->where('business_unit_id', $request->header('X-Business-Unit-ID'));
        });

        // Apply filters
        if ($request->filled('pr_number')) {
            $query->whereHas('purchaseRequest', function ($q) use ($request) {
                $q->where('pr_number', 'like', '%' . $request->pr_number . '%');
            });
        }

        if ($request->filled('department_id')) {
            $query->whereHas('purchaseRequest', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->filled('amount_min')) {
            $query->whereHas('purchaseRequest', function ($q) use ($request) {
                $q->where('total_amount', '>=', $request->amount_min);
            });
        }

        if ($request->filled('amount_max')) {
            $query->whereHas('purchaseRequest', function ($q) use ($request) {
                $q->where('total_amount', '<=', $request->amount_max);
            });
        }

        if ($request->filled('overdue')) {
            if ($request->boolean('overdue')) {
                $query->overdue();
            }
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'assigned_at');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $approvals = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $approvals->items(),
            'meta' => [
                'current_page' => $approvals->currentPage(),
                'last_page' => $approvals->lastPage(),
                'per_page' => $approvals->perPage(),
                'total' => $approvals->total(),
                'from' => $approvals->firstItem(),
                'to' => $approvals->lastItem(),
            ],
            'links' => [
                'first' => $approvals->url(1),
                'last' => $approvals->url($approvals->lastPage()),
                'prev' => $approvals->previousPageUrl(),
                'next' => $approvals->nextPageUrl(),
            ]
        ]);
    }

    /**
     * Display the specified approval
     */
    public function show(PrApproval $prApproval)
    {
        // Check if current user is the assigned approver
        if ($prApproval->approver_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this approval'
            ], 403);
        }
        
        $prApproval->load([
            'purchaseRequest.user',
            'purchaseRequest.department',
            'purchaseRequest.items.expenseDepartment',
            'purchaseRequest.approvals.approver'
        ]);
        
        return response()->json([
            'success' => true,
            'data' => $prApproval
        ]);
    }

    /**
     * Process approval action (approve/reject)
     */
    public function process(Request $request)
    {
        $request->validate([
            'approval_id' => 'required|exists:pr_approvals,id',
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:1000'
        ]);
        
        $prApproval = PrApproval::findOrFail($request->approval_id);
        
        // Check if current user is the assigned approver
        if ($prApproval->approver_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to process this approval'
            ], 403);
        }
        
        if ($prApproval->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This approval has already been processed'
            ], 400);
        }
        
        // Validate notes for rejection
        if ($request->action === 'reject' && empty(trim($request->notes))) {
            return response()->json([
                'success' => false,
                'message' => 'Comments are required when rejecting a request'
            ], 400);
        }
        
        try {
            $success = $this->workflowService->processApproval(
                $prApproval,
                $request->action,
                $request->notes
            );
            
            if ($success) {
                $purchaseRequest = $prApproval->purchaseRequest;
                
                $message = $request->action === 'approve' 
                    ? "Purchase Request {$purchaseRequest->pr_number} approved successfully"
                    : "Purchase Request {$purchaseRequest->pr_number} rejected successfully";
                
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'pr_number' => $purchaseRequest->pr_number,
                        'action' => $request->action,
                        'status' => $purchaseRequest->fresh()->status,
                        'approval' => $prApproval->fresh()
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process approval'
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process approval: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve a purchase request
     */
    public function approve(Request $request, PrApproval $prApproval)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000'
        ]);
        
        // Check if current user is the assigned approver
        if ($prApproval->approver_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to approve this request'
            ], 403);
        }
        
        if ($prApproval->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This approval has already been processed'
            ], 400);
        }
        
        try {
            $success = $this->workflowService->processApproval(
                $prApproval,
                'approved',
                $request->notes
            );
            
            if ($success) {
                $purchaseRequest = $prApproval->purchaseRequest;
                
                return response()->json([
                    'success' => true,
                    'message' => "Purchase Request {$purchaseRequest->pr_number} approved successfully",
                    'data' => [
                        'pr_number' => $purchaseRequest->pr_number,
                        'status' => $purchaseRequest->fresh()->status,
                        'approval' => $prApproval->fresh()
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to approve request'
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a purchase request
     */
    public function reject(Request $request, PrApproval $prApproval)
    {
        $request->validate([
            'notes' => 'required|string|max:1000'
        ]);
        
        // Check if current user is the assigned approver
        if ($prApproval->approver_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to reject this request'
            ], 403);
        }
        
        if ($prApproval->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This approval has already been processed'
            ], 400);
        }
        
        try {
            $success = $this->workflowService->processApproval(
                $prApproval,
                'rejected',
                $request->notes
            );
            
            if ($success) {
                $purchaseRequest = $prApproval->purchaseRequest;
                
                return response()->json([
                    'success' => true,
                    'message' => "Purchase Request {$purchaseRequest->pr_number} rejected successfully",
                    'data' => [
                        'pr_number' => $purchaseRequest->pr_number,
                        'status' => $purchaseRequest->fresh()->status,
                        'approval' => $prApproval->fresh()
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reject request'
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get approval statistics for the current user
     */
    public function statistics(Request $request)
    {
        $user = Auth::user();
        
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : null;
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : null;
        
        $statistics = $this->workflowService->getApprovalStatistics($user, $startDate, $endDate);
        
        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Get approval history for the current user
     */
    public function history(Request $request)
    {
        $user = Auth::user();
        
        $query = PrApproval::with([
            'purchaseRequest.user',
            'purchaseRequest.department'
        ])
        ->where('approver_id', $user->id)
        ->whereIn('status', ['approved', 'rejected'])
        ->whereHas('purchaseRequest', function ($q) use ($request) {
            $q->where('business_unit_id', $request->header('X-Business-Unit-ID'));
        });

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('responded_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('responded_at', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'responded_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $history = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $history->items(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
                'from' => $history->firstItem(),
                'to' => $history->lastItem(),
            ],
            'links' => [
                'first' => $history->url(1),
                'last' => $history->url($history->lastPage()),
                'prev' => $history->previousPageUrl(),
                'next' => $history->nextPageUrl(),
            ]
        ]);
    }
}