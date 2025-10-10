<?php

namespace App\Http\Controllers\Modules\PurchaseRequest;

use App\Http\Controllers\Controller;
use App\Models\Modules\PurchaseRequest\PrApproval;
use App\Models\Modules\PurchaseRequest\PurchaseRequest;
use App\Services\Core\QrCodeService;
use App\Services\Modules\PurchaseRequest\ApprovalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    protected $workflowService;

    public function __construct(ApprovalWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Show approval page for a specific approval
     */
    public function show($approvalId)
    {
        $approval = PrApproval::with([
            'purchaseRequest.user',
            'purchaseRequest.department',
            'purchaseRequest.businessUnit',
            'purchaseRequest.items',
            'purchaseRequest.approvals.approver',
            'approver',
        ])->findOrFail($approvalId);

        // Check if current user is the approver
        if ($approval->approver_id !== Auth::id()) {
            abort(403, 'You are not authorized to view this approval.');
        }

        // Check if this approval is the current pending one
        $currentApproval = $approval->purchaseRequest->currentApproval();
        $canApprove = $currentApproval && $currentApproval->id === $approval->id;

        return view('approvals.show', compact('approval', 'canApprove'));
    }

    /**
     * Process approval action
     */
    public function process(Request $request, $approvalId)
    {
        $request->validate([
            'action' => 'required|in:approved,rejected',
            'notes' => 'nullable|string|max:1000',
        ]);

        $approval = PrApproval::with('purchaseRequest')->findOrFail($approvalId);

        // Check if current user is the approver
        if ($approval->approver_id !== Auth::id()) {
            abort(403, 'You are not authorized to process this approval.');
        }

        // Check if approval is still pending
        if ($approval->status !== 'pending') {
            return redirect()->back()->with('error', 'This approval has already been processed.');
        }

        // Check if this is the current approval step
        $currentApproval = $approval->purchaseRequest->currentApproval();
        if (! $currentApproval || $currentApproval->id !== $approval->id) {
            return redirect()->back()->with('error', 'This approval is not currently active.');
        }

        try {
            $this->workflowService->processApproval(
                $approval,
                $request->action,
                $request->notes
            );

            $message = $request->action === 'approved'
                ? 'Purchase request has been approved successfully.'
                : 'Purchase request has been rejected.';

            return redirect()->route('approvals.show', $approval->id)
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to process approval: '.$e->getMessage());
        }
    }

    /**
     * Generate QR code for approved purchase request
     */
    public function generateQrCode($approvalId)
    {
        $approval = PrApproval::with('purchaseRequest')->findOrFail($approvalId);

        // Check if approval is approved
        if ($approval->status !== 'approved') {
            abort(404, 'QR code is only available for approved requests.');
        }

        // Use QrCodeService for consistent QR code generation
        $qrCodeService = new QrCodeService;
        $qrCodeSvg = $qrCodeService->generateApproverQrCode($approval);

        return response($qrCodeSvg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Show public view of purchase request via QR code
     */
    public function publicView($prId, Request $request)
    {
        $token = $request->get('token');
        $approverId = $request->get('approver');
        $requestorId = $request->get('requestor');

        if (! $token) {
            abort(404, 'Invalid verification link.');
        }

        $purchaseRequest = PurchaseRequest::with([
            'user',
            'department',
            'businessUnit',
            'items',
            'approvals.approver',
        ])->findOrFail($prId);

        $qrCodeService = new QrCodeService;
        $verificationData = [];

        // Check if this is requestor verification
        if ($requestorId) {
            if ($requestorId != $purchaseRequest->user_id) {
                abort(404, 'Invalid requestor verification.');
            }

            if (! $qrCodeService->verifyRequestorToken($purchaseRequest, $token)) {
                abort(403, 'Invalid verification token.');
            }

            $verificationData = [
                'type' => 'requestor',
                'verified_by' => $purchaseRequest->user,
                'verified_at' => $purchaseRequest->submitted_at,
                'role' => 'Purchase Request Creator',
            ];
        }
        // Check if this is approver verification
        elseif ($approverId) {
            $approval = $purchaseRequest->approvals()
                ->where('approver_id', $approverId)
                ->where('status', 'approved')
                ->first();

            if (! $approval) {
                abort(404, 'Approval not found or not approved.');
            }

            if (! $qrCodeService->verifyApprovalToken($approval, $token)) {
                abort(403, 'Invalid verification token.');
            }

            $verificationData = [
                'type' => 'approval',
                'verified_by' => $approval->approver,
                'verified_at' => $approval->responded_at,
                'role' => ucfirst($approval->approval_type),
                'approval' => $approval,
            ];
        } else {
            abort(404, 'Invalid verification parameters.');
        }

        return view('purchase-requests.public', compact('purchaseRequest', 'verificationData'));
    }

    /**
     * List pending approvals for current user
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'pending');

        $pendingApprovals = $this->workflowService->getPendingApprovalsForUser(Auth::user())->paginate(10);
        $approvalHistory = $this->workflowService->getApprovalHistoryForUser(Auth::user())->paginate(10);
        $approvalStats = $this->workflowService->getApprovalStatistics(Auth::user());

        return view('approvals.index', compact('pendingApprovals', 'approvalHistory', 'approvalStats', 'tab'));
    }
}
