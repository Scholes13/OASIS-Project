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
    ) {}

    /**
     * Execute the create flow.
     *
     * @return array{ok: true, stock_request: StockRequest}|array{ok: false, error: string}
     */
    public function execute(\App\Http\Requests\Purchasing\StoreStockRequestRequest $request, User $user): array
    {
        try {
            DB::beginTransaction();

            // Generate ST number
            $stNumber = $this->generateSTNumber($request->business_unit_id, $request->date_of_request);

            // Handle offline approval document upload
            [$offlineDocumentPath, $offlineDocumentName] = $this->storeOfflineDocument($request);

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
                'status' => 'submitted', // Directly submit (no draft step)
                'submitted_at' => now(),
                'offline_approval_document_path' => $offlineDocumentPath,
                'offline_approval_document_name' => $offlineDocumentName,
                'last_modified_by' => $user->id,
            ]);

            // Create ST items
            $this->createItems($stockRequest, $request->items);

            // Create approval workflow + notify first approver
            $this->createWorkflowFromRequest(
                $stockRequest,
                $request->approval_workflow,
                $request->approval_notes ?? null
            );

            DB::commit();

            return ['ok' => true, 'stock_request' => $stockRequest];

        } catch (\Exception $e) {
            DB::rollBack();

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
