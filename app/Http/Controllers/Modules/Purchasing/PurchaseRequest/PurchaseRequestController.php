<?php

namespace App\Http\Controllers\Modules\Purchasing\PurchaseRequest;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Modules\Purchasing\PurchaseRequest\ApprovalWorkflowService;
use App\Services\Modules\Purchasing\PurchaseRequest\PurchaseRequestDocumentService;
use App\Services\Modules\Purchasing\PurchaseRequest\PurchaseRequestQueryService;
use App\Services\Modules\Purchasing\PurchaseRequest\PurchaseRequestService;
use App\Services\Modules\Purchasing\PurchaseRequest\UniversalPRNumberingService;
use App\Services\Modules\Purchasing\Shared\RequestFormDataProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PurchaseRequestController extends Controller
{
    public function __construct(
        protected PurchaseRequestService $purchaseRequestService,
        protected ApprovalWorkflowService $approvalWorkflowService,
        protected UniversalPRNumberingService $numberingService,
        protected RequestFormDataProvider $formDataProvider,
        protected PurchaseRequestDocumentService $documentService,
        protected PurchaseRequestQueryService $queryService,
    ) {}

    /**
     * Display a listing of the user's purchase requests.
     * Requirements: 2.1, 12.1, 12.6
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $businessUnitId = (int) session('current_business_unit_id');

        $purchaseRequests = $this->queryService->paginateForUser($request, $user, $businessUnitId);

        $reservations = $this->queryService->getReservationsForUser(
            $user,
            $businessUnitId,
            $request->get('search', ''),
        );

        return Inertia::render('Purchasing/PurchaseRequest/Index', [
            'purchaseRequests' => $purchaseRequests,
            'reservations' => $reservations,
            'filters' => [
                'search' => $request->get('search', ''),
                'status' => $request->get('status', ''),
                'date_from' => $request->get('date_from', ''),
                'date_to' => $request->get('date_to', ''),
            ],
            'statuses' => $this->queryService->statusFilterOptions(),
            'can' => [
                'create' => true, // All authenticated users can create PRs
                'viewAll' => $user->can('view-all-purchase-requests') || $user->isSuperAdmin(),
                'export' => $user->can('export-purchase-requests') || $user->isSuperAdmin(),
            ],
        ]);
    }

    /**
     * Display all purchase requests in the current business unit.
     * All users registered to a business unit can view all PRs in that unit.
     * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
     */
    public function all(Request $request): Response
    {
        $user = Auth::user();
        $businessUnitId = (int) session('current_business_unit_id');

        // Verify user has access to this business unit
        $userBusinessUnitIds = $user->getAccessibleBusinessUnitIds();

        if (! $businessUnitId || ! in_array($businessUnitId, $userBusinessUnitIds)) {
            return redirect()->route('purchase-requests.index')
                ->with('error', 'You do not have access to this business unit.');
        }

        // For c_level/executive users, expand to include all descendant BUs
        $filterBusinessUnitIds = [$businessUnitId];
        if ($user->hasTopManagementAccess()) {
            $bu = BusinessUnit::find($businessUnitId);
            if ($bu) {
                $filterBusinessUnitIds = $bu->getAccessibleBusinessUnits();
            }
        }

        $purchaseRequests = $this->queryService->paginateForBusinessUnits(
            $request,
            $user,
            $filterBusinessUnitIds,
        );

        // Get departments for filter dropdown (from all accessible BUs)
        $departments = \App\Models\Core\Department::whereIn('business_unit_id', $filterBusinessUnitIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('Purchasing/PurchaseRequest/All', [
            'purchaseRequests' => $purchaseRequests,
            'filters' => [
                'search' => $request->get('search', ''),
                'status' => $request->get('status', ''),
                'date_from' => $request->get('date_from', ''),
                'date_to' => $request->get('date_to', ''),
                'department_id' => $request->get('department_id', ''),
            ],
            'departments' => $departments,
            'can' => [
                'export' => $user->can('export-purchase-requests') || $user->isSuperAdmin(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new purchase request.
     * Requirements: 3.1, 3.2
     */
    public function create(): Response
    {
        $user = Auth::user();
        $businessUnitId = (int) session('current_business_unit_id');
        $departmentId = (int) session('current_department_id');

        return Inertia::render(
            'Purchasing/PurchaseRequest/Create',
            $this->queryService->getCreateFormData($user, $businessUnitId, $departmentId, $this->formDataProvider),
        );
    }

    /**
     * Store a newly created purchase request.
     * Requirements: 3.10, 3.11, 14.1, 14.3
     */
    public function store(
        \App\Http\Requests\Purchasing\StorePurchaseRequestRequest $request,
        \App\Actions\Modules\Purchasing\PurchaseRequest\CreatePurchaseRequestAction $action,
    ) {
        $result = $action->execute($request, Auth::user());

        if (! $result['ok']) {
            return back()->withInput()->with('error', $result['error']);
        }

        return redirect()
            ->route('purchase-requests.show', $result['purchase_request'])
            ->with('success', 'Purchase request created successfully and submitted for approval.');
    }

    /**
     * Show the form for editing the specified purchase request.
     * Requirements: 3.1, 3.2
     */
    public function editInertia(PurchaseRequest $purchaseRequest): Response
    {
        $user = Auth::user();

        if (! $purchaseRequest->canBeEdited()) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'This purchase request cannot be edited.');
        }

        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this purchase request.');
        }

        if ($purchaseRequest->user_id !== $user->id) {
            abort(403, 'You are not authorized to edit this purchase request.');
        }

        return Inertia::render(
            'Purchasing/PurchaseRequest/Form',
            $this->queryService->getEditFormData($user, $purchaseRequest, $this->formDataProvider),
        );
    }

    /**
     * Update the specified purchase request.
     * Requirements: 3.10, 3.11, 14.1, 14.3
     */
    public function update(
        \App\Http\Requests\Purchasing\StorePurchaseRequestRequest $request,
        PurchaseRequest $purchaseRequest,
        \App\Actions\Modules\Purchasing\PurchaseRequest\UpdatePurchaseRequestAction $action,
    ) {
        $user = Auth::user();

        if (! $purchaseRequest->canBeEdited()) {
            return back()->with('error', 'This purchase request cannot be edited.');
        }

        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this purchase request.');
        }

        if ($purchaseRequest->user_id !== $user->id) {
            abort(403, 'You are not authorized to edit this purchase request.');
        }

        $result = $action->execute($request, $purchaseRequest, $user);

        if (! $result['ok']) {
            return back()->withInput()->with('error', $result['error']);
        }

        return redirect()
            ->route('purchase-requests.show', $result['purchase_request'])
            ->with('success', 'Purchase request updated successfully and resubmitted for approval.');
    }

    /**
     * Display the specified purchase request.
     * Requirements: 8.1, 8.3, 8.7
     */
    public function show(PurchaseRequest $purchaseRequest): Response
    {
        $user = Auth::user();

        // Validate business unit context
        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this purchase request.');
        }

        return Inertia::render(
            'Purchasing/PurchaseRequest/Show',
            $this->queryService->getShowData(
                $purchaseRequest,
                $user,
                (int) session('current_business_unit_id'),
                $this->documentService,
            ),
        );
    }

    /**
     * Approve a purchase request (Inertia endpoint)
     * Requirements: 8.4, 8.5
     */
    public function approve(
        Request $request,
        PurchaseRequest $purchaseRequest,
        \App\Actions\Modules\Purchasing\PurchaseRequest\ProcessApprovalAction $action,
    ) {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        // Validate business unit context
        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            return back()->with('error', 'You do not have access to this purchase request.');
        }

        // Check if PR can be approved
        if (! $purchaseRequest->canBeApproved()) {
            return back()->with('error', 'This purchase request cannot be approved at this time.');
        }

        $result = $action->execute($purchaseRequest, Auth::user(), 'approved', $request->notes);

        if (! $result['ok']) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', 'Purchase request approved successfully.');
    }

    /**
     * Reject a purchase request (Inertia endpoint)
     * Requirements: 8.4, 8.5
     */
    public function reject(
        Request $request,
        PurchaseRequest $purchaseRequest,
        \App\Actions\Modules\Purchasing\PurchaseRequest\ProcessApprovalAction $action,
    ) {
        $request->validate([
            'notes' => 'required|string|max:1000',
        ], [
            'notes.required' => 'Rejection reason is required.',
        ]);

        // Validate business unit context
        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            return back()->with('error', 'You do not have access to this purchase request.');
        }

        // Check if PR can be approved (same check for rejection)
        if (! $purchaseRequest->canBeApproved()) {
            return back()->with('error', 'This purchase request cannot be rejected at this time.');
        }

        $result = $action->execute($purchaseRequest, Auth::user(), 'rejected', $request->notes);

        if (! $result['ok']) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', 'Purchase request rejected successfully.');
    }

    /**
     * Show the form for editing the specified purchase request.
     */
    public function edit(PurchaseRequest $purchaseRequest)
    {
        if (! $purchaseRequest->canBeEdited()) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'This purchase request cannot be edited.');
        }

        if (session('current_business_unit_id') !== $purchaseRequest->business_unit_id) {
            session(['current_business_unit_id' => $purchaseRequest->business_unit_id]);
        }

        if (session('current_department_id') !== $purchaseRequest->department_id) {
            session(['current_department_id' => $purchaseRequest->department_id]);
        }

        $departments = $this->purchaseRequestService->getDepartments();
        $purchaseRequest->load(['items', 'businessUnit']);

        return view('purchasing.purchase-requests.edit', compact('purchaseRequest', 'departments'));
    }

    /**
     * Resubmit rejected purchase request (reset workflow).
     */
    public function resubmit(
        PurchaseRequest $purchaseRequest,
        \App\Actions\Modules\Purchasing\PurchaseRequest\ResubmitPurchaseRequestAction $action,
    ) {
        if ($purchaseRequest->status !== 'rejected') {
            return back()->with('error', 'Only rejected purchase requests can be resubmitted.');
        }

        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this purchase request.');
        }

        if ($purchaseRequest->user_id !== Auth::id()) {
            abort(403, 'You are not authorized to resubmit this purchase request.');
        }

        $result = $action->execute($purchaseRequest, Auth::user());

        if (! $result['ok']) {
            return back()->with('error', $result['error']);
        }

        return redirect()
            ->route('purchase-requests.show', $result['purchase_request'])
            ->with('success', 'Purchase request has been resubmitted for approval. Approval workflow has been reset.');
    }

    /**
     * Resend approval email to current pending approver.
     */
    public function resendApprovalEmail(
        PurchaseRequest $purchaseRequest,
        \App\Actions\Modules\Purchasing\PurchaseRequest\PurchaseRequestSideEffectsAction $action,
    ) {
        $user = Auth::user();

        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this purchase request.');
        }

        if ($purchaseRequest->user_id !== $user->id) {
            abort(403, 'Only the PR owner can resend approval email.');
        }

        if ($purchaseRequest->status !== 'in_approval') {
            return back()->with('error', 'Approval email can only be resent when PR is in approval process.');
        }

        $result = $action->resendApprovalEmail($purchaseRequest, $user);

        if (! $result['ok']) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', 'Approval email has been resent to the current approver.');
    }

    /**
     * Void purchase request.
     */
    public function void(
        Request $request,
        PurchaseRequest $purchaseRequest,
        \App\Actions\Modules\Purchasing\PurchaseRequest\PurchaseRequestSideEffectsAction $action,
    ) {
        $request->validate(['reason' => 'required|string|max:500']);

        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this purchase request.');
        }

        if (in_array($purchaseRequest->status, ['approved', 'voided'])) {
            return back()->with('error', 'This purchase request cannot be voided.');
        }

        $user = Auth::user();
        $canVoid = $purchaseRequest->user_id === $user->id ||
            in_array($user->getAccessLevel(), ['super_admin', 'executive', 'general_manager']);

        if (! $canVoid) {
            abort(403, 'You are not authorized to void this purchase request.');
        }

        $result = $action->void($purchaseRequest, $user, $request->reason);

        if (! $result['ok']) {
            return back()->with('error', $result['error']);
        }

        return redirect()
            ->route('purchase-requests.index')
            ->with('success', 'Purchase request has been voided.');
    }

    /**
     * Mark purchase request as approved offline/manually.
     */
    public function markOfflineApproved(
        Request $request,
        PurchaseRequest $purchaseRequest,
        \App\Actions\Modules\Purchasing\PurchaseRequest\MarkOfflineApprovedAction $action,
    ) {
        $request->validate([
            'notes' => 'nullable|string|max:500',
            'offline_approval_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ], [
            'offline_approval_document.required' => 'Bukti approval offline wajib diupload.',
            'offline_approval_document.mimes' => 'Format file harus JPG, PNG, atau PDF.',
            'offline_approval_document.max' => 'Ukuran file maksimal 10MB.',
        ]);

        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this purchase request.');
        }

        if (! in_array($purchaseRequest->status, ['submitted', 'in_approval'])) {
            return back()->with('error', 'This purchase request cannot be marked as offline approved. Only submitted or in-approval PRs are eligible.');
        }

        $user = Auth::user();
        if ($purchaseRequest->user_id !== $user->id) {
            abort(403, 'Only the PR owner can mark this purchase request as offline approved.');
        }

        $result = $action->execute($request, $purchaseRequest, $user);

        if (! $result['ok']) {
            return back()->with('error', $result['error']);
        }

        return redirect()
            ->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Purchase request has been marked as approved offline/manually.');
    }

    /**
     * Remove the specified purchase request.
     */
    public function destroy(PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this purchase request.');
        }

        if (! $purchaseRequest->canBeEdited()) {
            return back()->with('error', 'This purchase request cannot be deleted.');
        }

        if ($purchaseRequest->user_id !== Auth::id()) {
            abort(403, 'You are not authorized to delete this purchase request.');
        }

        $purchaseRequest->delete();

        return redirect()
            ->route('purchase-requests.index')
            ->with('success', 'Purchase request has been deleted.');
    }

    /**
     * Generate PDF view for purchase request.
     */
    public function pdf(PurchaseRequest $purchaseRequest)
    {
        return $this->documentService->renderPdfView($purchaseRequest, public: false);
    }

    /**
     * Generate PDF view for purchase request - Public access for browsershot.
     */
    public function pdfPublic(PurchaseRequest $purchaseRequest)
    {
        return $this->documentService->renderPdfView($purchaseRequest, public: true);
    }

    /**
     * Download PDF via public route (no authentication required).
     */
    public function downloadPdfPublic(PurchaseRequest $purchaseRequest)
    {
        return $this->documentService->streamPdfDownload($purchaseRequest);
    }

    /**
     * Download PDF for purchase request using configured method.
     */
    public function downloadPdf(PurchaseRequest $purchaseRequest)
    {
        // Directly call downloadPdfPublic to avoid redirect issues on hosting
        return $this->downloadPdfPublic($purchaseRequest);
    }

    /**
     * Stream the supporting document for a purchase request.
     */
    public function supportingDocument(PurchaseRequest $purchaseRequest): BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        return $this->documentService->serveSupportingDocument(
            $purchaseRequest,
            Auth::user(),
            (int) session('current_business_unit_id'),
            download: false,
        );
    }

    /**
     * Download the supporting document for a purchase request.
     */
    public function downloadSupportingDocument(PurchaseRequest $purchaseRequest): BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        return $this->documentService->serveSupportingDocument(
            $purchaseRequest,
            Auth::user(),
            (int) session('current_business_unit_id'),
            download: true,
        );
    }

    /**
     * Stream the offline approval document for an approved PR.
     *
     * Authorization is delegated to PurchaseRequestDocumentService and follows
     * the PO 2026-05-26 widening: super admin, top management, purchasing admin
     * in the PR's BU/ancestor BU, the assigned approver, and the PR creator
     * (only when the PR is in the user's current BU context).
     */
    public function offlineApprovalDocument(PurchaseRequest $purchaseRequest): BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        return $this->documentService->serveOfflineApprovalDocument(
            $purchaseRequest,
            Auth::user(),
            (int) session('current_business_unit_id'),
        );
    }

    // ============================================
    // Private Helper Methods
    // ============================================

    /**
     * Get authorization props for show page.
     * Requirements: 8.3, 8.7
     */
    private function getShowAuthorization(PurchaseRequest $pr, $user): array
    {
        return $this->queryService->getShowAuthorization(
            $pr,
            $user,
            (int) session('current_business_unit_id'),
            $this->documentService,
        );
    }
}
