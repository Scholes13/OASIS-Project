<?php

namespace App\Actions\Modules\Purchasing\StockRequest;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\StockRequest\StockApproval;
use App\Models\Modules\Purchasing\StockRequest\StockItem;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Core\EmailNotificationService;
use App\Services\Modules\Purchasing\Shared\PurchasingFileStorage;
use App\Services\Modules\Purchasing\StockRequest\StockRequestApprovalWorkflowResolver;
use App\Services\Modules\Purchasing\StockRequest\StockRequestPostApprovalRouter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        private StockRequestApprovalWorkflowResolver $approvalWorkflowResolver,
        private StockRequestPostApprovalRouter $postApprovalRouter,
        private PurchasingFileStorage $fileStorage,
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
        $storedFilePaths = [];

        try {
            DB::beginTransaction();

            $oldOfflineDocumentPath = $stockRequest->offline_approval_document_path;
            $oldItemImagePaths = $stockRequest->items()->whereNotNull('image_path')->pluck('image_path')->all();
            [$offlineDocumentPath, $offlineDocumentName] = $this->resolveOfflineDocument($request, $stockRequest);
            if ($offlineDocumentPath !== $oldOfflineDocumentPath) {
                $storedFilePaths[] = $offlineDocumentPath;
            }
            $routesDirectlyToPurchasing = $stockRequest->submitted_at
                ? $stockRequest->routes_directly_to_purchasing
                : $this->approvalWorkflowResolver->routesDirectlyToPurchasing(
                    $stockRequest->business_unit_id,
                    $stockRequest->department_id,
                );

            // Update stock request
            $stockRequest->update([
                'purpose' => $request->purpose,
                'date_of_request' => $request->date_of_request,
                'expected_date' => $request->expected_date,
                'offline_approval_document_path' => $offlineDocumentPath,
                'offline_approval_document_name' => $offlineDocumentName,
                'routes_directly_to_purchasing' => $routesDirectlyToPurchasing,
                'skips_ga_review' => $stockRequest->submitted_at
                    ? $stockRequest->skips_ga_review
                    : $routesDirectlyToPurchasing,
                'last_modified_by' => $user->id,
            ]);

            // Delete existing items
            $stockRequest->items()->delete();

            // Create new items
            $this->createItems($stockRequest, $request->items, $storedFilePaths);

            $this->resetWorkflow($stockRequest);

            $approvalWorkflow = $this->approvalWorkflowResolver->resolve(
                $user,
                $stockRequest->business_unit_id,
                $stockRequest->department_id,
                $routesDirectlyToPurchasing,
            );

            $stockRequest->update([
                'status' => $approvalWorkflow === [] ? 'submitted' : 'in_approval',
                'submitted_at' => $stockRequest->submitted_at ?? now(),
                'ga_review_started_at' => null,
                'rejected_at' => null,
                'ga_rejected_reason' => null,
            ]);

            if ($approvalWorkflow !== []) {
                $this->createWorkflowFromRequest(
                    $stockRequest,
                    $approvalWorkflow,
                    $request->approval_notes ?? null
                );
            } else {
                $this->postApprovalRouter->route($stockRequest);
            }

            DB::commit();

            $replacedFilePaths = $oldItemImagePaths;
            if ($oldOfflineDocumentPath && $oldOfflineDocumentPath !== $offlineDocumentPath) {
                $replacedFilePaths[] = $oldOfflineDocumentPath;
            }
            $this->fileStorage->deleteSafely($replacedFilePaths, [
                'st_id' => $stockRequest->id,
                'context' => 'replaced',
            ]);

            return ['ok' => true, 'stock_request' => $stockRequest];

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
                $this->fileStorage->deleteSafely($storedFilePaths, [
                    'st_id' => $stockRequest->id,
                    'context' => 'rolled-back',
                ]);
            }

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

        $file = $request->file('offline_approval_document');

        return [$this->fileStorage->store($file, 'stock-requests/offline-approvals'), $file->getClientOriginalName()];
    }

    /**
     * Persist ST items and their optional images.
     */
    private function createItems(StockRequest $stockRequest, array $items, array &$storedFilePaths): void
    {
        foreach ($items as $index => $itemData) {
            $imagePath = null;
            if (isset($itemData['image']) && $itemData['image'] instanceof UploadedFile) {
                $imagePath = $this->fileStorage->store($itemData['image'], 'stock-requests/items');
                $storedFilePaths[] = $imagePath;
            }

            StockItem::create([
                'stock_request_id' => $stockRequest->id,
                'item_order' => $index + 1,
                'item_name' => $itemData['item_name'],
                'specifications' => $itemData['item_description'] ?? null,
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

            $approver = User::query()->find($step['approver_id']);
            $assignment = $approver?->activeBusinessUnits()
                ->where('business_unit_id', $stockRequest->business_unit_id)
                ->where('department_id', $stockRequest->department_id)
                ->with(['department:id,name,code', 'position:id,name'])
                ->first();

            StockApproval::create([
                'stock_request_id' => $stockRequest->id,
                'approver_id' => $step['approver_id'],
                'step_order' => $index + 1,
                'approval_type' => $step['task_type'] ?? 'approval',
                'status' => 'pending',
                'notes' => $notes,
                'metadata' => [
                    'approver_snapshot' => [
                        'id' => $approver?->id,
                        'name' => $approver?->name,
                        'email' => $approver?->email,
                        'department' => $assignment?->department?->name,
                        'department_code' => $assignment?->department?->code,
                        'position' => $assignment?->position?->name,
                    ],
                ],
            ]);
        }

        // Update stock request status to in_approval
        $stockRequest->update(['status' => 'in_approval']);

        $this->notifyPendingApprovers($stockRequest);
    }

    /**
     * Send notification to the first pending approver of a stock request.
     */
    private function notifyPendingApprovers(StockRequest $stockRequest): void
    {
        $stockRequest->approvals()
            ->where('status', 'pending')
            ->with('approver', 'stockRequest.user')
            ->orderBy('step_order')
            ->get()
            ->each(function (StockApproval $approval) use ($stockRequest) {
                if (! $approval->approver) {
                    return;
                }

                DB::afterCommit(function () use ($approval, $stockRequest) {
                    try {
                        $committedApproval = StockApproval::query()->findOrFail($approval->id);
                        $this->emailService->sendStApprovalRequested($committedApproval);

                        Log::info('Stock request approver notification sent', [
                            'st_number' => $stockRequest->st_number,
                            'approver_id' => $approval->approver_id,
                            'approver_name' => $approval->approver->name,
                            'step_order' => $approval->step_order,
                        ]);
                    } catch (\Throwable $exception) {
                        Log::error('Failed to send stock request approval notification', [
                            'approval_id' => $approval->id,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                });
            });
    }
}
