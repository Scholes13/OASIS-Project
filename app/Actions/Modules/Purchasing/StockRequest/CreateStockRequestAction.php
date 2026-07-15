<?php

namespace App\Actions\Modules\Purchasing\StockRequest;

use App\Models\Core\BusinessUnit;
use App\Models\Core\NumberingModule;
use App\Models\Core\User;
use App\Models\Modules\Purchasing\StockRequest\StockApproval;
use App\Models\Modules\Purchasing\StockRequest\StockItem;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Core\EmailNotificationService;
use App\Services\Core\NumberingService;
use App\Services\Modules\Purchasing\Shared\PurchasingFileStorage;
use App\Services\Modules\Purchasing\StockRequest\StockRequestApprovalWorkflowResolver;
use App\Services\Modules\Purchasing\StockRequest\StockRequestPostApprovalRouter;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Create a new Stock Request: number generation, file uploads, item creation,
 * workflow initialization, first-approver notification.
 *
 * Lifted verbatim from StockRequestController::store() to preserve behavior.
 * Also folds in the local helpers generateSTNumber, createWorkflowFromRequest,
 * and notifyFirstApprover that were previously private to the controller.
 */
class CreateStockRequestAction
{
    public function __construct(
        private NumberingService $numberingService,
        private EmailNotificationService $emailService,
        private StockRequestApprovalWorkflowResolver $approvalWorkflowResolver,
        private StockRequestPostApprovalRouter $postApprovalRouter,
        private PurchasingFileStorage $fileStorage,
    ) {}

    /**
     * Execute the create flow.
     *
     * @return array{ok: true, stock_request: StockRequest}|array{ok: false, error: string}
     */
    public function execute(\App\Http\Requests\Purchasing\StoreStockRequestRequest $request, User $user): array
    {
        $storedFilePaths = [];

        try {
            DB::beginTransaction();

            // Generate ST number
            $stNumber = $this->generateSTNumber($request->business_unit_id, $request->date_of_request);

            // Handle offline approval document upload
            [$offlineDocumentPath, $offlineDocumentName] = $this->storeOfflineDocument($request);
            if ($offlineDocumentPath) {
                $storedFilePaths[] = $offlineDocumentPath;
            }

            $routesDirectlyToPurchasing = $this->approvalWorkflowResolver->routesDirectlyToPurchasing(
                (int) $request->business_unit_id,
                (int) $request->department_id,
            );
            $approvalWorkflow = $this->approvalWorkflowResolver->resolve(
                $user,
                (int) $request->business_unit_id,
                (int) $request->department_id,
                $routesDirectlyToPurchasing,
            );
            $initialStatus = $approvalWorkflow === [] ? 'submitted' : 'in_approval';

            // Create stock request
            $stockRequest = StockRequest::create([
                'st_number' => $stNumber['formatted_number'],
                'business_unit_id' => $request->business_unit_id,
                'department_id' => $request->department_id,
                'user_id' => $user->id,
                'sequence_id' => $stNumber['sequence_id'],
                'purpose' => $request->purpose,
                'date_of_request' => $request->date_of_request,
                'expected_date' => $request->expected_date,
                'status' => $initialStatus,
                'routes_directly_to_purchasing' => $routesDirectlyToPurchasing,
                'skips_ga_review' => $routesDirectlyToPurchasing,
                'submitted_at' => now(),
                'ga_review_started_at' => null,
                'offline_approval_document_path' => $offlineDocumentPath,
                'offline_approval_document_name' => $offlineDocumentName,
                'last_modified_by' => $user->id,
            ]);

            // Create ST items
            $this->createItems($stockRequest, $request->items, $storedFilePaths);

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

            return ['ok' => true, 'stock_request' => $stockRequest];

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
                $this->fileStorage->deleteSafely($storedFilePaths, [
                    'user_id' => $user->id,
                    'context' => 'rolled-back-stock-create',
                ]);
            }

            Log::error('Failed to create stock request', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'ok' => false,
                'error' => 'Failed to create stock request. Please try again or contact support.',
            ];
        }
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function storeOfflineDocument(\App\Http\Requests\Purchasing\StoreStockRequestRequest $request): array
    {
        if (! $request->hasFile('offline_approval_document')) {
            return [null, null];
        }

        $file = $request->file('offline_approval_document');

        return [$this->fileStorage->store($file, 'stock-requests/offline-approvals'), $file->getClientOriginalName()];
    }

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
     * Generate ST number using numbering service.
     */
    private function generateSTNumber(int $businessUnitId, string $dateOfRequest): array
    {
        $date = Carbon::parse($dateOfRequest);

        $businessUnit = BusinessUnit::find($businessUnitId);
        if (! $businessUnit) {
            throw new \Exception('Business unit not found');
        }

        // Ensure ST numbering module exists
        $moduleCode = 'ST';
        NumberingModule::firstOrCreate(
            [
                'business_unit_id' => $businessUnit->id,
                'module_code' => $moduleCode,
            ],
            [
                'module_name' => 'Stock Request',
                'format_pattern' => 'ST.{BU_CODE}/{YYYYMM}/{SEQUENCE}',
                'config' => [
                    'sequence_padding' => 3,
                    'max_number' => 999,
                    'reset_annually' => true,
                    'reset_monthly' => false,
                    'cross_department' => true,
                    'shared_sequence' => true,
                ],
                'is_active' => true,
            ]
        );

        // Generate sequence number
        $result = $this->numberingService->generateNumber(
            $businessUnit->id,
            $moduleCode,
            null, // No department separation
            $date->year,
            null  // No monthly reset
        );

        // Format the ST number
        $result['formatted_number'] = sprintf(
            'ST.%s/%d%02d/%03d',
            $businessUnit->code,
            $date->year,
            $date->month,
            $result['sequence_number']
        );

        return $result;
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
