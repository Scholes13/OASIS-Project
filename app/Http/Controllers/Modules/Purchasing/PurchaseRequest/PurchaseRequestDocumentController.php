<?php

namespace App\Http\Controllers\Modules\Purchasing\PurchaseRequest;

use App\Http\Controllers\Controller;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Modules\Purchasing\AllRequests\PurchasingRequestScopeResolver;
use App\Services\Modules\Purchasing\PurchaseRequest\PurchaseRequestDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PurchaseRequestDocumentController extends Controller
{
    public function __construct(
        private PurchaseRequestDocumentService $documentService,
        private PurchasingRequestScopeResolver $scopeResolver,
    ) {}

    public function pdf(PurchaseRequest $purchaseRequest): mixed
    {
        $this->authorizeDocument($purchaseRequest);

        return $this->documentService->renderPdfView($purchaseRequest);
    }

    public function pdfPublic(PurchaseRequest $purchaseRequest): mixed
    {
        return $this->documentService->renderPdfView($purchaseRequest, public: true);
    }

    public function downloadPdfPublic(PurchaseRequest $purchaseRequest): mixed
    {
        return $this->documentService->streamPdfDownload($purchaseRequest);
    }

    public function downloadPdf(PurchaseRequest $purchaseRequest): mixed
    {
        $this->authorizeDocument($purchaseRequest);

        return $this->documentService->streamPdfDownload($purchaseRequest);
    }

    public function supportingDocument(PurchaseRequest $purchaseRequest): BinaryFileResponse|RedirectResponse
    {
        return $this->documentService->serveSupportingDocument($purchaseRequest, Auth::user(), (int) session('current_business_unit_id'), false);
    }

    public function downloadSupportingDocument(PurchaseRequest $purchaseRequest): BinaryFileResponse|RedirectResponse
    {
        return $this->documentService->serveSupportingDocument($purchaseRequest, Auth::user(), (int) session('current_business_unit_id'), true);
    }

    public function offlineApprovalDocument(PurchaseRequest $purchaseRequest): BinaryFileResponse|RedirectResponse
    {
        return $this->documentService->serveOfflineApprovalDocument($purchaseRequest, Auth::user(), (int) session('current_business_unit_id'));
    }

    private function authorizeDocument(PurchaseRequest $purchaseRequest): void
    {
        $user = Auth::user();
        if (! $user || ! $user->is_active || ! $this->scopeResolver->canAccess(
            $user,
            (int) session('current_business_unit_id'),
            (int) $purchaseRequest->business_unit_id,
        )) {
            abort(403, 'You do not have access to this purchase request.');
        }
    }
}
