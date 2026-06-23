<?php

namespace App\Http\Controllers\Modules\Purchasing\StockRequest;

use App\Http\Controllers\Controller;
use App\Models\Modules\Purchasing\StockRequest\StockApproval;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Core\EmailNotificationService;
use App\Services\Core\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class StockApprovalController extends Controller
{
    /**
     * Show approval page for a specific approval
     */
    public function show(StockApproval $approval): Response
    {
        $approval->load([
            'stockRequest' => function ($query) {
                $query->select('id', 'st_number', 'user_id', 'department_id', 'business_unit_id',
                    'purpose', 'status', 'date_of_request', 'expected_date',
                    'created_at', 'submitted_at', 'approved_at');
            },
            'stockRequest.user:id,name,email',
            'stockRequest.department:id,name,code',
            'stockRequest.businessUnit:id,name,code,logo',
            'stockRequest.items' => function ($query) {
                $query->select('id', 'stock_request_id', 'item_name', 'specifications',
                    'quantity', 'unit', 'price', 'total', 'item_code');
            },
            'stockRequest.approvals' => function ($query) {
                $query->select('id', 'stock_request_id', 'approver_id', 'step_order',
                    'approval_type', 'task_type', 'status', 'notes', 'responded_at')
                    ->orderBy('step_order');
            },
            'stockRequest.approvals.approver:id,name,email',
            'approver:id,name,email',
        ]);

        // Check if current user is the approver
        if ($approval->approver_id !== Auth::id()) {
            abort(403, 'You are not authorized to view this approval.');
        }

        if ($approval->stockRequest->business_unit_id !== (int) session('current_business_unit_id')) {
            abort(403, 'You do not have access to this stock request approval.');
        }

        // Check if this approval is the current pending one
        $currentApproval = $approval->stockRequest->currentApproval();
        $canApprove = $currentApproval && $currentApproval->id === $approval->id;

        $authorization = [
            'approve' => $canApprove,
            'reject' => $canApprove,
            'edit' => false,
            'delete' => false,
            'void' => false,
            'resubmit' => false,
            'resendApprovalEmail' => false,
            'downloadPdf' => true,
            'markOfflineApproved' => false,
            'offlineApprovalDocument' => false,
        ];

        return Inertia::render('Purchasing/StockRequest/Show', [
            'stockRequest' => array_merge(
                $approval->stockRequest->toArray(),
                [
                    'approval_progress' => $approval->stockRequest->getApprovalProgress(),
                    'can' => $authorization,
                ]
            ),
            'can' => $authorization,
            'approvalContext' => [
                'approvalId' => $approval->id,
                'canApprove' => $canApprove,
                'approvalStatus' => $approval->status,
            ],
        ]);
    }

    /**
     * Process approval action
     */
    public function process(Request $request, StockApproval $approval)
    {
        $request->validate([
            'action' => 'required|in:approve,reject,approved,rejected',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Normalize action to past tense
        $action = $request->action;
        if ($action === 'approve') {
            $action = 'approved';
        } elseif ($action === 'reject') {
            $action = 'rejected';
        }

        $approval->load([
            'stockRequest:id,st_number,status,user_id,business_unit_id',
        ]);

        // Check if current user is the approver
        if ($approval->approver_id !== Auth::id()) {
            abort(403, 'You are not authorized to process this approval.');
        }

        if ($approval->stockRequest->business_unit_id !== (int) session('current_business_unit_id')) {
            abort(403, 'You do not have access to this stock request approval.');
        }

        // Check if approval is still pending
        if ($approval->status !== 'pending') {
            return redirect()->back()->with('error', 'This approval has already been processed.');
        }

        // Check if this is the current approval step
        $currentApproval = $approval->stockRequest->currentApproval();
        if (! $currentApproval || $currentApproval->id !== $approval->id) {
            return redirect()->back()->with('error', 'This approval is not currently active.');
        }

        try {
            $this->processStockApproval($approval, $action, $request->notes);

            $message = $action === 'approved'
                ? 'Stock request has been approved successfully.'
                : 'Stock request has been rejected.';

            return redirect()->route('stock-approvals.show', $approval->id)
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to process approval: '.$e->getMessage());
        }
    }

    /**
     * Process stock approval decision
     */
    protected function processStockApproval(StockApproval $approval, string $action, ?string $notes): void
    {
        // Check if the assigned approver is still active
        $approver = \App\Models\Core\User::find($approval->approver_id);
        if (! $approver || ! ($approver->is_active ?? true)) {
            throw new \Exception('The assigned approver is no longer active. Please contact an administrator to reassign the approval.');
        }

        // Update approval status
        $approval->update([
            'status' => $action,
            'notes' => $notes,
            'responded_at' => now(),
        ]);

        $stockRequest = $approval->stockRequest;

        if ($action === 'rejected') {
            // Reject the entire stock request
            $stockRequest->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejection_notes' => $notes,
            ]);

            app(EmailNotificationService::class)
                ->sendStApprovalRejected($approval->fresh(['stockRequest', 'approver']));
        } elseif ($action === 'approved') {
            // Check if all approvals are complete
            $pendingApprovals = $stockRequest->approvals()->where('status', 'pending')->count();

            if ($pendingApprovals === 0) {
                // All approvals complete
                $stockRequest->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);

                app(EmailNotificationService::class)
                    ->sendStApprovalApproved($stockRequest->fresh());
            } else {
                // Assign next approval step
                $nextApproval = $stockRequest->approvals()
                    ->where('status', 'pending')
                    ->orderBy('step_order')
                    ->first();

                if ($nextApproval) {
                    $nextApproval->update([
                        'assigned_at' => now(),
                    ]);

                    // Send email notification to next approver
                    $this->sendNextApproverNotification($nextApproval);
                }
            }
        }
    }

    /**
     * Send email notification to the next approver
     */
    protected function sendNextApproverNotification(StockApproval $approval): void
    {
        try {
            if ($approval->approver && ! $approval->email_sent) {
                app(EmailNotificationService::class)
                    ->sendStApprovalRequested($approval);

                Log::info('Stock request approval notification sent to next approver', [
                    'st_number' => $approval->stockRequest->st_number,
                    'approver_id' => $approval->approver_id,
                    'approver_name' => $approval->approver->name,
                    'step_order' => $approval->step_order,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send stock request approval notification', [
                'approval_id' => $approval->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate QR code for approved stock request
     */
    public function generateQrCode($approvalId)
    {
        $approval = StockApproval::with('stockRequest')->findOrFail($approvalId);

        // Check if approval is approved
        if ($approval->status !== 'approved') {
            abort(404, 'QR code is only available for approved requests.');
        }

        // Use QrCodeService for consistent QR code generation
        $qrCodeService = new QrCodeService;
        $qrCodeSvg = $qrCodeService->generateStockApprovalQrCode($approval);

        return response($qrCodeSvg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Show public view of stock request via QR code
     */
    public function publicView($srId, Request $request)
    {
        $token = $request->get('token');
        $approverId = $request->get('approver');
        $requestorId = $request->get('requestor');
        $gaReviewerId = $request->get('ga_reviewer');

        if (! $token) {
            abort(404, 'Invalid verification link.');
        }

        $stockRequest = StockRequest::with([
            'user',
            'department',
            'businessUnit',
            'items',
            'approvals.approver',
        ])->findOrFail($srId);

        $qrCodeService = new QrCodeService;
        $verificationData = [];

        // Check if this is requestor verification
        if ($requestorId) {
            if ($requestorId != $stockRequest->user_id) {
                abort(404, 'Invalid requestor verification.');
            }

            if (! $qrCodeService->verifyStockRequestorToken($stockRequest, $token)) {
                abort(403, 'Invalid verification token.');
            }

            $verificationData = [
                'type' => 'requestor',
                'verified_by' => $stockRequest->user,
                'verified_at' => $stockRequest->submitted_at,
                'role' => 'Stock Request Creator',
            ];
        } elseif ($gaReviewerId) {
            if ($gaReviewerId != $stockRequest->ga_reviewed_by || ! $stockRequest->ga_reviewed_at) {
                abort(404, 'GA review verification not found.');
            }

            if (! $qrCodeService->verifyStockGaReviewerToken($stockRequest, $token)) {
                abort(403, 'Invalid verification token.');
            }

            $verificationData = [
                'type' => 'ga_review',
                'verified_by' => \App\Models\Core\User::findOrFail($stockRequest->ga_reviewed_by),
                'verified_at' => $stockRequest->ga_reviewed_at,
                'role' => 'GA Stock Reviewer',
            ];
        }
        // Check if this is approver verification
        elseif ($approverId) {
            $approval = $stockRequest->approvals()
                ->where('approver_id', $approverId)
                ->where('status', 'approved')
                ->first();

            if (! $approval) {
                abort(404, 'Approval not found or not approved.');
            }

            if (! $qrCodeService->verifyStockApprovalToken($approval, $token)) {
                abort(403, 'Invalid verification token.');
            }

            $verificationData = [
                'type' => 'approval',
                'verified_by' => $approval->approver,
                'verified_at' => $approval->responded_at,
                'role' => ucfirst($approval->approval_type ?? $approval->task_type),
                'approval' => $approval,
            ];
        } else {
            abort(404, 'Invalid verification parameters.');
        }

        return view('purchasing.stock-requests.public', compact('stockRequest', 'verificationData'));
    }

    /**
     * List pending approvals for current user
     */
    public function index(Request $request)
    {
        return redirect()->route('approvals.index');
    }

    /**
     * Show public approval page (no authentication required)
     * Accessed via signed URL from email notification
     */
    public function showPublicApproval(StockApproval $approval, Request $request)
    {
        // Load relationships
        $approval->load([
            'stockRequest.user',
            'stockRequest.department',
            'stockRequest.businessUnit',
            'stockRequest.items',
            'stockRequest.approvals.approver.primaryDepartment',
            'approver.primaryDepartment',
        ]);

        // Validate approval status - must be pending
        if ($approval->status !== 'pending') {
            return view('purchasing.approvals.stock-request.public-error', [
                'title' => 'Approval Already Processed',
                'message' => 'This approval has already been '.$approval->status.'.',
                'icon' => 'fa-check-circle',
                'color' => $approval->status === 'approved' ? 'green' : 'red',
            ]);
        }

        // Validate SR status - must be in_approval
        if ($approval->stockRequest->status !== 'in_approval') {
            return view('purchasing.approvals.stock-request.public-error', [
                'title' => 'Request No Longer Active',
                'message' => 'This stock request is no longer awaiting approval (Status: '.$approval->stockRequest->status.').',
                'icon' => 'fa-exclamation-triangle',
                'color' => 'yellow',
            ]);
        }

        // Validate this is the current approval step
        $currentApproval = $approval->stockRequest->currentApproval();
        if (! $currentApproval || $currentApproval->id !== $approval->id) {
            return view('purchasing.approvals.stock-request.public-error', [
                'title' => 'Not Current Approval Step',
                'message' => 'This approval is not currently active. Another approver may need to act first.',
                'icon' => 'fa-clock',
                'color' => 'blue',
            ]);
        }

        // Generate QR codes for display
        $qrCodeService = new QrCodeService;
        $qrCodes = $this->generateQrCodesForPublicApproval($approval->stockRequest, $qrCodeService);

        return view('purchasing.approvals.stock-request.public-approval', compact('approval', 'qrCodes'));
    }

    /**
     * Generate QR codes for public approval page
     */
    protected function generateQrCodesForPublicApproval(StockRequest $stockRequest, QrCodeService $qrCodeService): array
    {
        $qrCodes = [];

        // Generate QR code for requestor (creator)
        if ($stockRequest->submitted_at) {
            $qrCodes['requestor'] = $qrCodeService->generateStockRequestorQrCodeDataUrl($stockRequest);
        }

        // Generate QR codes for all approvals that have been approved
        $qrCodes['approvals'] = [];
        foreach ($stockRequest->approvals->where('status', 'approved') as $approval) {
            $qrCodes['approvals'][$approval->id] = $qrCodeService->generateStockApprovalQrCodeDataUrl($approval);
        }

        return $qrCodes;
    }

    /**
     * Process public approval decision (no authentication required)
     */
    public function processPublicApproval(StockApproval $approval, Request $request)
    {
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
        $approval->load('stockRequest');

        // Re-validate approval status
        if ($approval->status !== 'pending') {
            return view('purchasing.approvals.stock-request.public-error', [
                'title' => 'Approval Already Processed',
                'message' => 'This approval has already been '.$approval->status.'.',
                'icon' => 'fa-check-circle',
                'color' => $approval->status === 'approved' ? 'green' : 'red',
            ]);
        }

        // Re-validate SR status
        if ($approval->stockRequest->status !== 'in_approval') {
            return view('purchasing.approvals.stock-request.public-error', [
                'title' => 'Request No Longer Active',
                'message' => 'This stock request is no longer awaiting approval.',
                'icon' => 'fa-exclamation-triangle',
                'color' => 'yellow',
            ]);
        }

        // Re-validate current step
        $currentApproval = $approval->stockRequest->currentApproval();
        if (! $currentApproval || $currentApproval->id !== $approval->id) {
            return view('purchasing.approvals.stock-request.public-error', [
                'title' => 'Not Current Approval Step',
                'message' => 'This approval is not currently active.',
                'icon' => 'fa-clock',
                'color' => 'blue',
            ]);
        }

        try {
            // Process the approval
            $this->processStockApproval($approval, $validated['action'], $validated['notes']);

            // Mark related notification as read (if user is logged in)
            if (Auth::check() && Auth::id() === $approval->approver_id) {
                Auth::user()->unreadNotifications()
                    ->where('data->approval_id', $approval->id)
                    ->update(['read_at' => now()]);
            }

            // Show success page
            return view('purchasing.approvals.stock-request.public-success', [
                'approval' => $approval->fresh(['stockRequest', 'approver']),
                'action' => $validated['action'],
                'notes' => $validated['notes'],
            ]);

        } catch (\Exception $e) {
            Log::error('Public stock approval processing failed', [
                'approval_id' => $approval->id,
                'action' => $validated['action'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return view('purchasing.approvals.stock-request.public-error', [
                'title' => 'Processing Failed',
                'message' => 'An error occurred while processing your decision. Please try again or contact support.',
                'icon' => 'fa-exclamation-circle',
                'color' => 'red',
            ]);
        }
    }
}
