<?php

namespace App\Http\Controllers;

use App\Models\Modules\WNS\PrApproval;
use App\Models\Modules\WNS\PurchaseRequest;
use App\Services\Modules\WNS\ApprovalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    protected ApprovalWorkflowService $workflowService;
    
    public function __construct(ApprovalWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }
    /**
     * Display a listing of pending approvals for the current user
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get pending approvals assigned to current user in current business unit
        $pendingApprovals = PrApproval::with([
            'purchaseRequest.user',
            'purchaseRequest.department',
            'purchaseRequest.items'
        ])
        ->where('approver_id', $user->id)
        ->where('status', 'pending')
        ->whereHas('purchaseRequest', function ($query) {
            $query->where('business_unit_id', session('current_business_unit_id'));
        })
        ->orderBy('assigned_at', 'asc')
        ->paginate(15);
        
        // Get recent approval history
        $recentApprovals = PrApproval::with([
            'purchaseRequest.user',
            'purchaseRequest.department'
        ])
        ->where('approver_id', $user->id)
        ->whereIn('status', ['approved', 'rejected'])
        ->whereHas('purchaseRequest', function ($query) {
            $query->where('business_unit_id', session('current_business_unit_id'));
        })
        ->orderBy('responded_at', 'desc')
        ->limit(10)
        ->get();
        
        // Get approval statistics
        $stats = [
            'pending_count' => $pendingApprovals->total(),
            'approved_this_month' => PrApproval::where('approver_id', $user->id)
                ->where('status', 'approved')
                ->whereMonth('responded_at', now()->month)
                ->whereYear('responded_at', now()->year)
                ->count(),
            'rejected_this_month' => PrApproval::where('approver_id', $user->id)
                ->where('status', 'rejected')
                ->whereMonth('responded_at', now()->month)
                ->whereYear('responded_at', now()->year)
                ->count(),
            'total_processed' => PrApproval::where('approver_id', $user->id)
                ->whereIn('status', ['approved', 'rejected'])
                ->count(),
        ];
        
        return view('approvals.index', compact('pendingApprovals', 'recentApprovals', 'stats'));
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
            return back()->with('error', 'You are not authorized to approve this request.');
        }
        
        if ($prApproval->status !== 'pending') {
            return back()->with('error', 'This approval has already been processed.');
        }
        
        DB::beginTransaction();
        
        try {
            // Update the approval
            $prApproval->update([
                'status' => 'approved',
                'notes' => $request->notes,
                'responded_at' => now(),
            ]);
            
            $purchaseRequest = $prApproval->purchaseRequest;
            
            // Check if this was the last pending approval
            $remainingPendingApprovals = $purchaseRequest->pendingApprovals()->count();
            
            if ($remainingPendingApprovals === 0) {
                // All approvals completed - mark PR as approved
                $purchaseRequest->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);
                $message = "Purchase Request {$purchaseRequest->pr_number} has been fully approved.";
            } else {
                // More approvals needed - mark as in_approval
                $purchaseRequest->update([
                    'status' => 'in_approval'
                ]);
                $message = "Your approval has been recorded. {$remainingPendingApprovals} more approval(s) needed.";
            }
            
            DB::commit();
            
            return back()->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to approve request: ' . $e->getMessage());
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
            return back()->with('error', 'You are not authorized to reject this request.');
        }
        
        if ($prApproval->status !== 'pending') {
            return back()->with('error', 'This approval has already been processed.');
        }
        
        DB::beginTransaction();
        
        try {
            // Update the approval
            $prApproval->update([
                'status' => 'rejected',
                'notes' => $request->notes,
                'responded_at' => now(),
            ]);
            
            // Mark the entire PR as rejected
            $purchaseRequest = $prApproval->purchaseRequest;
            $purchaseRequest->update([
                'status' => 'rejected',
                'rejected_at' => now(),
            ]);
            
            DB::commit();
            
            return back()->with('success', "Purchase Request {$purchaseRequest->pr_number} has been rejected.");
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reject request: ' . $e->getMessage());
        }
    }
    
    /**
     * Process approval action via AJAX
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
                'message' => 'You are not authorized to process this request.'
            ], 403);
        }
        
        if ($prApproval->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This approval has already been processed.'
            ], 400);
        }
        
        // Validate notes for rejection
        if ($request->action === 'reject' && empty(trim($request->notes))) {
            return response()->json([
                'success' => false,
                'message' => 'Comments are required when rejecting a request.'
            ], 400);
        }
        
        DB::beginTransaction();
        
        try {
            $status = $request->action === 'approve' ? 'approved' : 'rejected';
            
            // Update the approval
            $prApproval->update([
                'status' => $status,
                'notes' => $request->notes,
                'responded_at' => now(),
            ]);
            
            $purchaseRequest = $prApproval->purchaseRequest;
            
            if ($request->action === 'approve') {
                // Check if this was the last pending approval
                $remainingPendingApprovals = $purchaseRequest->pendingApprovals()->count();
                
                if ($remainingPendingApprovals === 0) {
                    // All approvals completed - mark PR as approved
                    $purchaseRequest->update([
                        'status' => 'approved',
                        'approved_at' => now(),
                    ]);
                    $message = "Purchase Request {$purchaseRequest->pr_number} has been fully approved.";
                } else {
                    // More approvals needed - mark as in_approval
                    $purchaseRequest->update([
                        'status' => 'in_approval'
                    ]);
                    $message = "Your approval has been recorded. {$remainingPendingApprovals} more approval(s) needed.";
                }
            } else {
                // Mark the entire PR as rejected
                $purchaseRequest->update([
                    'status' => 'rejected',
                    'rejected_at' => now(),
                ]);
                $message = "Purchase Request {$purchaseRequest->pr_number} has been rejected.";
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'pr_number' => $purchaseRequest->pr_number,
                'action' => $request->action
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process request: ' . $e->getMessage()
            ], 500);
        }
    }
}