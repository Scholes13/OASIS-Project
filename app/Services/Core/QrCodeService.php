<?php

namespace App\Services\Core;

use App\Models\Modules\PurchaseRequest\PrApproval;
use App\Models\Modules\PurchaseRequest\PurchaseRequest;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Service for generating QR codes for purchase request verification
 *
 * Note: QrCode facade methods (size, margin, etc.) are properly defined
 * in SimpleSoftwareIO\QrCode but may not be recognized by static analysis.
 */
class QrCodeService
{
    /**
     * Generate QR code for purchase request creator
     */
    public function generateRequestorQrCode(PurchaseRequest $purchaseRequest): string
    {
        if (! $purchaseRequest->submitted_at) {
            return $this->generatePlaceholderQr();
        }

        // Generate verification token for requestor
        $verificationToken = $this->generateRequestorToken($purchaseRequest);

        // Create public URL for PR verification
        $publicUrl = route('purchase-requests.public', [
            'pr' => $purchaseRequest->id,
            'token' => $verificationToken,
            'requestor' => $purchaseRequest->user_id,
        ]);

        // Generate QR code as SVG for PDF embedding
        return QrCode::format('svg')
            ->size(120)
            ->margin(1)
            ->generate($publicUrl);
    }

    /**
     * Generate QR code for approver
     */
    public function generateApproverQrCode(PrApproval $approval): string
    {
        if ($approval->status !== 'approved') {
            return $this->generatePlaceholderQr();
        }

        // Generate verification token for this approval
        $verificationToken = $this->generateApprovalToken($approval);

        // Create public URL for PR verification
        $publicUrl = route('purchase-requests.public', [
            'pr' => $approval->purchase_request_id,
            'token' => $verificationToken,
            'approver' => $approval->approver_id,
        ]);

        // Generate QR code as SVG for PDF embedding
        return QrCode::format('svg')
            ->size(120)
            ->margin(1)
            ->generate($publicUrl);
    }

    /**
     * Generate base64 data URL for QR code (for PDF embedding)
     */
    public function generateRequestorQrCodeDataUrl(PurchaseRequest $purchaseRequest): string
    {
        $qrCodeSvg = $this->generateRequestorQrCode($purchaseRequest);

        return 'data:image/svg+xml;base64,'.base64_encode($qrCodeSvg);
    }

    /**
     * Generate base64 data URL for approver QR code (for PDF embedding)
     */
    public function generateApproverQrCodeDataUrl(PrApproval $approval): string
    {
        $qrCodeSvg = $this->generateApproverQrCode($approval);

        return 'data:image/svg+xml;base64,'.base64_encode($qrCodeSvg);
    }

    /**
     * Generate verification token for requestor
     */
    protected function generateRequestorToken(PurchaseRequest $purchaseRequest): string
    {
        $data = [
            'pr_id' => $purchaseRequest->id,
            'user_id' => $purchaseRequest->user_id,
            'submitted_at' => $purchaseRequest->submitted_at?->timestamp,
            'type' => 'requestor',
        ];

        return hash('sha256', json_encode($data).config('app.key'));
    }

    /**
     * Generate verification token for approval
     */
    public function generateApprovalToken(PrApproval $approval): string
    {
        $data = [
            'approval_id' => $approval->id,
            'pr_id' => $approval->purchase_request_id,
            'approver_id' => $approval->approver_id,
            'approved_at' => $approval->responded_at?->timestamp,
            'type' => 'approval',
        ];

        return hash('sha256', json_encode($data).config('app.key'));
    }

    /**
     * Verify requestor token
     */
    public function verifyRequestorToken(PurchaseRequest $purchaseRequest, string $token): bool
    {
        $expectedToken = $this->generateRequestorToken($purchaseRequest);

        return hash_equals($expectedToken, $token);
    }

    /**
     * Verify approval token
     */
    public function verifyApprovalToken(PrApproval $approval, string $token): bool
    {
        $expectedToken = $this->generateApprovalToken($approval);

        return hash_equals($expectedToken, $token);
    }

    /**
     * Generate public verification URL for an approval
     */
    public function generatePublicVerificationUrl(PrApproval $approval): string
    {
        $verificationToken = $this->generateApprovalToken($approval);

        return route('purchase-requests.public', [
            'pr' => $approval->purchase_request_id,
            'token' => $verificationToken,
            'approver' => $approval->approver_id,
        ]);
    }

    /**
     * Generate public verification URL for requestor
     */
    public function generateRequestorPublicVerificationUrl(PurchaseRequest $purchaseRequest): string
    {
        $verificationToken = $this->generateRequestorToken($purchaseRequest);

        return route('purchase-requests.public', [
            'pr' => $purchaseRequest->id,
            'token' => $verificationToken,
            'requestor' => $purchaseRequest->user_id,
        ]);
    }

    /**
     * Generate placeholder QR code for pending items
     *
     * @return string SVG QR code
     *
     * Note: SimpleSoftwareIO QrCode methods are dynamically called via facade.
     * Static analysis may report false positives for size() and margin() methods.
     */
    protected function generatePlaceholderQr(): string
    {
        // @phpstan-ignore-next-line method.notFound
        return QrCode::format('svg')
            ->size(120)  // @phpstan-ignore-line
            ->margin(1)
            ->generate('PENDING_VERIFICATION');
    }

    /**
     * Generate placeholder QR code data URL
     */
    public function generatePlaceholderQrDataUrl(): string
    {
        $qrCodeSvg = $this->generatePlaceholderQr();

        return 'data:image/svg+xml;base64,'.base64_encode($qrCodeSvg);
    }
}
