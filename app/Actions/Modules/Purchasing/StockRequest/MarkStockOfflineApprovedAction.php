<?php

namespace App\Actions\Modules\Purchasing\StockRequest;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Modules\Purchasing\Shared\PurchasingFileStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Mark a Stock Request as approved offline/manually.
 *
 * Used when digital approval is too slow and user has exported the ST
 * for manual/offline approval. Stores the offline-approval evidence
 * document and updates the ST status atomically.
 *
 * Lifted verbatim from StockRequestController::markOfflineApproved() try/catch block.
 */
class MarkStockOfflineApprovedAction
{
    public function __construct(private PurchasingFileStorage $fileStorage) {}

    /**
     * Execute the offline-approval flow.
     *
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function execute(Request $request, StockRequest $stockRequest, User $user): array
    {
        $documentPath = null;

        try {
            DB::beginTransaction();
            $stockRequest = StockRequest::query()->lockForUpdate()->findOrFail($stockRequest->id);

            if ($stockRequest->approvals()->where('status', 'pending')->exists()) {
                DB::rollBack();

                return [
                    'ok' => false,
                    'error' => 'Department approval must be completed before offline approval can be recorded.',
                ];
            }

            // Handle file upload
            $documentName = null;
            if ($request->hasFile('offline_approval_document')) {
                $file = $request->file('offline_approval_document');
                $documentName = $file->getClientOriginalName();
                $documentPath = $this->fileStorage->store(
                    $file,
                    'offline-approvals/stock-requests/'.$stockRequest->id,
                );
            }

            $previousStatus = $stockRequest->getOriginal('status');

            $stockRequest->update([
                'status' => 'approved',
                'approved_at' => now(),
                'offline_approved_at' => now(),
                'offline_approved_by' => $user->id,
                'offline_approval_notes' => $request->notes,
                'offline_approval_document_path' => $documentPath,
                'offline_approval_document_name' => $documentName,
            ]);

            // Log activity
            activity()
                ->performedOn($stockRequest)
                ->causedBy($user)
                ->withProperties([
                    'notes' => $request->notes,
                    'document_path' => $documentPath,
                    'previous_status' => $previousStatus,
                ])
                ->log('Stock request marked as offline approved');

            DB::commit();

            return ['ok' => true];

        } catch (\Exception $e) {
            DB::rollBack();
            if ($documentPath) {
                $this->fileStorage->deleteSafely($documentPath, [
                    'st_id' => $stockRequest->id,
                    'context' => 'rolled-back-offline-approval',
                ]);
            }

            Log::error('Failed to mark stock request as offline approved', [
                'st_id' => $stockRequest->id,
                'st_number' => $stockRequest->st_number,
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
