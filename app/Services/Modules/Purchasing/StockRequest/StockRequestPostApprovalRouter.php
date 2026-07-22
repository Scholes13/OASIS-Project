<?php

namespace App\Services\Modules\Purchasing\StockRequest;

use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Modules\Purchasing\Admin\AdminTaskService;
use Illuminate\Support\Facades\DB;

class StockRequestPostApprovalRouter
{
    public function __construct(
        private AdminTaskService $adminTaskService,
    ) {}

    public function route(StockRequest $stockRequest): void
    {
        DB::transaction(function () use ($stockRequest) {
            if (! $stockRequest->skips_ga_review) {
                $stockRequest->forceFill([
                    'status' => 'ga_review',
                    'ga_review_started_at' => now(),
                ])->saveQuietly();

                return;
            }

            $stockRequest->forceFill([
                'status' => 'ready_for_purchasing',
                'ga_review_started_at' => null,
                'ga_reviewed_at' => null,
                'ga_reviewed_by' => null,
                'ga_review_notes' => null,
            ])->saveQuietly();

            $stockRequest->items()->update([
                'ga_review_result' => 'need_procurement',
                'warehouse_available_qty' => 0,
            ]);

            $this->adminTaskService->createForStockRequest($stockRequest);
        });
    }
}
