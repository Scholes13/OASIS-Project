<?php

namespace App\Observers;

use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use Illuminate\Support\Facades\Log;

class StockRequestObserver
{
    /**
     * Handle the StockRequest "updated" event.
     * Moves ST to GA review when approval workflow is complete.
     */
    public function updated(StockRequest $stockRequest): void
    {
        // Check if status changed to 'approved'
        if ($stockRequest->isDirty('status') && $stockRequest->status === 'approved') {
            try {
                $stockRequest->forceFill([
                    'status' => 'ga_review',
                    'ga_review_started_at' => now(),
                ])->saveQuietly();

                Log::info('ST moved to GA review', [
                    'st_id' => $stockRequest->id,
                    'st_number' => $stockRequest->st_number,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create admin task for approved ST', [
                    'st_id' => $stockRequest->id,
                    'st_number' => $stockRequest->st_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
