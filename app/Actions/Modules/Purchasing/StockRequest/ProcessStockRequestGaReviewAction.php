<?php

namespace App\Actions\Modules\Purchasing\StockRequest;

use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\Purchasing\Admin\AdminTask;
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
            $reviewItems = collect($data['items'] ?? [])->keyBy('id');

            foreach ($stockRequest->items as $item) {
                $review = $reviewItems->get($item->id);

                if (! $review) {
                    throw new \DomainException('All items must be reviewed before GA approval.');
                }

                $warehouseQty = $review['warehouse_available_qty'] ?? null;

                $procurementQty = $review['procurement_quantity'] ?? null;

                if ($warehouseQty !== null && $warehouseQty > $item->quantity) {
                    throw new \DomainException('Warehouse quantity cannot exceed requested quantity.');
                }

                if ($procurementQty !== null && $procurementQty > $item->quantity) {
                    throw new \DomainException('Procurement quantity cannot exceed requested quantity.');
                }

                if ($warehouseQty !== null && $procurementQty !== null && ($warehouseQty + $procurementQty) !== $item->quantity) {
                    throw new \DomainException('Warehouse quantity and procurement quantity must match requested quantity.');
                }

                $quantity = $item->quantity;
                $total = $item->total;

                if ($review['ga_review_result'] === 'need_procurement') {
                    $quantity = $procurementQty ?? max($item->quantity - (int) $warehouseQty, 0);
                    $total = $item->price * $quantity;
                }

                $item->update([
                    'quantity' => $quantity,
                    'total' => $total,
                    'ga_review_result' => $review['ga_review_result'],
                    'ga_review_note' => $review['ga_review_note'] ?? null,
                    'warehouse_available_qty' => $warehouseQty,
                ]);
            }

            $nextStatus = $stockRequest->items()
                ->where('ga_review_result', 'need_procurement')
                ->where('quantity', '>', 0)
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
                $this->createPurchasingTask($stockRequest);
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

    private function createPurchasingTask(StockRequest $stockRequest): void
    {
        $department = Department::query()
            ->where('business_unit_id', $stockRequest->business_unit_id)
            ->where('is_purchasing_department', true)
            ->first();

        if (! $department) {
            throw new \DomainException('Purchasing department is not configured for this business unit.');
        }

        $taskExists = AdminTask::query()
            ->where('taskable_type', StockRequest::class)
            ->where('taskable_id', $stockRequest->id)
            ->where('business_unit_id', $stockRequest->business_unit_id)
            ->where('department_id', $department->id)
            ->whereIn('status', ['pending_followup', 'in_progress'])
            ->exists();

        if ($taskExists) {
            return;
        }

        $this->adminTaskService->createTask(
            $stockRequest,
            $stockRequest->business_unit_id,
            $department->id,
            null
        );
    }

    public function reject(StockRequest $stockRequest, User $user, string $reason): void
    {
        $stockRequest->update([
            'status' => 'ga_rejected',
            'ga_reviewed_at' => now(),
            'ga_reviewed_by' => $user->id,
            'ga_rejected_reason' => $reason,
            'rejection_notes' => $reason,
        ]);

        activity()
            ->performedOn($stockRequest)
            ->causedBy($user)
            ->withProperties(['reason' => $reason])
            ->log('stock request GA review rejected');
    }
}
