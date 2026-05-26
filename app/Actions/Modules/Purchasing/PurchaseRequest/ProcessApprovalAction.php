<?php

namespace App\Actions\Modules\Purchasing\PurchaseRequest;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Modules\Purchasing\PurchaseRequest\ApprovalWorkflowService;
use App\Services\Modules\Purchasing\PurchaseRequest\PurchaseRequestService;
use Illuminate\Support\Facades\Log;

/**
 * Approve or reject a Purchase Request approval step.
 *
 * Wraps ApprovalWorkflowService::processApproval with the controller's
 * existing try/catch + dashboard cache clear contract. Behavior preserved
 * verbatim so approve() and reject() responses remain identical.
 */
class ProcessApprovalAction
{
    public function __construct(
        private ApprovalWorkflowService $approvalWorkflowService,
        private PurchaseRequestService $purchaseRequestService,
    ) {}

    /**
     * Execute the approval step. $action must be 'approved' or 'rejected'.
     *
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function execute(
        PurchaseRequest $purchaseRequest,
        User $user,
        string $action,
        ?string $notes,
    ): array {
        // Get current approval for this user (the controller has already
        // validated that the user is the assigned approver, but we re-resolve
        // here defensively in case the chain has shifted between validation
        // and execution).
        $currentApproval = $purchaseRequest->currentApproval();

        if (! $currentApproval || $currentApproval->approver_id !== $user->id) {
            return [
                'ok' => false,
                'error' => $action === 'approved'
                    ? 'You are not authorized to approve this purchase request at this step.'
                    : 'You are not authorized to reject this purchase request at this step.',
            ];
        }

        try {
            $this->approvalWorkflowService->processApproval(
                $currentApproval,
                $action,
                $notes,
            );

            $this->purchaseRequestService->clearDashboardCache($purchaseRequest);

            return ['ok' => true];

        } catch (\Exception $e) {
            $verb = $action === 'approved' ? 'approve' : 'reject';

            Log::error("Failed to {$verb} purchase request", [
                'pr_id' => $purchaseRequest->id,
                'pr_number' => $purchaseRequest->pr_number,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'ok' => false,
                'error' => "Failed to {$verb} purchase request. Please try again or contact support.",
            ];
        }
    }
}
