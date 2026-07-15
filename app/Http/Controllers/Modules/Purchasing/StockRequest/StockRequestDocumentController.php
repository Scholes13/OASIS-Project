<?php

namespace App\Http\Controllers\Modules\Purchasing\StockRequest;

use App\Http\Controllers\Controller;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Modules\Purchasing\StockRequest\StockRequestDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockRequestDocumentController extends Controller
{
    public function __construct(private StockRequestDocumentService $documentService) {}

    public function pdfPublic(Request $request, StockRequest $stockRequest): mixed
    {
        $this->authorizePublicOrAuthenticated($request, $stockRequest);

        return $this->documentService->renderPdfView($stockRequest);
    }

    public function downloadPdfPublic(Request $request, StockRequest $stockRequest): mixed
    {
        $this->authorizePublicOrAuthenticated($request, $stockRequest);

        return $this->documentService->streamPdfDownload($stockRequest);
    }

    public function downloadPdf(StockRequest $stockRequest): mixed
    {
        $this->authorizeAuthenticated($stockRequest);

        return $this->documentService->streamPdfDownload($stockRequest);
    }

    public function offlineApprovalDocument(StockRequest $stockRequest): BinaryFileResponse|RedirectResponse
    {
        return $this->documentService->serveOfflineApprovalDocument(
            $stockRequest,
            Auth::user(),
            (int) session('current_business_unit_id'),
        );
    }

    private function authorizePublicOrAuthenticated(Request $request, StockRequest $stockRequest): void
    {
        if ($request->hasValidSignature()) {
            return;
        }

        $this->authorizeAuthenticated($stockRequest);
    }

    private function authorizeAuthenticated(StockRequest $stockRequest): void
    {
        $user = Auth::user();

        if (! $user || ! $this->documentService->canAccessDocument($stockRequest, $user)) {
            abort(403, 'You do not have access to this stock request.');
        }
    }
}
