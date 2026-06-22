<?php

namespace App\Actions\Modules\Purchasing\PurchaseRequest;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Core\EmailNotificationService;
use App\Services\Modules\Purchasing\PurchaseRequest\PurchaseRequestService;
use Illuminate\Support\Facades\Log;

/**
 * Side-effect actions for Purchase Request that don't fit a single
 * write/update flow: void (with reason) and resending the current
 * approval email. Behavior preserved verbatim from the controller.
 */
class PurchaseRequestSideEffectsAction
{
    public function __construct(
        private PurchaseRequestService $purchaseRequestService,
    ) {}

    /**
     * Void a purchase request with a reason. Caller is responsible for
     * authorization checks; this action only performs the mutation.
     *
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function void(PurchaseRequest $purchaseRequest, User $user, string $reason): array
    {
        try {
            $this->purchaseRequestService->voidPurchaseRequest($purchaseRequest, $reason);

            return ['ok' => true];
        } catch (\Exception $e) {
            Log::error('Failed to void purchase request', [
                'pr_id' => $purchaseRequest->id,
                'pr_number' => $purchaseRequest->pr_number,
                'user_id' => $user->id,
                'reason' => $reason,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'ok' => false,
                'error' => 'Failed to void purchase request. Please try again or contact support.',
            ];
        }
    }

    /**
     * Resend the current approval email to the pending approver.
     *
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function resendApprovalEmail(PurchaseRequest $purchaseRequest, User $user): array
    {
        $currentApproval = $purchaseRequest->currentApproval();
        if (! $currentApproval || $currentApproval->status !== 'pending') {
            return [
                'ok' => false,
                'error' => 'No active approval step found. The approval workflow may need to be rebuilt. Please contact a purchasing administrator.',
            ];
        }

        try {
            $emailService = app(EmailNotificationService::class);
            $emailSent = $emailService->sendApprovalRequested($currentApproval);

            if (! $emailSent) {
                return [
                    'ok' => false,
                    'error' => 'Failed to resend approval email. Please check notification settings and try again.',
                ];
            }

            return ['ok' => true];
        } catch (\Exception $e) {
            Log::error('Failed to resend purchase request approval email', [
                'pr_id' => $purchaseRequest->id,
                'pr_number' => $purchaseRequest->pr_number,
                'approval_id' => $currentApproval->id,
                'requestor_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'ok' => false,
                'error' => 'Failed to resend approval email. Please try again or contact support.',
            ];
        }
    }
}
