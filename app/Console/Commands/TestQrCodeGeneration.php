<?php

namespace App\Console\Commands;

use App\Models\Modules\Wns\PurchaseRequest;
use App\Services\QrCodeService;
use Illuminate\Console\Command;

class TestQrCodeGeneration extends Command
{
    protected $signature = 'test:qr-generation {pr_id?}';

    protected $description = 'Test QR code generation for purchase requests';

    public function handle()
    {
        $prId = $this->argument('pr_id');

        if (! $prId) {
            // Get the latest PR
            $purchaseRequest = PurchaseRequest::with(['user', 'approvals.approver'])
                ->latest()
                ->first();
        } else {
            $purchaseRequest = PurchaseRequest::with(['user', 'approvals.approver'])
                ->findOrFail($prId);
        }

        if (! $purchaseRequest) {
            $this->error('No purchase request found.');

            return;
        }

        $this->info("Testing QR code generation for PR: {$purchaseRequest->pr_number}");

        $qrCodeService = new QrCodeService;

        // Test requestor QR code
        $this->info("\n--- Requestor QR Code ---");
        $this->info("Requestor: {$purchaseRequest->user->name}");
        $this->info('Submitted: '.($purchaseRequest->submitted_at ? $purchaseRequest->submitted_at->format('Y-m-d H:i:s') : 'Not submitted'));

        try {
            $requestorQr = $qrCodeService->generateRequestorQrCodeDataUrl($purchaseRequest);
            $this->info('✓ Requestor QR code generated successfully');
            $this->info('Data URL length: '.strlen($requestorQr));
        } catch (\Exception $e) {
            $this->error('✗ Failed to generate requestor QR code: '.$e->getMessage());
        }

        // Test approver QR codes
        $this->info("\n--- Approver QR Codes ---");
        foreach ($purchaseRequest->approvals as $approval) {
            $this->info("Approver: {$approval->approver->name} ({$approval->approval_type})");
            $this->info("Status: {$approval->status}");
            $this->info('Responded: '.($approval->responded_at ? $approval->responded_at->format('Y-m-d H:i:s') : 'Not responded'));

            try {
                $approverQr = $qrCodeService->generateApproverQrCodeDataUrl($approval);
                $this->info('✓ Approver QR code generated successfully');
                $this->info('Data URL length: '.strlen($approverQr));
            } catch (\Exception $e) {
                $this->error('✗ Failed to generate approver QR code: '.$e->getMessage());
            }
            $this->info('---');
        }

        $this->info("\nQR code generation test completed!");
    }
}
