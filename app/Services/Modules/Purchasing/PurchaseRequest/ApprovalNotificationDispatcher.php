<?php

namespace App\Services\Modules\Purchasing\PurchaseRequest;

use App\Models\Modules\Purchasing\PurchaseRequest\PrApproval;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Core\EmailNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Approval-related email/queue dispatcher for Purchase Requests.
 *
 * Owns the queued email handoff for approval-requested,
 * approval-completed and approval-rejected events. Lifted
 * verbatim from ApprovalWorkflowService notify* helpers.
 */
class ApprovalNotificationDispatcher
{
    /**
     * Notify the next pending approver for a purchase request.
     *
     * Resolves the current approval from the PR. Public surface kept
     * intentionally permissive (accepting either a PR or an approval).
     */
    public function notifyNextApprover(PurchaseRequest|PrApproval $target): void
    {
        $purchaseRequest = $target instanceof PrApproval
            ? $target->purchaseRequest
            : $target;

        if (! $purchaseRequest) {
            return;
        }

        $nextApproval = $purchaseRequest->currentApproval();

        if (! $nextApproval) {
            return;
        }

        try {
            $emailService = app(EmailNotificationService::class);

            // Dispatch to queue untuk tidak blocking response
            dispatch(function () use ($emailService, $nextApproval, $purchaseRequest) {
                try {
                    $emailService->sendApprovalRequested($nextApproval);

                    Log::info('Approval notification sent successfully', [
                        'pr_number' => $purchaseRequest->pr_number,
                        'approver_id' => $nextApproval->approver_id,
                        'approver_email' => $nextApproval->approver?->email,
                        'step_order' => $nextApproval->step_order,
                        'due_date' => $nextApproval->due_date,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to send approval notification', [
                        'pr_number' => $purchaseRequest->pr_number,
                        'approver_email' => $nextApproval->approver?->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            })->afterResponse(); // Send after HTTP response

        } catch (\Exception $e) {
            Log::warning('Failed to queue approval notification', [
                'pr_number' => $purchaseRequest->pr_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Dispatch an event-shaped notification to the requester.
     *
     * Supported events: 'completed' (all approvals done) and
     * 'rejected' (any reject step).
     */
    public function notifyRequester(PurchaseRequest $purchaseRequest, string $event): void
    {
        match ($event) {
            'completed' => $this->notifyCompletion($purchaseRequest),
            'rejected' => $this->notifyRejection($purchaseRequest),
            default => null,
        };
    }

    /**
     * Notify completion of all approvals.
     */
    public function notifyCompletion(PurchaseRequest $purchaseRequest): void
    {
        try {
            $emailService = app(EmailNotificationService::class);

            // Dispatch to queue untuk tidak blocking response
            dispatch(function () use ($emailService, $purchaseRequest) {
                try {
                    $emailService->sendApprovalCompleted($purchaseRequest);

                    Log::info('PR approval completion notification sent successfully', [
                        'pr_number' => $purchaseRequest->pr_number,
                        'requestor_id' => $purchaseRequest->user_id,
                        'requestor_email' => $purchaseRequest->user?->email,
                        'approved_at' => $purchaseRequest->approved_at,
                        'total_amount' => $purchaseRequest->total_amount,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to send completion notification', [
                        'pr_number' => $purchaseRequest->pr_number,
                        'requestor_email' => $purchaseRequest->user?->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            })->afterResponse(); // Send after HTTP response

        } catch (\Exception $e) {
            Log::warning('Failed to queue completion notification', [
                'pr_number' => $purchaseRequest->pr_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify rejection.
     */
    public function notifyRejection(PurchaseRequest $purchaseRequest): void
    {
        $rejectedApproval = $purchaseRequest->approvals()
            ->where('status', 'rejected')
            ->orderBy('responded_at', 'desc')
            ->first();

        if (! $rejectedApproval) {
            return;
        }

        try {
            $emailService = app(EmailNotificationService::class);

            // Dispatch to queue untuk tidak blocking response
            dispatch(function () use ($emailService, $rejectedApproval, $purchaseRequest) {
                try {
                    $emailService->sendApprovalRejected($rejectedApproval);

                    Log::info('PR rejection notification sent successfully', [
                        'pr_number' => $purchaseRequest->pr_number,
                        'requestor_id' => $purchaseRequest->user_id,
                        'requestor_email' => $purchaseRequest->user?->email,
                        'rejected_by' => $rejectedApproval->approver?->email,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to send rejection notification', [
                        'pr_number' => $purchaseRequest->pr_number,
                        'requestor_email' => $purchaseRequest->user?->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            })->afterResponse(); // Send after HTTP response

        } catch (\Exception $e) {
            Log::warning('Failed to queue rejection notification', [
                'pr_number' => $purchaseRequest->pr_number,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
