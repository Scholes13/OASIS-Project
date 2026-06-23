<?php

namespace App\Actions\Modules\Purchasing\StockRequest;

use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Core\EmailNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Resubmit a previously rejected Stock Request.
 *
 * Resets all approval steps back to pending, clears rejection state,
 * and notifies the first approver.
 *
 * Lifted verbatim from StockRequestController::resubmit() body.
 */
class ResubmitStockRequestAction
{
    public function __construct(
        private EmailNotificationService $emailService,
    ) {}

    /**
     * Execute the resubmit flow.
     *
     * @return array{ok: true, stock_request: StockRequest}
     */
    public function execute(StockRequest $stockRequest): array
    {
        // Reset all approval steps back to pending
        $stockRequest->approvals()->update([
            'status' => 'pending',
            'notes' => null,
            'responded_at' => null,
            'email_sent' => false,
            'email_sent_at' => null,
        ]);

        $stockRequest->items()->update([
            'ga_review_result' => 'pending_review',
            'ga_review_note' => null,
            'warehouse_available_qty' => null,
        ]);

        // Reset workflow status
        $stockRequest->update([
            'status' => 'in_approval',
            'submitted_at' => now(),
            'rejected_at' => null,
            'rejection_notes' => null,
            'ga_review_started_at' => null,
            'ga_reviewed_at' => null,
            'ga_reviewed_by' => null,
            'ga_review_notes' => null,
            'ga_rejected_reason' => null,
        ]);

        // Notify the first approver
        $this->notifyFirstApprover($stockRequest);

        return ['ok' => true, 'stock_request' => $stockRequest];
    }

    /**
     * Send notification to the first pending approver of a stock request.
     */
    private function notifyFirstApprover(StockRequest $stockRequest): void
    {
        $firstApproval = $stockRequest->approvals()
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->first();

        if (! $firstApproval || ! $firstApproval->approver) {
            return;
        }

        $firstApproval->loadMissing('approver', 'stockRequest.user');

        $this->emailService->sendStApprovalRequested($firstApproval);

        Log::info('Stock request first approver notification sent', [
            'st_number' => $stockRequest->st_number,
            'approver_id' => $firstApproval->approver_id,
            'approver_name' => $firstApproval->approver->name,
            'step_order' => $firstApproval->step_order,
        ]);
    }
}
