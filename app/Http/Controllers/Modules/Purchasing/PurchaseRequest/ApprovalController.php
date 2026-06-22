<?php

namespace App\Http\Controllers\Modules\Purchasing\PurchaseRequest;

use App\Http\Controllers\Controller;
use App\Models\Modules\Purchasing\PurchaseRequest\PrApproval;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Core\QrCodeService;
use App\Services\Modules\Purchasing\PurchaseRequest\ApprovalListService;
use App\Services\Modules\Purchasing\PurchaseRequest\ApprovalQrCodeBuilder;
use App\Services\Modules\Purchasing\PurchaseRequest\ApprovalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    public function __construct(
        protected ApprovalWorkflowService $workflowService,
        protected ApprovalListService $approvalListService,
        protected ApprovalQrCodeBuilder $qrCodeBuilder,
    ) {}

    /**
     * Show approval page for a specific approval
     */
    /**
     * Show approval page for a specific approval
     */
    public function show($approvalId)
    {
        $approval = PrApproval::findOrFail($approvalId);
        $user = Auth::user();

        // Check if current user is the approver
        if ($approval->approver_id !== $user->id) {
            abort(403, 'You are not authorized to view this approval.');
        }

        $purchaseRequest = $approval->purchaseRequest;

        // Load relationships needed for Show page
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

        // Logic for permission
        $currentApproval = $purchaseRequest->currentApproval();
        $isCurrentApprover = $currentApproval && $currentApproval->id === $approval->id;
        $canApprove = $isCurrentApprover && $purchaseRequest->status === 'in_approval' && $approval->status === 'pending';

        $authorization = [
            'approve' => $canApprove,
            'reject' => $canApprove,
            'view' => true,
            'downloadPdf' => true,
            'edit' => false,
            'delete' => false,
            'void' => false,
            'resubmit' => false,
            'resendApprovalEmail' => false,
            'markOfflineApproved' => false,
        ];

        return \Inertia\Inertia::render('Purchasing/PurchaseRequest/Show', [
            'purchaseRequest' => array_merge(
                $purchaseRequest->toArray(),
                [
                    'approval_progress' => $purchaseRequest->getApprovalProgress(),
                    'can' => $authorization,
                ]
            ),
            'can' => $authorization,
        ]);
    }

    /**
     * Process approval action
     */
    public function process(Request $request, $approvalId)
    {
        $request->validate([
            'action' => 'required|in:approve,reject,approved,rejected',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Normalize action to past tense for workflow service
        $action = $request->action;
        if ($action === 'approve') {
            $action = 'approved';
        } elseif ($action === 'reject') {
            $action = 'rejected';
        }

        // Optimized: Only load minimal data needed for processing
        $approval = PrApproval::with([
            'purchaseRequest:id,pr_number,status,user_id,department_id,business_unit_id',
        ])->findOrFail($approvalId);

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
                $action,
                $request->notes
            );

            $message = $action === 'approved'
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

        return view('purchasing.purchase-requests.public', compact('purchaseRequest', 'verificationData'));
    }

    /**
     * List pending approvals for current user (Inertia)
     * Combined PR and ST approvals
     */
    public function index(Request $request)
    {
        $data = $this->approvalListService->buildIndexData(
            (int) Auth::id(),
            session('current_business_unit_id'),
        );

        return inertia('Purchasing/Approvals', [
            'pendingApprovals' => $data['pendingApprovals'],
            'recentApprovals' => $data['recentApprovals'],
            'stats' => $data['stats'],
            'can' => [
                'processApprovals' => true,
            ],
        ]);
    }

    /**
     * Show public approval page (no authentication required)
     * Accessed via signed URL from email notification
     */
    public function showPublicApproval(PrApproval $approval, Request $request)
    {
        // Signed URL validation is automatic via 'signed' middleware

        // Load relationships
        $approval->load([
            'purchaseRequest.user',
            'purchaseRequest.department',
            'purchaseRequest.businessUnit',
            'purchaseRequest.items',
            'purchaseRequest.approvals.approver.primaryDepartment',
            'approver.primaryDepartment',
        ]);

        // Validate approval status - must be pending
        if ($approval->status !== 'pending') {
            return view('purchasing.approvals.purchase-request.public-error', [
                'title' => 'Approval Already Processed',
                'message' => 'This approval has already been '.$approval->status.'.',
                'icon' => 'fa-check-circle',
                'color' => $approval->status === 'approved' ? 'green' : 'red',
            ]);
        }

        // Validate PR status - must be in_approval
        if ($approval->purchaseRequest->status !== 'in_approval') {
            return view('purchasing.approvals.purchase-request.public-error', [
                'title' => 'Request No Longer Active',
                'message' => 'This purchase request is no longer awaiting approval (Status: '.$approval->purchaseRequest->status.').',
                'icon' => 'fa-exclamation-triangle',
                'color' => 'yellow',
            ]);
        }

        // Validate this is the current approval step
        $currentApproval = $approval->purchaseRequest->currentApproval();
        if (! $currentApproval || $currentApproval->id !== $approval->id) {
            return view('purchasing.approvals.purchase-request.public-error', [
                'title' => 'Not Current Approval Step',
                'message' => 'This approval is not currently active. Another approver may need to act first.',
                'icon' => 'fa-clock',
                'color' => 'blue',
            ]);
        }

        // Generate QR codes for display
        $qrCodes = $this->qrCodeBuilder->buildForPublicApproval($approval->purchaseRequest);

        return view('purchasing.approvals.purchase-request.public-approval', compact('approval', 'qrCodes'));
    }

    /**
     * Process public approval decision (no authentication required)
     * Accessed via signed URL from email notification
     */
    public function processPublicApproval(PrApproval $approval, Request $request)
    {
        // Signed URL validation is automatic via 'signed' middleware

        // Validate input
        $validated = $request->validate([
            'action' => 'required|in:approved,rejected',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Additional validation: notes required for rejection
        if ($validated['action'] === 'rejected' && empty($validated['notes'])) {
            return redirect()->back()
                ->withErrors(['notes' => 'Notes are required when rejecting a request.'])
                ->withInput();
        }

        // Load relationships
        $approval->load('purchaseRequest');

        // Re-validate approval status
        if ($approval->status !== 'pending') {
            return view('purchasing.approvals.purchase-request.public-error', [
                'title' => 'Approval Already Processed',
                'message' => 'This approval has already been '.$approval->status.'.',
                'icon' => 'fa-check-circle',
                'color' => $approval->status === 'approved' ? 'green' : 'red',
            ]);
        }

        // Re-validate PR status
        if ($approval->purchaseRequest->status !== 'in_approval') {
            return view('purchasing.approvals.purchase-request.public-error', [
                'title' => 'Request No Longer Active',
                'message' => 'This purchase request is no longer awaiting approval.',
                'icon' => 'fa-exclamation-triangle',
                'color' => 'yellow',
            ]);
        }

        // Re-validate current step
        $currentApproval = $approval->purchaseRequest->currentApproval();
        if (! $currentApproval || $currentApproval->id !== $approval->id) {
            return view('purchasing.approvals.purchase-request.public-error', [
                'title' => 'Not Current Approval Step',
                'message' => 'This approval is not currently active.',
                'icon' => 'fa-clock',
                'color' => 'blue',
            ]);
        }

        try {
            // Process the approval using the workflow service
            $this->workflowService->processApproval(
                $approval,
                $validated['action'],
                $validated['notes']
            );

            // Mark related notification as read (if user is logged in)
            if (Auth::check() && Auth::id() === $approval->approver_id) {
                Auth::user()->unreadNotifications()
                    ->where('data->approval_id', $approval->id)
                    ->update(['read_at' => now()]);
            }

            // Show success page
            return view('purchasing.approvals.purchase-request.public-success', [
                'approval' => $approval->fresh(['purchaseRequest', 'approver']),
                'action' => $validated['action'],
                'notes' => $validated['notes'],
            ]);

        } catch (\Exception $e) {
            \Log::error('Public approval processing failed', [
                'approval_id' => $approval->id,
                'action' => $validated['action'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return view('purchasing.approvals.purchase-request.public-error', [
                'title' => 'Processing Failed',
                'message' => 'An error occurred while processing your decision. Please try again or contact support.',
                'icon' => 'fa-exclamation-circle',
                'color' => 'red',
            ]);
        }
    }
}
