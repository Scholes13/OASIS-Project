<?php

namespace App\Actions\Modules\Purchasing\StockRequest;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\StockRequest\StockApproval;
use App\Models\Modules\Purchasing\StockRequest\StockItem;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Core\EmailNotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Update an existing Stock Request: replace items, update fields, reset and
 * recreate approval workflow.
 *
 * Lifted verbatim from StockRequestController::update() to preserve behavior.
 */
class UpdateStockRequestAction
{
    public function __construct(
        private EmailNotificationService $emailService,
    ) {}

    /**
     * Execute the update flow.
     *
     * @return array{ok: true, stock_request: StockRequest}|array{ok: false, error: string}
     */
    public function execute(
        \App\Http\Requests\Purchasing\StoreStockRequestRequest $request,
        StockRequest $stockRequest,
        User $user
    ): array {
        try {
            DB::beginTransaction();

            [$offlineDocumentPath, $offlineDocumentName] = $this->resolveOfflineDocument($request, $stockRequest);

            // Update stock request
            $stockRequest->update([
                'purpose' => $request->purpose,
                'date_of_request' => $request->date_of_request,
                'expected_date' => $request->expected_date,
                'offline_approval_document_path' => $offlineDocumentPath,
                'offline_approval_document_name' => $offlineDocumentName,
                'last_modified_by' => $user->id,
            ]);

            // Delete existing items
            $stockRequest->items()->delete();

            // Create new items
            $this->createItems($stockRequest, $request->items);

            // Reset and recreate approval workflow
            $this->resetWorkflow($stockRequest);
            $this->createWorkflowFromRequest(
                $stockRequest,
                $request->approval_workflow,
                $request->approval_notes ?? null
            );

            // Update status to submitted
            $stockRequest->update([
                'status' => 'submitted',
                'submitted_at' => $stockRequest->submitted_at ?? now(), // Preserve original if exists
                'rejected_at' => null,
            ]);

            DB::commit();

            return ['ok' => true, 'stock_request' => $stockRequest];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update stock request', [
                'st_id' => $stockRequest->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'ok' => false,
                'error' => 'Failed to update stock request. Please try again or contact support.',
            ];
        }
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveOfflineDocument(
        \App\Http\Requests\Purchasing\StoreStockRequestRequest $request,
        StockRequest $stockRequest,
    ): array {
        $offlineDocumentPath = $stockRequest->offline_approval_document_path;
        $offlineDocumentName = $stockRequest->offline_approval_document_name;

        if (! $request->hasFile('offline_approval_document')) {
            return [$offlineDocumentPath, $offlineDocumentName];
        }

        // Delete old document if exists
        if ($offlineDocumentPath) {
            Storage::disk('public')->delete($offlineDocumentPath);
        }

        $file = $request->file('offline_approval_document');

        return [
            $file->store('stock-requests/offline-approvals', 'public'),
            $file->getClientOriginalName(),
        ];
    }

    /**
     * Persist ST items and their optional images.
     */
    private function createItems(StockRequest $stockRequest, array $items): void
    {
        foreach ($items as $index => $itemData) {
            $imagePath = null;
            if (isset($itemData['image']) && $itemData['image'] instanceof UploadedFile) {
                $imagePath = $itemData['image']->store('stock-requests/items', 'public');
            }

            StockItem::create([
                'stock_request_id' => $stockRequest->id,
                'item_order' => $index + 1,
                'item_name' => $itemData['item_name'],
                'item_description' => $itemData['item_description'] ?? null,
                'quantity' => $itemData['quantity'],
                'unit' => $itemData['unit'],
                'image_path' => $imagePath,
            ]);
        }
    }

    /**
     * Reset approval workflow.
     */
    private function resetWorkflow(StockRequest $stockRequest): void
    {
        // Delete all existing approvals
        $stockRequest->approvals()->delete();

        // Reset approval-related fields
        $stockRequest->update([
            'status' => 'submitted',
            'approved_at' => null,
            'rejected_at' => null,
            'rejection_notes' => null,
        ]);
    }

    /**
     * Create approval workflow records and notify the first approver.
     */
    private function createWorkflowFromRequest(StockRequest $stockRequest, array $approvalWorkflow, ?string $notes): void
    {
        foreach ($approvalWorkflow as $index => $step) {
            // Block self-approval
            if ((int) $step['approver_id'] === (int) $stockRequest->user_id) {
                throw new \Exception('Request creator cannot be assigned as an approver.');
            }

            StockApproval::create([
                'stock_request_id' => $stockRequest->id,
                'approver_id' => $step['approver_id'],
                'step_order' => $index + 1,
                'approval_type' => $step['task_type'] ?? 'approval',
                'status' => 'pending',
                'notes' => $notes,
            ]);
        }

        // Update stock request status to in_approval
        $stockRequest->update(['status' => 'in_approval']);

        // Send notification to the first approver
        $this->notifyFirstApprover($stockRequest);
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
