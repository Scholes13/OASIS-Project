<?php

namespace App\Services\Modules\Purchasing\PurchaseRequest;

use App\Models\Core\BusinessUnit;
use App\Models\Core\User;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Core\QrCodeService;
use App\Services\Modules\Purchasing\Shared\PdfGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Document/PDF/QR helpers for Purchase Requests.
 *
 * Owns the document streaming, QR generation, and access-check helpers
 * previously inlined in PurchaseRequestController. Behavior preserved verbatim.
 */
class PurchaseRequestDocumentService
{
    public function __construct(
        private PdfGenerationService $pdfGenerationService,
    ) {}

    /**
     * Render the browser PDF view for a PR.
     */
    public function renderPdfView(PurchaseRequest $purchaseRequest, bool $public = false): \Illuminate\View\View
    {
        $relations = ['user', 'department', 'businessUnit', 'items', 'approvals.approver'];
        if ($public) {
            $relations[] = 'offlineApprovedBy';
        }

        $purchaseRequest->load($relations);

        $qrCodeService = new QrCodeService;
        $qrCodes = $this->generateQrCodesForPdf($purchaseRequest, $qrCodeService);

        return view('purchasing.purchase-requests.pdf-browser', compact('purchaseRequest', 'qrCodes'));
    }

    /**
     * Stream PR PDF for download via browsershot pipeline.
     */
    public function streamPdfDownload(PurchaseRequest $purchaseRequest): mixed
    {
        $purchaseRequest->load([
            'user',
            'department',
            'businessUnit',
            'items',
            'approvals.approver',
            'offlineApprovedBy',
        ]);

        $qrCodeService = new QrCodeService;
        $qrCodes = $this->generateQrCodesForPdf($purchaseRequest, $qrCodeService);

        $cleanPrNumber = preg_replace('/[\/\\\\:*?"<>|]/', '-', $purchaseRequest->pr_number);
        $filename = 'PR-'.$cleanPrNumber.'.pdf';

        return $this->pdfGenerationService->streamPdf(
            'purchasing.purchase-requests.pdf-browser',
            compact('purchaseRequest', 'qrCodes'),
            [
                'filename' => $filename,
                'fallback_url' => URL::temporarySignedRoute('purchase-requests.pdf-public', now()->addMinutes(10), [
                    'purchaseRequest' => $purchaseRequest->id,
                ]),
            ],
        );
    }

    /**
     * Serve a supporting document using inline or attachment disposition.
     */
    public function serveSupportingDocument(
        PurchaseRequest $purchaseRequest,
        User $user,
        int $currentBusinessUnitId,
        bool $download
    ): BinaryFileResponse|RedirectResponse {
        if (! $this->canAccessSupportingDocument($purchaseRequest, $user, $currentBusinessUnitId)) {
            abort(403, 'You do not have access to this purchase request.');
        }

        $documentPath = $purchaseRequest->supporting_document_path;
        if (! $documentPath || ! Storage::disk('public')->exists($documentPath)) {
            return back()->with('error', 'The requested document file is no longer available. Please contact the request creator to re-upload.');
        }

        $documentName = $purchaseRequest->supporting_document_name ?? basename($documentPath);
        $fullPath = Storage::disk('public')->path($documentPath);

        if ($download) {
            return response()->download($fullPath, $documentName);
        }

        return response()->file($fullPath, [
            'Content-Disposition' => 'inline; filename="'.$documentName.'"',
        ]);
    }

    /**
     * Stream the offline-approval evidence document (PR creator/approver/admins only).
     */
    public function serveOfflineApprovalDocument(
        PurchaseRequest $purchaseRequest,
        User $user,
        int $currentBusinessUnitId,
    ): BinaryFileResponse|RedirectResponse {
        if (! $this->canAccessOfflineApprovalDocument($purchaseRequest, $user, $currentBusinessUnitId)) {
            abort(403, 'You do not have access to this purchase request.');
        }

        $documentPath = $purchaseRequest->offline_approval_document_path;
        if (! $documentPath || ! Storage::disk('public')->exists($documentPath)) {
            return back()->with('error', 'The requested document file is no longer available. Please contact the request creator to re-upload.');
        }

        return response()->file(Storage::disk('public')->path($documentPath));
    }

    /**
     * Determine whether the user may view the supporting document.
     * Behavior preserved from controller helper.
     */
    public function canAccessSupportingDocument(
        PurchaseRequest $purchaseRequest,
        User $user,
        int $currentBusinessUnitId,
    ): bool {
        $isAssignedApprover = $purchaseRequest->approvals()
            ->where('approver_id', $user->id)
            ->exists();

        if ($isAssignedApprover && $this->selectedContextContains($purchaseRequest, $currentBusinessUnitId)) {
            return true;
        }

        return $purchaseRequest->business_unit_id === $currentBusinessUnitId
            && $purchaseRequest->user_id === $user->id;
    }

    /**
     * Determine whether the user may view the offline-approval evidence.
     * Mirrors the previous controller helper exactly.
     */
    public function canAccessOfflineApprovalDocument(
        PurchaseRequest $purchaseRequest,
        User $user,
        int $currentBusinessUnitId,
    ): bool {
        if (! $this->selectedContextContains($purchaseRequest, $currentBusinessUnitId)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasTopManagementAccess()) {
            return true;
        }

        if ($user->isAdminInBuOrAncestor('is_purchasing_admin', $purchaseRequest->business_unit_id)) {
            return true;
        }

        $isAssignedApprover = $purchaseRequest->approvals()
            ->where('approver_id', $user->id)
            ->exists();

        if ($isAssignedApprover) {
            return true;
        }

        return $purchaseRequest->business_unit_id === $currentBusinessUnitId
            && $purchaseRequest->user_id === $user->id;
    }

    private function selectedContextContains(PurchaseRequest $purchaseRequest, int $currentBusinessUnitId): bool
    {
        if ((int) $purchaseRequest->business_unit_id === $currentBusinessUnitId) {
            return true;
        }

        $selectedBusinessUnit = BusinessUnit::find($currentBusinessUnitId);
        $requestBusinessUnit = BusinessUnit::find($purchaseRequest->business_unit_id);

        return $selectedBusinessUnit !== null
            && $requestBusinessUnit !== null
            && $selectedBusinessUnit->isParentOf($requestBusinessUnit);
    }

    /**
     * Generate QR codes for PDF.
     */
    public function generateQrCodesForPdf(PurchaseRequest $purchaseRequest, QrCodeService $qrCodeService): array
    {
        $qrCodes = [];

        // Skip QR code generation for rejected PRs
        if ($purchaseRequest->status === 'rejected') {
            return $qrCodes;
        }

        // Generate QR code for requestor (only if submitted)
        if ($purchaseRequest->submitted_at) {
            $qrCodes['requestor'] = $qrCodeService->generateRequestorQrCodeDataUrl($purchaseRequest);
        }

        // Generate QR codes for each approved approval only
        $qrCodes['approvals'] = [];
        foreach ($purchaseRequest->approvals->where('status', 'approved') as $approval) {
            $qrCodes['approvals'][$approval->id] = $qrCodeService->generateApproverQrCodeDataUrl($approval);
        }

        return $qrCodes;
    }
}
