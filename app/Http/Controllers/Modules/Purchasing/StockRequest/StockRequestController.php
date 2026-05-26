<?php

namespace App\Http\Controllers\Modules\Purchasing\StockRequest;

use App\Http\Controllers\Controller;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Modules\Purchasing\Shared\RequestFormDataProvider;
use App\Services\Modules\Purchasing\StockRequest\StockRequestDocumentService;
use App\Services\Modules\Purchasing\StockRequest\StockRequestQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockRequestController extends Controller
{
    public function __construct(
        protected RequestFormDataProvider $formDataProvider,
        protected StockRequestDocumentService $documentService,
        protected StockRequestQueryService $queryService,
    ) {}

    /**
     * Display a listing of the user's stock requests.
     * Requirements: 6.1
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $businessUnitId = (int) session('current_business_unit_id');

        $stockRequests = $this->queryService->paginateForUser($request, $user, $businessUnitId);

        $reservations = $this->queryService->getReservationsForUser(
            $user,
            $businessUnitId,
            $request->get('search', ''),
        );

        return Inertia::render('Purchasing/StockRequest/Index', [
            'stockRequests' => $stockRequests,
            'reservations' => $reservations,
            'filters' => [
                'search' => $request->get('search', ''),
                'status' => $request->get('status', ''),
                'date_from' => $request->get('date_from', ''),
                'date_to' => $request->get('date_to', ''),
            ],
            'can' => [
                'create' => true, // All authenticated users can create STs
                'viewAll' => $user->can('view-all-stock-requests') || $user->isSuperAdmin(),
                'export' => $user->can('export-stock-requests') || $user->isSuperAdmin(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new stock request.
     * Requirements: 6.2, 6.3
     */
    public function createInertia(): Response
    {
        $user = Auth::user();
        $businessUnitId = (int) session('current_business_unit_id');
        $departmentId = (int) session('current_department_id');

        return Inertia::render(
            'Purchasing/StockRequest/Form',
            $this->queryService->getCreateFormData($user, $businessUnitId, $departmentId, $this->formDataProvider),
        );
    }

    /**
     * Store a newly created stock request.
     * Requirements: 6.2, 6.3
     */
    public function store(
        \App\Http\Requests\Purchasing\StoreStockRequestRequest $request,
        \App\Actions\Modules\Purchasing\StockRequest\CreateStockRequestAction $action,
    ) {
        $result = $action->execute($request, Auth::user());

        if (! $result['ok']) {
            return back()->withInput()->with('error', $result['error']);
        }

        return redirect()
            ->route('stock-requests.show', $result['stock_request'])
            ->with('success', 'Stock request created successfully and submitted for approval.');
    }

    /**
     * Show the form for editing the specified stock request.
     * Requirements: 6.2, 6.3
     */
    public function editInertia(StockRequest $stockRequest): Response
    {
        $user = Auth::user();

        if (! $stockRequest->isEditable()) {
            return redirect()
                ->route('stock-requests.show', $stockRequest)
                ->with('error', 'This stock request cannot be edited.');
        }

        if ($stockRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this stock request.');
        }

        if ($stockRequest->user_id !== $user->id) {
            abort(403, 'You are not authorized to edit this stock request.');
        }

        return Inertia::render(
            'Purchasing/StockRequest/Form',
            $this->queryService->getEditFormData($user, $stockRequest, $this->formDataProvider),
        );
    }

    /**
     * Update the specified stock request.
     * Requirements: 6.2, 6.3
     */
    public function update(
        \App\Http\Requests\Purchasing\StoreStockRequestRequest $request,
        StockRequest $stockRequest,
        \App\Actions\Modules\Purchasing\StockRequest\UpdateStockRequestAction $action,
    ) {
        $user = Auth::user();

        if (! $stockRequest->isEditable()) {
            return back()->with('error', 'This stock request cannot be edited.');
        }

        if ($stockRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this stock request.');
        }

        if ($stockRequest->user_id !== $user->id) {
            abort(403, 'You are not authorized to edit this stock request.');
        }

        $result = $action->execute($request, $stockRequest, $user);

        if (! $result['ok']) {
            return back()->withInput()->with('error', $result['error']);
        }

        return redirect()
            ->route('stock-requests.show', $result['stock_request'])
            ->with('success', 'Stock request updated successfully and resubmitted for approval.');
    }

    /**
     * Display the specified stock request.
     * Requirements: 6.4
     */
    public function showInertia(StockRequest $stockRequest): Response
    {
        $user = Auth::user();

        if ($stockRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this stock request.');
        }

        return Inertia::render(
            'Purchasing/StockRequest/Show',
            $this->queryService->getShowData(
                $stockRequest,
                $user,
                (int) session('current_business_unit_id'),
            ),
        );
    }

    /**
     * Show the form for creating a new stock request.
     */
    public function create(): Response
    {
        return $this->createInertia();
    }

    /**
     * Display the specified stock request.
     */
    public function show(StockRequest $stockRequest): Response
    {
        return $this->showInertia($stockRequest);
    }

    /**
     * Show the form for editing the specified stock request.
     */
    public function edit(StockRequest $stockRequest): Response
    {
        return $this->editInertia($stockRequest);
    }

    /**
     * Remove the specified stock request
     */
    public function destroy(StockRequest $stockRequest)
    {
        // Verify business unit access
        if ($stockRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this stock request.');
        }

        // Only draft stock requests can be deleted
        if ($stockRequest->status !== 'draft') {
            return back()->with('error', 'Only draft stock requests can be deleted.');
        }

        // Check if user owns this stock request or has permission
        if ($stockRequest->user_id !== Auth::id() && ! Auth::user()->can('delete', $stockRequest)) {
            abort(403, 'You do not have permission to delete this stock request.');
        }

        $stockRequest->delete();

        return redirect()
            ->route('stock-requests.index')
            ->with('success', "Stock request {$stockRequest->st_number} has been deleted.");
    }

    /**
     * Void a stock request
     */
    public function void(Request $request, StockRequest $stockRequest)
    {
        // Verify business unit access
        if ($stockRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this stock request.');
        }

        $user = Auth::user();
        $isOwner = (int) $stockRequest->user_id === $user->id;
        $isAdmin = $user->isAdminInBuOrAncestor('is_purchasing_admin', (int) session('current_business_unit_id'));

        if (! $isOwner && ! $isAdmin) {
            abort(403, 'Only the request creator or a purchasing admin can void this request.');
        }

        if (! $stockRequest->canBeVoided()) {
            return back()->with('error', 'This stock request cannot be voided.');
        }

        $request->validate([
            'void_reason' => 'required|string|max:500',
        ]);

        $stockRequest->update([
            'status' => 'voided',
            'voided_at' => now(),
            'rejection_notes' => $request->void_reason,
        ]);

        return back()->with('success', "Stock request {$stockRequest->st_number} has been voided.");
    }

    /**
     * Mark stock request as approved offline/manually
     *
     * Used when digital approval is too slow and user has exported the ST
     * for manual/offline approval. This marks the entire ST as approved at once.
     */
    public function markOfflineApproved(
        Request $request,
        StockRequest $stockRequest,
        \App\Actions\Modules\Purchasing\StockRequest\MarkStockOfflineApprovedAction $action,
    ) {
        $request->validate([
            'notes' => 'nullable|string|max:500',
            'offline_approval_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ], [
            'offline_approval_document.required' => 'Bukti approval offline wajib diupload.',
            'offline_approval_document.mimes' => 'Format file harus JPG, PNG, atau PDF.',
            'offline_approval_document.max' => 'Ukuran file maksimal 10MB.',
        ]);

        if ($stockRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this stock request.');
        }

        if (! in_array($stockRequest->status, ['submitted', 'in_approval'])) {
            return back()->with('error', 'This stock request cannot be marked as offline approved. Only submitted or in-approval STs are eligible.');
        }

        $user = Auth::user();
        if ($stockRequest->user_id !== $user->id) {
            abort(403, 'Only the ST owner can mark this stock request as offline approved.');
        }

        $result = $action->execute($request, $stockRequest, $user);

        if (! $result['ok']) {
            return back()->with('error', $result['error']);
        }

        return redirect()
            ->route('stock-requests.show', $stockRequest)
            ->with('success', 'Stock request has been marked as approved offline/manually.');
    }

    /**
     * Resubmit rejected stock request
     */
    public function resubmit(
        StockRequest $stockRequest,
        \App\Actions\Modules\Purchasing\StockRequest\ResubmitStockRequestAction $action,
    ) {
        if ($stockRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this stock request.');
        }

        if ((int) $stockRequest->user_id !== Auth::id()) {
            abort(403, 'Only the request creator can resubmit.');
        }

        if ($stockRequest->status !== 'rejected') {
            return back()->with('error', 'Only rejected stock requests can be resubmitted.');
        }

        $action->execute($stockRequest);

        return redirect()
            ->route('stock-requests.show', $stockRequest)
            ->with('success', "Stock request {$stockRequest->st_number} has been resubmitted for approval.");
    }

    /**
     * Resend approval email to the current pending approver.
     */
    public function resendApprovalEmail(StockRequest $stockRequest)
    {
        $user = Auth::user();

        if ($stockRequest->business_unit_id !== (int) session('current_business_unit_id')) {
            abort(403, 'You do not have access to this stock request.');
        }

        if ($stockRequest->user_id !== $user->id) {
            abort(403, 'Only the ST owner can resend approval email.');
        }

        if ($stockRequest->status !== 'in_approval') {
            return back()->with('error', 'Approval email can only be resent when ST is in approval process.');
        }

        /** @var \App\Models\Modules\Purchasing\StockRequest\StockApproval|null $currentApproval */
        $currentApproval = $stockRequest->currentApproval();
        if (! $currentApproval || $currentApproval->status !== 'pending') {
            return back()->with('error', 'No active approval step found. The approval workflow may need to be rebuilt. Please contact a purchasing administrator.');
        }

        try {
            $currentApproval->loadMissing('approver', 'stockRequest.user');

            app(\App\Services\Core\EmailNotificationService::class)
                ->sendStApprovalRequested($currentApproval);

            return back()->with('success', 'Approval email has been resent to the current approver.');
        } catch (\Exception $e) {
            Log::error('Failed to resend stock request approval email', [
                'st_id' => $stockRequest->id,
                'st_number' => $stockRequest->st_number,
                'approval_id' => $currentApproval->id,
                'requestor_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to resend approval email. Please try again or contact support.');
        }
    }

    /**
     * Display PDF view for public access (no authentication required)
     */
    public function pdfPublic(StockRequest $stockRequest)
    {
        return $this->documentService->renderPdfView($stockRequest);
    }

    /**
     * Download PDF for stock request (public access)
     */
    public function downloadPdfPublic(StockRequest $stockRequest)
    {
        return $this->documentService->streamPdfDownload($stockRequest);
    }

    /**
     * Download PDF for stock request using configured method
     */
    public function downloadPdf(StockRequest $stockRequest)
    {
        // Directly call downloadPdfPublic to avoid redirect issues on hosting
        return $this->downloadPdfPublic($stockRequest);
    }

    /**
     * Stream the offline approval evidence for a stock request.
     */
    public function offlineApprovalDocument(StockRequest $stockRequest): BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        return $this->documentService->serveOfflineApprovalDocument(
            $stockRequest,
            Auth::user(),
            (int) session('current_business_unit_id'),
        );
    }
}
