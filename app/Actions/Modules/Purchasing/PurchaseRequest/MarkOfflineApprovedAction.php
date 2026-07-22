<?php

namespace App\Actions\Modules\Purchasing\PurchaseRequest;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Modules\Purchasing\PurchaseRequest\PurchaseRequestService;
use App\Services\Modules\Purchasing\Shared\PurchasingFileStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Mark a Purchase Request as approved offline/manually.
 *
 * Used when digital approval is too slow and user has exported the PR
 * for manual/offline approval. Stores the offline-approval evidence
 * document and delegates the status mutation to PurchaseRequestService.
 *
 * Lifted verbatim from PurchaseRequestController::markOfflineApproved().
 */
class MarkOfflineApprovedAction
{
    public function __construct(
        private PurchaseRequestService $purchaseRequestService,
        private PurchasingFileStorage $fileStorage,
    ) {}

    /**
     * Execute the offline-approval flow.
     *
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function execute(Request $request, PurchaseRequest $purchaseRequest, User $user): array
    {
        $documentPath = null;

        try {
            // Handle file upload
            $documentName = null;
            if ($request->hasFile('offline_approval_document')) {
                $file = $request->file('offline_approval_document');
                $documentName = $file->getClientOriginalName();
                $documentPath = $this->fileStorage->store(
                    $file,
                    'offline-approvals/purchase-requests/'.$purchaseRequest->id,
                );
            }

            $this->purchaseRequestService->markAsOfflineApproved(
                $purchaseRequest,
                $request->notes,
                $documentPath,
                $documentName
            );

            return ['ok' => true];

        } catch (\Exception $e) {
            if ($documentPath) {
                $this->fileStorage->deleteSafely($documentPath, [
                    'pr_id' => $purchaseRequest->id,
                    'context' => 'rolled-back-offline-approval',
                ]);
            }

            Log::error('Failed to mark purchase request as offline approved', [
                'pr_id' => $purchaseRequest->id,
                'pr_number' => $purchaseRequest->pr_number,
                'user_id' => $user->id,
                'notes' => $request->notes,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'ok' => false,
                'error' => 'Failed to mark as offline approved. Please try again or contact support.',
            ];
        }
    }
}
