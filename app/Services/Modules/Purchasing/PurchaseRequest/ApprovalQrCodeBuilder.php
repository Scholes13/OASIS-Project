<?php

namespace App\Services\Modules\Purchasing\PurchaseRequest;

use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Core\QrCodeService;

/**
 * Builds the QR-code data-URL bundle rendered on the PR public approval page.
 *
 * Extracted from
 * {@see \App\Http\Controllers\Modules\Purchasing\PurchaseRequest\ApprovalController::generateQrCodesForPublicApproval()}.
 * Behavior preserved verbatim.
 */
class ApprovalQrCodeBuilder
{
    public function __construct(
        protected QrCodeService $qrCodeService,
    ) {}

    /**
     * Generate QR codes for the public approval Blade view.
     *
     * Returns:
     * - 'requestor': data-URL of the requestor QR (only if PR has been submitted).
     * - 'approvals': map of approved PrApproval id => approver QR data-URL.
     *
     * @return array{
     *     requestor?: string,
     *     approvals: array<int, string>,
     * }
     */
    public function buildForPublicApproval(PurchaseRequest $purchaseRequest): array
    {
        $qrCodes = ['approvals' => []];

        if ($purchaseRequest->submitted_at) {
            $qrCodes['requestor'] = $this->qrCodeService->generateRequestorQrCodeDataUrl($purchaseRequest);
        }

        foreach ($purchaseRequest->approvals->where('status', 'approved') as $approval) {
            $qrCodes['approvals'][$approval->id] = $this->qrCodeService->generateApproverQrCodeDataUrl($approval);
        }

        return $qrCodes;
    }
}
