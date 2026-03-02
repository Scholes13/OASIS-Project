<?php

namespace App\Http\Controllers\Modules\Purchasing\PurchaseRequest;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use App\Models\Modules\Purchasing\PurchaseRequest\PrApproval;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Models\Modules\Purchasing\StockRequest\StockApproval;
use App\Services\Core\QrCodeService;
use App\Services\Modules\Purchasing\PurchaseRequest\ApprovalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $userId = Auth::id();
        $businessUnitId = session('current_business_unit_id');

        // For c_level/executive users, expand filter to include all descendant BUs
        $filterBusinessUnitIds = $this->getFilterBusinessUnitIds($businessUnitId);

        // Get PR pending count (must match list: parent in_approval + business unit)
        $prPendingQuery = PrApproval::where('approver_id', $userId)
            ->where('status', 'pending')
            ->whereHas('purchaseRequest', function ($q) {
                $q->where('status', 'in_approval');
            });

        if (! empty($filterBusinessUnitIds)) {
            $prPendingQuery->whereHas('purchaseRequest', function ($q) use ($filterBusinessUnitIds) {
                $q->whereIn('business_unit_id', $filterBusinessUnitIds);
            });
        }

        $prPendingCount = $prPendingQuery->count();

        // Get PR approved/rejected counts (historical, filtered by business unit only)
        $prHistoryQuery = PrApproval::where('approver_id', $userId)
            ->whereIn('status', ['approved', 'rejected']);

        if (! empty($filterBusinessUnitIds)) {
            $prHistoryQuery->whereHas('purchaseRequest', function ($q) use ($filterBusinessUnitIds) {
                $q->whereIn('business_unit_id', $filterBusinessUnitIds);
            });
        }

        $prHistoryStats = $prHistoryQuery->select([
            DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
            DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count"),
        ])->first();

        // Get ST pending count (must match list: parent in_approval + business unit)
        $stPendingQuery = StockApproval::where('approver_id', $userId)
            ->where('status', 'pending')
            ->whereHas('stockRequest', function ($q) {
                $q->where('status', 'in_approval');
            });

        if (! empty($filterBusinessUnitIds)) {
            $stPendingQuery->whereHas('stockRequest', function ($q) use ($filterBusinessUnitIds) {
                $q->whereIn('business_unit_id', $filterBusinessUnitIds);
            });
        }

        $stPendingCount = $stPendingQuery->count();

        // Get ST approved/rejected counts (historical, filtered by business unit only)
        $stHistoryQuery = StockApproval::where('approver_id', $userId)
            ->whereIn('status', ['approved', 'rejected']);

        if (! empty($filterBusinessUnitIds)) {
            $stHistoryQuery->whereHas('stockRequest', function ($q) use ($filterBusinessUnitIds) {
                $q->whereIn('business_unit_id', $filterBusinessUnitIds);
            });
        }

        $stHistoryStats = $stHistoryQuery->select([
            DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
            DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count"),
        ])->first();

        // Build query for pending PR approvals
        $pendingPrQuery = PrApproval::with([
            'purchaseRequest' => function ($query) {
                $query->select('id', 'pr_number', 'user_id', 'department_id', 'business_unit_id',
                    'total_amount', 'currency', 'status', 'used_for', 'created_at', 'updated_at');
            },
            'purchaseRequest.user:id,name,email',
            'purchaseRequest.department:id,name,code',
            'purchaseRequest.businessUnit:id,name,code',
        ])
            ->where('approver_id', $userId)
            ->where('status', 'pending')
            ->whereHas('purchaseRequest', function ($q) {
                $q->where('status', 'in_approval');
            });

        // Build query for pending ST approvals
        $pendingStQuery = StockApproval::with([
            'stockRequest' => function ($query) {
                $query->select('id', 'st_number', 'user_id', 'department_id', 'business_unit_id',
                    'purpose', 'status', 'created_at', 'updated_at');
            },
            'stockRequest.user:id,name,email',
            'stockRequest.department:id,name,code',
            'stockRequest.businessUnit:id,name,code',
            'stockRequest.items:id,stock_request_id,total',
        ])
            ->where('approver_id', $userId)
            ->where('status', 'pending')
            ->whereHas('stockRequest', function ($q) {
                $q->where('status', 'in_approval');
            });

        // Filter by business unit if set
        if (! empty($filterBusinessUnitIds)) {
            $pendingPrQuery->whereHas('purchaseRequest', function ($q) use ($filterBusinessUnitIds) {
                $q->whereIn('business_unit_id', $filterBusinessUnitIds);
            });
            $pendingStQuery->whereHas('stockRequest', function ($q) use ($filterBusinessUnitIds) {
                $q->whereIn('business_unit_id', $filterBusinessUnitIds);
            });
        }

        // Get pending approvals
        $pendingPrApprovals = $pendingPrQuery->orderBy('created_at', 'asc')->get();
        $pendingStApprovals = $pendingStQuery->orderBy('created_at', 'asc')->get();

        // Transform and merge pending approvals
        $pendingApprovals = collect();

        foreach ($pendingPrApprovals as $approval) {
            $pendingApprovals->push([
                'id' => $approval->id,
                'type' => 'PR',
                'request_number' => $approval->purchaseRequest->pr_number,
                'request_id' => $approval->purchaseRequest->id,
                'used_for' => $approval->purchaseRequest->used_for,
                'total_amount' => $approval->purchaseRequest->total_amount,
                'currency' => $approval->purchaseRequest->currency ?? 'IDR',
                'user' => $approval->purchaseRequest->user ?? ['id' => 0, 'name' => 'Unknown User', 'email' => ''],
                'department' => $approval->purchaseRequest->department ?? ['id' => 0, 'name' => 'Unknown Department', 'code' => '-'],
                'business_unit' => $approval->purchaseRequest->businessUnit ?? ['id' => 0, 'name' => 'Unknown BU', 'code' => '-'],
                'step_order' => $approval->step_order,
                'approval_type' => $approval->approval_type ?? $approval->task_type,
                'status' => $approval->status,
                'waiting_since' => $approval->created_at->toISOString(),
                'created_at' => $approval->created_at,
                'can' => [
                    'approve' => $this->canProcessApproval($approval),
                    'reject' => $this->canProcessApproval($approval),
                ],
            ]);
        }

        foreach ($pendingStApprovals as $approval) {
            $totalAmount = $approval->stockRequest->items->sum('total');
            $pendingApprovals->push([
                'id' => $approval->id,
                'type' => 'ST',
                'request_number' => $approval->stockRequest->st_number,
                'request_id' => $approval->stockRequest->id,
                'used_for' => $approval->stockRequest->purpose,
                'total_amount' => $totalAmount,
                'currency' => 'IDR',
                'user' => $approval->stockRequest->user ?? ['id' => 0, 'name' => 'Unknown User', 'email' => ''],
                'department' => $approval->stockRequest->department ?? ['id' => 0, 'name' => 'Unknown Department', 'code' => '-'],
                'business_unit' => $approval->stockRequest->businessUnit ?? ['id' => 0, 'name' => 'Unknown BU', 'code' => '-'],
                'step_order' => $approval->step_order,
                'approval_type' => $approval->approval_type ?? $approval->task_type,
                'status' => $approval->status,
                'waiting_since' => $approval->created_at->toISOString(),
                'created_at' => $approval->created_at,
                'can' => [
                    'approve' => $this->canProcessStockApproval($approval),
                    'reject' => $this->canProcessStockApproval($approval),
                ],
            ]);
        }

        // Sort by created_at (oldest first)
        $pendingApprovals = $pendingApprovals->sortBy('created_at')->values();

        // Get recent PR approvals
        $recentPrQuery = PrApproval::with([
            'purchaseRequest' => function ($query) {
                $query->select('id', 'pr_number', 'user_id', 'department_id', 'business_unit_id',
                    'total_amount', 'currency', 'status', 'used_for', 'created_at', 'updated_at');
            },
            'purchaseRequest.user:id,name,email',
            'purchaseRequest.department:id,name,code',
            'purchaseRequest.businessUnit:id,name,code',
        ])
            ->where('approver_id', $userId)
            ->whereIn('status', ['approved', 'rejected']);

        // Get recent ST approvals
        $recentStQuery = StockApproval::with([
            'stockRequest' => function ($query) {
                $query->select('id', 'st_number', 'user_id', 'department_id', 'business_unit_id',
                    'purpose', 'status', 'created_at', 'updated_at');
            },
            'stockRequest.user:id,name,email',
            'stockRequest.department:id,name,code',
            'stockRequest.businessUnit:id,name,code',
            'stockRequest.items:id,stock_request_id,total',
        ])
            ->where('approver_id', $userId)
            ->whereIn('status', ['approved', 'rejected']);

        // Filter by business unit if set
        if (! empty($filterBusinessUnitIds)) {
            $recentPrQuery->whereHas('purchaseRequest', function ($q) use ($filterBusinessUnitIds) {
                $q->whereIn('business_unit_id', $filterBusinessUnitIds);
            });
            $recentStQuery->whereHas('stockRequest', function ($q) use ($filterBusinessUnitIds) {
                $q->whereIn('business_unit_id', $filterBusinessUnitIds);
            });
        }

        $recentPrApprovals = $recentPrQuery->orderBy('responded_at', 'desc')->limit(10)->get();
        $recentStApprovals = $recentStQuery->orderBy('responded_at', 'desc')->limit(10)->get();

        // Transform and merge recent approvals
        $recentApprovals = collect();

        foreach ($recentPrApprovals as $approval) {
            $recentApprovals->push([
                'id' => $approval->id,
                'type' => 'PR',
                'request_number' => $approval->purchaseRequest->pr_number,
                'request_id' => $approval->purchaseRequest->id,
                'used_for' => $approval->purchaseRequest->used_for,
                'total_amount' => $approval->purchaseRequest->total_amount,
                'currency' => $approval->purchaseRequest->currency ?? 'IDR',
                'user' => $approval->purchaseRequest->user ?? ['id' => 0, 'name' => 'Unknown User', 'email' => ''],
                'department' => $approval->purchaseRequest->department ?? ['id' => 0, 'name' => 'Unknown Department', 'code' => '-'],
                'business_unit' => $approval->purchaseRequest->businessUnit ?? ['id' => 0, 'name' => 'Unknown BU', 'code' => '-'],
                'step_order' => $approval->step_order,
                'approval_type' => $approval->approval_type ?? $approval->task_type,
                'status' => $approval->status,
                'responded_at' => $approval->responded_at,
                'waiting_since' => $approval->created_at->toISOString(),
                'can' => ['approve' => false, 'reject' => false],
            ]);
        }

        foreach ($recentStApprovals as $approval) {
            $totalAmount = $approval->stockRequest->items->sum('total');
            $recentApprovals->push([
                'id' => $approval->id,
                'type' => 'ST',
                'request_number' => $approval->stockRequest->st_number,
                'request_id' => $approval->stockRequest->id,
                'used_for' => $approval->stockRequest->purpose,
                'total_amount' => $totalAmount,
                'currency' => 'IDR',
                'user' => $approval->stockRequest->user ?? ['id' => 0, 'name' => 'Unknown User', 'email' => ''],
                'department' => $approval->stockRequest->department ?? ['id' => 0, 'name' => 'Unknown Department', 'code' => '-'],
                'business_unit' => $approval->stockRequest->businessUnit ?? ['id' => 0, 'name' => 'Unknown BU', 'code' => '-'],
                'step_order' => $approval->step_order,
                'approval_type' => $approval->approval_type ?? $approval->task_type,
                'status' => $approval->status,
                'responded_at' => $approval->responded_at,
                'waiting_since' => $approval->created_at->toISOString(),
                'can' => ['approve' => false, 'reject' => false],
            ]);
        }

        // Sort by responded_at (most recent first) and take 10
        $recentApprovals = $recentApprovals->sortByDesc('responded_at')->take(10)->values();

        // Combined stats
        $approvedCount = (int) (($prHistoryStats->approved_count ?? 0) + ($stHistoryStats->approved_count ?? 0));
        $rejectedCount = (int) (($prHistoryStats->rejected_count ?? 0) + ($stHistoryStats->rejected_count ?? 0));
        $pendingCount = $prPendingCount + $stPendingCount;

        $stats = [
            'pending' => $pendingCount,
            'approved' => $approvedCount,
            'rejected' => $rejectedCount,
            'total' => $pendingCount + $approvedCount + $rejectedCount,
            'pr_pending' => $prPendingCount,
            'st_pending' => $stPendingCount,
        ];

        return inertia('Purchasing/Approvals', [
            'pendingApprovals' => $pendingApprovals,
            'recentApprovals' => $recentApprovals,
            'stats' => $stats,
            'can' => [
                'processApprovals' => true,
            ],
        ]);
    }

    /**
     * Get business unit IDs to filter approvals by.
     *
     * For c_level/executive users viewing from a parent BU, this returns
     * the parent BU + all descendant BU IDs so they can see approvals
     * from all child business units in one place.
     *
     * @return int[]
     */
    protected function getFilterBusinessUnitIds(?int $businessUnitId): array
    {
        if (! $businessUnitId) {
            return [];
        }

        $user = Auth::user();

        if ($user->hasTopManagementAccess()) {
            $bu = BusinessUnit::find($businessUnitId);
            if ($bu) {
                return $bu->getAccessibleBusinessUnits();
            }
        }

        return [$businessUnitId];
    }

    /**
     * Check if current user can process this stock approval
     */
    protected function canProcessStockApproval(StockApproval $approval): bool
    {
        if ($approval->approver_id !== Auth::id()) {
            return false;
        }
        if ($approval->status !== 'pending') {
            return false;
        }
        if ($approval->stockRequest->status !== 'in_approval') {
            return false;
        }
        $currentApproval = $approval->stockRequest->currentApproval();
        if (! $currentApproval || $currentApproval->id !== $approval->id) {
            return false;
        }

        return true;
    }

    /**
     * Transform PurchaseRequest model to match frontend interface
     */
    protected function transformPurchaseRequest($pr): array
    {
        return [
            'id' => $pr->id,
            'pr_number' => $pr->pr_number,
            'business_unit_id' => $pr->business_unit_id,
            'department_id' => $pr->department_id,
            'user_id' => $pr->user_id,
            'used_for' => $pr->used_for,
            'date_of_request' => $pr->date_of_request?->toISOString(),
            'status' => $pr->status,
            'total_amount' => $pr->total_amount,
            'currency' => $pr->currency,
            'created_at' => $pr->created_at->toISOString(),
            'updated_at' => $pr->updated_at->toISOString(),
            'department' => [
                'id' => $pr->department->id,
                'name' => $pr->department->name,
                'code' => $pr->department->code,
            ],
            'user' => [
                'id' => $pr->user->id,
                'name' => $pr->user->name,
                'email' => $pr->user->email,
            ],
            'business_unit' => $pr->businessUnit ? [
                'id' => $pr->businessUnit->id,
                'name' => $pr->businessUnit->name,
                'code' => $pr->businessUnit->code,
            ] : null,
            'current_approval_step' => $pr->approvals->where('status', 'approved')->count() + 1,
            'total_approval_steps' => $pr->approvals->count(),
            'can' => [
                'view' => true,
                'edit' => false,
                'delete' => false,
                'void' => false,
                'resubmit' => false,
            ],
        ];
    }

    /**
     * Check if current user can process this approval
     */
    protected function canProcessApproval(PrApproval $approval): bool
    {
        // Must be the approver
        if ($approval->approver_id !== Auth::id()) {
            return false;
        }

        // Must be pending
        if ($approval->status !== 'pending') {
            return false;
        }

        // PR must be in approval
        if ($approval->purchaseRequest->status !== 'in_approval') {
            return false;
        }

        // Must be the current approval step
        $currentApproval = $approval->purchaseRequest->currentApproval();
        if (! $currentApproval || $currentApproval->id !== $approval->id) {
            return false;
        }

        return true;
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
        $qrCodeService = new QrCodeService;
        $qrCodes = $this->generateQrCodesForPublicApproval($approval->purchaseRequest, $qrCodeService);

        return view('purchasing.approvals.purchase-request.public-approval', compact('approval', 'qrCodes'));
    }

    /**
     * Generate QR codes for public approval page
     */
    protected function generateQrCodesForPublicApproval(PurchaseRequest $purchaseRequest, QrCodeService $qrCodeService): array
    {
        $qrCodes = [];

        // Generate QR code for requestor (creator)
        if ($purchaseRequest->submitted_at) {
            $qrCodes['requestor'] = $qrCodeService->generateRequestorQrCodeDataUrl($purchaseRequest);
        }

        // Generate QR codes for all approvals that have been approved
        $qrCodes['approvals'] = [];
        foreach ($purchaseRequest->approvals->where('status', 'approved') as $approval) {
            $qrCodes['approvals'][$approval->id] = $qrCodeService->generateApproverQrCodeDataUrl($approval);
        }

        return $qrCodes;
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
