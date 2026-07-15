<?php

namespace App\Services\Modules\Purchasing\PurchaseRequest;

use App\Models\Modules\Purchasing\PurchaseRequest\PrApproval;
use App\Models\Modules\Purchasing\StockRequest\StockApproval;

class ApprovalListPayloadBuilder
{
    public function transformPurchaseRequest(PrApproval $approval, bool $canProcess, bool $includeProcessableActions): array
    {
        $payload = [
            'id' => $approval->id,
            'type' => 'PR',
            'request_number' => $approval->purchaseRequest->pr_number,
            'request_id' => $approval->purchaseRequest->id,
            'used_for' => $approval->purchaseRequest->used_for,
            'total_amount' => $approval->purchaseRequest->total_amount,
            'currency' => $approval->purchaseRequest->currency ?? 'IDR',
            'user' => $approval->purchaseRequest->user ?? ['id' => 0, 'name' => 'Unknown User', 'email' => ''],
            'department' => $approval->purchaseRequest->department ?? ['id' => 0, 'name' => 'Unknown Department', 'code' => '-'],
            'business_unit' => $approval->purchaseRequest->businessUnit ?? ['id' => 0, 'name' => 'Unknown BU', 'code' => '-'],
            'step_order' => $approval->step_order,
            'approval_type' => $approval->approval_type ?? $approval->task_type,
            'status' => $approval->status,
            'waiting_since' => $approval->created_at->toISOString(),
            'can' => ['approve' => $canProcess, 'reject' => $canProcess],
        ];

        if ($includeProcessableActions) {
            $payload['created_at'] = $approval->created_at;
        } else {
            $payload['responded_at'] = $approval->responded_at;
        }

        return $payload;
    }

    public function transformStockRequest(StockApproval $approval, bool $canProcess, bool $includeProcessableActions): array
    {
        $payload = [
            'id' => $approval->id,
            'type' => 'ST',
            'request_number' => $approval->stockRequest->st_number,
            'request_id' => $approval->stockRequest->id,
            'used_for' => $approval->stockRequest->purpose,
            'total_amount' => $approval->stockRequest->items->sum('total'),
            'currency' => 'IDR',
            'user' => $approval->stockRequest->user ?? ['id' => 0, 'name' => 'Unknown User', 'email' => ''],
            'department' => $approval->stockRequest->department ?? ['id' => 0, 'name' => 'Unknown Department', 'code' => '-'],
            'business_unit' => $approval->stockRequest->businessUnit ?? ['id' => 0, 'name' => 'Unknown BU', 'code' => '-'],
            'step_order' => $approval->step_order,
            'approval_type' => $approval->approval_type ?? $approval->task_type,
            'status' => $approval->status,
            'waiting_since' => $approval->created_at->toISOString(),
            'can' => ['approve' => $canProcess, 'reject' => $canProcess],
        ];

        if ($includeProcessableActions) {
            $payload['created_at'] = $approval->created_at;
        } else {
            $payload['responded_at'] = $approval->responded_at;
        }

        return $payload;
    }
}
