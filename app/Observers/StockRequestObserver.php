<?php

namespace App\Observers;

use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Modules\Purchasing\StockRequest\StockRequestPostApprovalRouter;
use Illuminate\Support\Facades\Log;

class StockRequestObserver
{
    public function __construct(
        private StockRequestPostApprovalRouter $postApprovalRouter,
    ) {}

    /**
     * Handle the StockRequest "updated" event.
     * Moves ST to GA review when approval workflow is complete.
     */
    public function updated(StockRequest $stockRequest): void
    {
        // Check if status changed to 'approved'
        if ($stockRequest->isDirty('status') && $stockRequest->status === 'approved') {
            try {
                $this->postApprovalRouter->route($stockRequest);

                Log::info('ST routed after department approval', [
                    'st_id' => $stockRequest->id,
                    'st_number' => $stockRequest->st_number,
                    'status' => $stockRequest->status,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to route approved ST', [
                    'st_id' => $stockRequest->id,
                    'st_number' => $stockRequest->st_number,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }
    }
}
