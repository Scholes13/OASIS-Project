<?php

namespace App\Services\Modules\Purchasing\PurchaseRequest;

use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Status transition logic for Purchase Requests.
 *
 * Owns submit, resubmit, void, and offline-approval transitions previously
 * inlined in PurchaseRequestService. Behavior preserved verbatim.
 */
class PrStatusTransitioner
{
    public function __construct(
        protected ApprovalWorkflowService $workflowService,
    ) {}

    /**
     * Submit an EXISTING DRAFT Purchase Request for approval.
     *
     * ⚠️ CURRENTLY UNUSED - Kept for future "Save as Draft" feature.
     *
     * @throws \Exception If PR not in submittable state
     */
    public function submit(PurchaseRequest $purchaseRequest): PurchaseRequest
    {
        if (! $purchaseRequest->canBeSubmitted()) {
            throw new \Exception('This purchase request cannot be submitted.');
        }

        return DB::transaction(function () use ($purchaseRequest) {
            $purchaseRequest->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            $this->workflowService->createWorkflow($purchaseRequest);

            return $purchaseRequest;
        });
    }

    /**
     * Resubmit a rejected Purchase Request (reset workflow).
     *
     * Preserves the original submitted_at timestamp so QR tokens stay valid.
     *
     * @throws \Exception If PR is not rejected
     */
    public function resubmit(PurchaseRequest $purchaseRequest): PurchaseRequest
    {
        if ($purchaseRequest->status !== 'rejected') {
            throw new \Exception('Only rejected purchase requests can be resubmitted.');
        }

        return DB::transaction(function () use ($purchaseRequest) {
            // Preserve original submitted_at timestamp (for QR token reusability)
            $originalSubmittedAt = $purchaseRequest->submitted_at;

            // Reset the workflow (delete old approvals)
            $this->workflowService->resetWorkflow($purchaseRequest);

            // Update PR status and timestamps
            // CRITICAL: Keep original submitted_at to ensure QR tokens remain valid
            $purchaseRequest->update([
                'status' => 'submitted',
                'submitted_at' => $originalSubmittedAt ?? now(),
                'rejected_at' => null,
            ]);

            // Create new approval workflow (will use preserved JSON if available)
            $this->workflowService->createWorkflow($purchaseRequest);

            return $purchaseRequest;
        });
    }

    /**
     * Void a Purchase Request.
     *
     * @throws \Exception If PR cannot be voided
     */
    public function void(PurchaseRequest $purchaseRequest, string $reason): PurchaseRequest
    {
        if (! $purchaseRequest->canBeVoided()) {
            throw new \Exception('This purchase request cannot be voided.');
        }

        $purchaseRequest->void(Auth::user(), $reason);

        return $purchaseRequest;
    }

    /**
     * Mark a Purchase Request as approved offline/manually.
     *
     * Used when the digital approval process is too slow and the user has
     * exported the PR for manual/offline approval. Marks the entire PR as
     * approved in one action (not step by step).
     *
     * @param  string|null  $notes  Optional notes explaining why offline approval was used
     * @param  string|null  $documentPath  Path to uploaded offline approval document
     * @param  string|null  $documentName  Original name of uploaded document
     */
    public function markAsOfflineApproved(
        PurchaseRequest $purchaseRequest,
        ?string $notes = null,
        ?string $documentPath = null,
        ?string $documentName = null,
    ): PurchaseRequest {
        return DB::transaction(function () use ($purchaseRequest, $notes, $documentPath, $documentName) {
            $user = Auth::user();

            // Update all pending approvals to approved (mark as offline)
            // Note: pr_approvals uses 'responded_at' not 'approved_at'
            $purchaseRequest->approvals()
                ->where('status', 'pending')
                ->update([
                    'status' => 'approved',
                    'responded_at' => now(),
                    'notes' => 'Approved offline/manually',
                ]);

            // Update the PR status to approved with offline approval info
            $updateData = [
                'status' => 'approved',
                'approved_at' => now(),
                'offline_approved_at' => now(),
                'offline_approved_by' => $user->id,
                'offline_approval_notes' => $notes,
            ];

            // Add document info if uploaded
            if ($documentPath) {
                $updateData['offline_approval_document_path'] = $documentPath;
                $updateData['offline_approval_document_name'] = $documentName;
            }

            $purchaseRequest->update($updateData);

            // Log activity
            activity()
                ->performedOn($purchaseRequest)
                ->causedBy($user)
                ->withProperties([
                    'action' => 'offline_approved',
                    'notes' => $notes,
                    'document_path' => $documentPath,
                    'previous_status' => $purchaseRequest->getOriginal('status'),
                ])
                ->log('PR marked as approved offline/manually');

            Log::info("PR #{$purchaseRequest->pr_number} marked as offline approved by user {$user->id}");

            return $purchaseRequest->fresh();
        });
    }
}
