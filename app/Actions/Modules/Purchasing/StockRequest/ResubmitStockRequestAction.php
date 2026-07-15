<?php

namespace App\Actions\Modules\Purchasing\StockRequest;

use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Core\EmailNotificationService;
use App\Services\Modules\Purchasing\StockRequest\StockRequestPostApprovalRouter;
use Illuminate\Support\Facades\DB;
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
        private StockRequestPostApprovalRouter $postApprovalRouter,
    ) {}

    /**
     * Execute the resubmit flow.
     *
     * @return array{ok: true, stock_request: StockRequest}
     */
    public function execute(StockRequest $stockRequest): array
    {
        return DB::transaction(function () use ($stockRequest) {
            $stockRequest = StockRequest::query()
                ->lockForUpdate()
                ->findOrFail($stockRequest->id);

            if (! in_array($stockRequest->status, ['rejected', 'ga_rejected'], true)) {
                throw new \DomainException('Only rejected stock requests can be resubmitted.');
            }

            $returnsToGaReview = $stockRequest->status === 'ga_rejected';
            $routesDirectlyToPurchasing = $stockRequest->routes_directly_to_purchasing;

            // Reset all approval steps back to pending
            if (! $returnsToGaReview && ! $routesDirectlyToPurchasing) {
                $stockRequest->approvals()->update([
                    'status' => 'pending',
                    'notes' => null,
                    'responded_at' => null,
                    'email_sent' => false,
                    'email_sent_at' => null,
                ]);
            }

            $stockRequest->items()->update([
                'ga_review_result' => 'pending_review',
                'ga_review_note' => null,
                'warehouse_available_qty' => null,
            ]);

            // Reset workflow status
            $stockRequest->update([
                'status' => $routesDirectlyToPurchasing
                    ? 'submitted'
                    : ($returnsToGaReview ? 'ga_review' : 'in_approval'),
                'submitted_at' => now(),
                'rejected_at' => null,
                'rejection_notes' => null,
                'ga_review_started_at' => $returnsToGaReview && ! $routesDirectlyToPurchasing ? now() : null,
                'ga_reviewed_at' => null,
                'ga_reviewed_by' => null,
                'ga_review_notes' => null,
                'ga_rejected_reason' => null,
            ]);

            if ($routesDirectlyToPurchasing) {
                $this->postApprovalRouter->route($stockRequest);
            } elseif (! $returnsToGaReview) {
                DB::afterCommit(function () use ($stockRequest) {
                    try {
                        $this->notifyFirstApprover($stockRequest);
                    } catch (\Throwable $exception) {
                        Log::error('Failed to notify resubmitted stock request approver', [
                            'stock_request_id' => $stockRequest->id,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                });
            }

            return ['ok' => true, 'stock_request' => $stockRequest];
        });
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
