<?php

namespace App\Actions\Modules\Purchasing\StockRequest;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Modules\Purchasing\Admin\AdminTaskService;
use Illuminate\Support\Facades\DB;

class ProcessStockRequestGaReviewAction
{
    public function __construct(
        private AdminTaskService $adminTaskService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function approve(StockRequest $stockRequest, User $user, array $data): void
    {
        DB::transaction(function () use ($stockRequest, $user, $data) {
            $stockRequest = StockRequest::query()
                ->lockForUpdate()
                ->findOrFail($stockRequest->id);

            if ($stockRequest->status !== 'ga_review') {
                throw new \DomainException('Only stock requests waiting for GA review can be processed.');
            }

            $stockRequest->load('items');
            $reviewItems = collect($data['items'] ?? [])->keyBy('id');

            foreach ($stockRequest->items as $item) {
                $review = $reviewItems->get($item->id);

                if (! $review) {
                    throw new \DomainException('All items must be reviewed before GA approval.');
                }

                $warehouseQty = isset($review['warehouse_available_qty'])
                    ? (int) $review['warehouse_available_qty']
                    : null;
                $procurementQty = isset($review['procurement_quantity'])
                    ? (int) $review['procurement_quantity']
                    : null;
                $requestedQty = (int) $item->quantity;

                if ($warehouseQty === null || $procurementQty === null) {
                    throw new \DomainException('Warehouse and procurement quantities are required for every item.');
                }

                if ($warehouseQty > $requestedQty) {
                    throw new \DomainException('Warehouse quantity cannot exceed requested quantity.');
                }

                if ($procurementQty > $requestedQty) {
                    throw new \DomainException('Procurement quantity cannot exceed requested quantity.');
                }

                if (($warehouseQty + $procurementQty) !== $requestedQty) {
                    throw new \DomainException('Warehouse quantity and procurement quantity must match requested quantity.');
                }

                if ($review['ga_review_result'] === 'warehouse_stock'
                    && ($warehouseQty !== $requestedQty || $procurementQty !== 0)) {
                    throw new \DomainException('Warehouse stock items must be fulfilled entirely from warehouse stock.');
                }

                if ($review['ga_review_result'] === 'need_procurement' && $procurementQty <= 0) {
                    throw new \DomainException('Procurement items must include a procurement quantity.');
                }

                $item->update([
                    'ga_review_result' => $review['ga_review_result'],
                    'ga_review_note' => $review['ga_review_note'] ?? null,
                    'warehouse_available_qty' => $warehouseQty,
                ]);
            }

            $nextStatus = $stockRequest->items()
                ->where('ga_review_result', 'need_procurement')
                ->exists()
                    ? 'ready_for_purchasing'
                    : 'approved';

            $stockRequest->forceFill([
                'status' => $nextStatus,
                'ga_reviewed_at' => now(),
                'ga_reviewed_by' => $user->id,
                'ga_review_notes' => $data['ga_review_notes'] ?? null,
                'ga_rejected_reason' => null,
            ])->saveQuietly();

            if ($nextStatus === 'ready_for_purchasing') {
                $this->adminTaskService->createForStockRequest($stockRequest);
            }

            activity()
                ->performedOn($stockRequest)
                ->causedBy($user)
                ->withProperties([
                    'status' => $nextStatus,
                    'items' => $stockRequest->items()
                        ->get(['id', 'item_name', 'ga_review_result', 'warehouse_available_qty'])
                        ->toArray(),
                ])
                ->log('stock request GA review approved');
        });
    }

    public function reject(StockRequest $stockRequest, User $user, string $reason): void
    {
        DB::transaction(function () use ($stockRequest, $user, $reason) {
            $stockRequest = StockRequest::query()
                ->lockForUpdate()
                ->findOrFail($stockRequest->id);

            if ($stockRequest->status !== 'ga_review') {
                throw new \DomainException('Only stock requests waiting for GA review can be processed.');
            }

            $stockRequest->update([
                'status' => 'ga_rejected',
                'ga_reviewed_at' => now(),
                'ga_reviewed_by' => $user->id,
                'ga_review_notes' => null,
                'ga_rejected_reason' => $reason,
                'rejection_notes' => $reason,
            ]);

            activity()
                ->performedOn($stockRequest)
                ->causedBy($user)
                ->withProperties(['reason' => $reason])
                ->log('stock request GA review rejected');
        });
    }
}
