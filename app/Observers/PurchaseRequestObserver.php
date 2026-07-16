<?php

namespace App\Observers;

use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Modules\Purchasing\Admin\AdminTaskService;
use App\Services\Modules\Purchasing\Shared\PurchasingDepartmentResolver;

class PurchaseRequestObserver
{
    public function __construct(
        protected AdminTaskService $adminTaskService,
        protected PurchasingDepartmentResolver $purchasingDepartmentResolver,
    ) {}

    /**
     * Handle the PurchaseRequest "updated" event.
     * Triggers admin task creation when PR is approved.
     */
    public function updated(PurchaseRequest $purchaseRequest): void
    {
        // Check if status changed to 'approved'
        if ($purchaseRequest->isDirty('status') && $purchaseRequest->status === 'approved') {
            $businessUnitId = $purchaseRequest->business_unit_id;

            if (! $businessUnitId) {
                $fresh = PurchaseRequest::query()
                    ->select(['id', 'business_unit_id'])
                    ->find($purchaseRequest->id);

                $businessUnitId = $fresh?->business_unit_id;
            }

            if (! $businessUnitId) {
                throw new \DomainException('Purchase request business unit context is missing.');
            }

            $purchasingDepartment = $this->purchasingDepartmentResolver
                ->resolveForBusinessUnit((int) $businessUnitId);

            $taskExists = AdminTask::query()
                ->where('taskable_type', PurchaseRequest::class)
                ->where('taskable_id', $purchaseRequest->id)
                ->where('business_unit_id', $businessUnitId)
                ->where('department_id', $purchasingDepartment->id)
                ->whereIn('status', ['pending_followup', 'in_progress'])
                ->exists();

            if ($taskExists) {
                return;
            }

            $this->adminTaskService->createTask(
                $purchaseRequest,
                (int) $businessUnitId,
                $purchasingDepartment->id,
                null
            );
        }
    }
}
