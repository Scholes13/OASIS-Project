<?php

namespace App\Actions\Modules\Purchasing\PurchaseRequest;

use App\Http\Requests\Purchasing\StorePurchaseRequestRequest;
use App\Models\Core\User;
use App\Models\Modules\Purchasing\PurchaseRequest\PrItem;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Modules\Purchasing\PurchaseRequest\ApprovalWorkflowService;
use App\Services\Modules\Purchasing\PurchaseRequest\PurchaseRequestService;
use App\Services\Modules\Purchasing\PurchaseRequest\UniversalPRNumberingService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Create a new Purchase Request: number generation, file uploads, item creation,
 * workflow initialization, dashboard cache clear.
 *
 * Lifted verbatim from PurchaseRequestController::store() to preserve behavior.
 */
class CreatePurchaseRequestAction
{
    public function __construct(
        private PurchaseRequestService $purchaseRequestService,
        private ApprovalWorkflowService $approvalWorkflowService,
        private UniversalPRNumberingService $numberingService,
    ) {}

    /**
     * Execute the create flow.
     *
     * @return array{ok: true, purchase_request: PurchaseRequest}|array{ok: false, error: string}
     */
    public function execute(StorePurchaseRequestRequest $request, User $user): array
    {
        try {
            DB::beginTransaction();

            // Generate PR number
            $prNumber = $this->numberingService->generatePRNumber(
                $user,
                $request->business_unit_id,
                null,
                Carbon::parse($request->date_of_request)
            );

            // Handle supporting document upload
            [$supportingDocumentPath, $supportingDocumentName] = $this->storeSupportingDocument($request);

            // Create purchase request
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => $prNumber['formatted_number'],
                'business_unit_id' => $request->business_unit_id,
                'department_id' => $request->department_id,
                'category_id' => $request->category_id,
                'user_id' => $user->id,
                'sequence_id' => $prNumber['sequence_id'],
                'used_for' => $request->used_for,
                'date_of_request' => $request->date_of_request,
                'expected_date' => $request->expected_date,
                'designated_date' => $request->expected_date,
                'status' => 'submitted', // Directly submit (no draft step)
                'submitted_at' => now(),
                'currency' => $request->currency,
                'supporting_document_path' => $supportingDocumentPath,
                'supporting_document_name' => $supportingDocumentName,
                'last_modified_by' => $user->id,
            ]);

            // Create PR items
            $this->createItems($purchaseRequest, $request->items);

            // Update total amount
            $purchaseRequest->updateTotalAmount();

            // Create approval workflow
            $this->approvalWorkflowService->createWorkflowFromRequest(
                $purchaseRequest,
                $request->approval_workflow,
                $request->approval_notes
            );

            // Clear dashboard cache
            $this->purchaseRequestService->clearDashboardCache($purchaseRequest);

            DB::commit();

            return ['ok' => true, 'purchase_request' => $purchaseRequest];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create purchase request', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'ok' => false,
                'error' => 'Failed to create purchase request. Please try again or contact support.',
            ];
        }
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function storeSupportingDocument(StorePurchaseRequestRequest $request): array
    {
        if (! $request->hasFile('supporting_document')) {
            return [null, null];
        }

        $file = $request->file('supporting_document');

        return [
            $file->store('purchase-requests/supporting-documents', 'public'),
            $file->getClientOriginalName(),
        ];
    }

    /**
     * Persist PR items and their optional images.
     */
    private function createItems(PurchaseRequest $purchaseRequest, array $items): void
    {
        foreach ($items as $index => $itemData) {
            $imagePath = null;
            if (isset($itemData['image']) && $itemData['image'] instanceof UploadedFile) {
                $imagePath = $itemData['image']->store('purchase-requests/items', 'public');
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
