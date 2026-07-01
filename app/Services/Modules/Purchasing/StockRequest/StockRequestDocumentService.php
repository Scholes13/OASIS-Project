<?php

namespace App\Services\Modules\Purchasing\StockRequest;

use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Core\QrCodeService;
use App\Services\Modules\Purchasing\Shared\PdfGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Document/PDF/QR helpers for Stock Requests.
 *
 * Owns the document streaming, QR generation, and access-check helpers
 * previously inlined in StockRequestController. Behavior preserved verbatim.
 */
class StockRequestDocumentService
{
    public function __construct(
        private PdfGenerationService $pdfGenerationService,
    ) {}

    /**
     * Render the public-access PDF view for an ST.
     */
    public function renderPdfView(StockRequest $stockRequest): \Illuminate\View\View
    {
        $stockRequest->load([
            'user',
            'department',
            'businessUnit',
            'items',
            'approvals.approver',
            'adminTask.assignedAdmin.primaryDepartment',
            'adminTask.assignedAdmin.primaryDepartment',
        ]);

        $qrCodeService = new QrCodeService;
        $qrCodes = $this->generateQrCodesForPdf($stockRequest, $qrCodeService);

        return view('purchasing.stock-requests.pdf-browser', compact('stockRequest', 'qrCodes'));
    }

    /**
     * Stream ST PDF for download via browsershot pipeline.
     */
    public function streamPdfDownload(StockRequest $stockRequest): mixed
    {
        $stockRequest->load([
            'user',
            'department',
            'businessUnit',
            'items',
            'approvals.approver',
            'adminTask.assignedAdmin.primaryDepartment',
            'adminTask.assignedAdmin.primaryDepartment',
        ]);

        $qrCodeService = new QrCodeService;
        $qrCodes = $this->generateQrCodesForPdf($stockRequest, $qrCodeService);

        $cleanStNumber = preg_replace('/[\/\\\\:*?"<>|]/', '-', $stockRequest->st_number);
        $filename = 'ST-'.$cleanStNumber.'.pdf';

        return $this->pdfGenerationService->streamPdf(
            'purchasing.stock-requests.pdf-browser',
            compact('stockRequest', 'qrCodes'),
            [
                'filename' => $filename,
                'fallback_url' => route('stock-requests.pdf-public', $stockRequest),
            ],
        );
    }

    /**
     * Stream the offline-approval evidence document.
     */
    public function serveOfflineApprovalDocument(
        StockRequest $stockRequest,
        User $user,
        int $currentBusinessUnitId,
    ): BinaryFileResponse|RedirectResponse {
        if (! $this->canAccessOfflineApprovalDocument($stockRequest, $user, $currentBusinessUnitId)) {
            abort(403, 'You do not have access to this stock request.');
        }

        $documentPath = $stockRequest->offline_approval_document_path;
        if (! $documentPath || ! Storage::disk('public')->exists($documentPath)) {
            return back()->with('error', 'The requested document file is no longer available. Please contact the request creator to re-upload.');
        }

        $documentName = $stockRequest->offline_approval_document_name ?? basename($documentPath);

        return response()->file(Storage::disk('public')->path($documentPath), [
            'Content-Disposition' => 'inline; filename="'.$documentName.'"',
        ]);
    }

    /**
     * Determine whether the user may view the offline-approval evidence.
     * Mirrors the previous controller helper exactly.
     */
    public function canAccessOfflineApprovalDocument(
        StockRequest $stockRequest,
        User $user,
        int $currentBusinessUnitId,
    ): bool {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasTopManagementAccess()) {
            return true;
        }

        if ($user->isAdminInBuOrAncestor('is_purchasing_admin', $stockRequest->business_unit_id)) {
            return true;
        }

        $isAssignedApprover = $stockRequest->approvals()
            ->where('approver_id', $user->id)
            ->exists();

        if ($isAssignedApprover) {
            return true;
        }

        return $stockRequest->business_unit_id === $currentBusinessUnitId
            && $stockRequest->user_id === $user->id;
    }

    /**
     * Generate QR codes for PDF.
     */
    public function generateQrCodesForPdf(StockRequest $stockRequest, QrCodeService $qrCodeService): array
    {
        $qrCodes = [];

        // Skip QR code generation for rejected SRs
        if ($stockRequest->status === 'rejected') {
            return $qrCodes;
        }

        // Generate QR code for requestor (only if submitted)
        if ($stockRequest->submitted_at) {
            $qrCodes['requestor'] = $qrCodeService->generateStockRequestorQrCodeDataUrl($stockRequest);
        }

        // Generate QR codes for each approved approval only
        $qrCodes['approvals'] = [];
        foreach ($stockRequest->approvals->where('status', 'approved') as $approval) {
            $qrCodes['approvals'][$approval->id] = $qrCodeService->generateStockApprovalQrCodeDataUrl($approval);
        }

        if ($stockRequest->ga_reviewed_by && $stockRequest->ga_reviewed_at) {
            $qrCodes['ga_reviewer'] = $qrCodeService->generateStockGaReviewerQrCodeDataUrl($stockRequest);
        }

        $acknowledger = $this->resolvePurchasingAcknowledger($stockRequest);
        if ($acknowledger) {
            $qrCodes['purchasing_acknowledger'] = $qrCodeService->generateStockPurchasingAcknowledgerQrCodeDataUrl($stockRequest, $acknowledger);
            $qrCodes['purchasing_acknowledger_user'] = $acknowledger;
        }

        return $qrCodes;
    }

    public function resolvePurchasingAcknowledger(StockRequest $stockRequest): ?User
    {
        $assignedAdmin = $stockRequest->adminTask?->assignedAdmin;
        if (! $assignedAdmin) {
            return null;
        }

        $assignment = UserBusinessUnit::with(['user.primaryDepartment', 'position'])
            ->where('user_business_units.business_unit_id', $stockRequest->business_unit_id)
            ->where('user_business_units.department_id', $stockRequest->adminTask->department_id)
            ->where('user_business_units.is_active', true)
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->whereHas('position', fn ($query) => $query
                ->whereIn('level', ['hod', 'leader'])
                ->orWhereIn('access_level', ['department_head', 'team_leader']))
            ->join('positions', 'positions.id', '=', 'user_business_units.position_id')
            ->orderByRaw("CASE WHEN positions.level = 'hod' OR positions.access_level = 'department_head' THEN 0 ELSE 1 END")
            ->select('user_business_units.*')
            ->first();

        return $assignment?->user;
    }
}
