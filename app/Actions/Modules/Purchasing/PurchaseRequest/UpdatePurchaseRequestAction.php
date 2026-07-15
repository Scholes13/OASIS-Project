<?php

namespace App\Actions\Modules\Purchasing\PurchaseRequest;

use App\Http\Requests\Purchasing\StorePurchaseRequestRequest;
use App\Models\Core\User;
use App\Models\Modules\Purchasing\PurchaseRequest\PrItem;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Modules\Purchasing\PurchaseRequest\ApprovalWorkflowService;
use App\Services\Modules\Purchasing\PurchaseRequest\PurchaseRequestService;
use App\Services\Modules\Purchasing\Shared\PurchasingFileStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Update an existing Purchase Request: replace items, update fields, reset and
 * recreate approval workflow, clear dashboard cache.
 *
 * Lifted verbatim from PurchaseRequestController::update() to preserve behavior.
 */
class UpdatePurchaseRequestAction
{
    public function __construct(
        private PurchaseRequestService $purchaseRequestService,
        private ApprovalWorkflowService $approvalWorkflowService,
        private PurchasingFileStorage $fileStorage,
    ) {}

    /**
     * Execute the update flow.
     *
     * @return array{ok: true, purchase_request: PurchaseRequest}|array{ok: false, error: string}
     */
    public function execute(
        StorePurchaseRequestRequest $request,
        PurchaseRequest $purchaseRequest,
        User $user
    ): array {
        $storedFiles = [];

        try {
            DB::beginTransaction();

            // Handle supporting document upload
            [$supportingDocumentPath, $supportingDocumentName] = $this->resolveSupportingDocument(
                $request,
                $purchaseRequest,
                $storedFiles,
            );
            $replacedFiles = $purchaseRequest->items()
                ->whereNotNull('image_path')
                ->pluck('image_path')
                ->all();
            if ($purchaseRequest->supporting_document_path
                && $supportingDocumentPath !== $purchaseRequest->supporting_document_path) {
                $replacedFiles[] = $purchaseRequest->supporting_document_path;
            }

            // Update purchase request
            $purchaseRequest->update([
                'category_id' => $request->category_id,
                'used_for' => $request->used_for,
                'date_of_request' => $request->date_of_request,
                'expected_date' => $request->expected_date,
                'designated_date' => $request->expected_date,
                'currency' => $request->currency,
                'supporting_document_path' => $supportingDocumentPath,
                'supporting_document_name' => $supportingDocumentName,
                'last_modified_by' => $user->id,
            ]);

            // Delete existing items
            $purchaseRequest->items()->delete();

            // Create new items
            $this->createItems($purchaseRequest, $request->items, $storedFiles);

            // Update total amount
            $purchaseRequest->updateTotalAmount();

            // Reset workflow before either keeping draft or resubmitting.
            $this->approvalWorkflowService->resetWorkflow($purchaseRequest);
            if ($request->isDraft()) {
                $purchaseRequest->update(['status' => 'draft', 'submitted_at' => null, 'rejected_at' => null]);
            } else {
                $this->approvalWorkflowService->createWorkflowFromRequest(
                    $purchaseRequest,
                    $request->approval_workflow,
                    $request->approval_notes
                );
                $purchaseRequest->update([
                    'submitted_at' => $purchaseRequest->submitted_at ?? now(),
                    'rejected_at' => null,
                ]);
            }

            // Clear dashboard cache
            $this->purchaseRequestService->clearDashboardCache($purchaseRequest);
            DB::afterCommit(fn () => $this->fileStorage->deleteSafely($replacedFiles, [
                'pr_id' => $purchaseRequest->id,
                'context' => 'replaced',
            ]));

            DB::commit();

            return ['ok' => true, 'purchase_request' => $purchaseRequest];

        } catch (\Exception $e) {
            DB::rollBack();
            $this->fileStorage->deleteSafely($storedFiles, [
                'pr_id' => $purchaseRequest->id,
                'context' => 'rolled-back',
            ]);

            Log::error('Failed to update purchase request', [
                'pr_id' => $purchaseRequest->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'ok' => false,
                'error' => 'Failed to update purchase request. Please try again or contact support.',
            ];
        }
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveSupportingDocument(
        StorePurchaseRequestRequest $request,
        PurchaseRequest $purchaseRequest,
        array &$storedFiles,
    ): array {
        $supportingDocumentPath = $purchaseRequest->supporting_document_path;
        $supportingDocumentName = $purchaseRequest->supporting_document_name;

        if (! $request->hasFile('supporting_document')) {
            return [$supportingDocumentPath, $supportingDocumentName];
        }

        $file = $request->file('supporting_document');
        $path = $this->fileStorage->store($file, 'purchase-requests/supporting-documents');
        $storedFiles[] = $path;

        return [$path, $file->getClientOriginalName()];
    }

    /**
     * Persist PR items and their optional images.
     */
    private function createItems(PurchaseRequest $purchaseRequest, array $items, array &$storedFiles): void
    {
        foreach ($items as $index => $itemData) {
            $imagePath = null;
            if (isset($itemData['image']) && $itemData['image'] instanceof UploadedFile) {
                $imagePath = $this->fileStorage->store($itemData['image'], 'purchase-requests/items');
                $storedFiles[] = $imagePath;
            }

            PrItem::create([
                'purchase_request_id' => $purchaseRequest->id,
                'item_order' => $index + 1,
                'item_name' => $itemData['item_name'],
                'brand_name' => $itemData['brand_name'] ?? null,
                'item_description' => $itemData['item_description'] ?? null,
                'supplier_name' => $itemData['supplier_name'] ?? null,
                'quantity' => $itemData['quantity'],
                'unit' => $itemData['unit'],
                'unit_price' => $itemData['unit_price'],
                'currency' => $itemData['currency'],
                'expense_department_id' => $itemData['expense_department_id'],
                'image_path' => $imagePath,
            ]);
        }
    }
}
