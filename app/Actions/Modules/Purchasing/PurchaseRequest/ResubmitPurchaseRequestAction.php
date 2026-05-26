<?php

namespace App\Actions\Modules\Purchasing\PurchaseRequest;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Modules\Purchasing\PurchaseRequest\PurchaseRequestService;
use Illuminate\Support\Facades\Log;

/**
 * Resubmit a previously rejected Purchase Request.
 *
 * Delegates the status/approval reset to PurchaseRequestService::resubmitPurchaseRequest.
 *
 * Lifted verbatim from PurchaseRequestController::resubmit() try/catch block.
 */
class ResubmitPurchaseRequestAction
{
    public function __construct(
        private PurchaseRequestService $purchaseRequestService,
    ) {}

    /**
     * Execute the resubmit flow.
     *
     * @return array{ok: true, purchase_request: PurchaseRequest}|array{ok: false, error: string}
     */
    public function execute(PurchaseRequest $purchaseRequest, User $user): array
    {
        try {
            $purchaseRequest = $this->purchaseRequestService
                ->resubmitPurchaseRequest($purchaseRequest);

            return ['ok' => true, 'purchase_request' => $purchaseRequest];

        } catch (\Exception $e) {
            Log::error('Failed to resubmit purchase request', [
                'pr_id' => $purchaseRequest->id,
                'pr_number' => $purchaseRequest->pr_number,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'ok' => false,
                'error' => 'Failed to resubmit purchase request. Please try again or contact support.',
            ];
        }
    }
}
