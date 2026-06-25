<?php

namespace App\Observers;

use App\Models\Core\Department;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Modules\Purchasing\Admin\AdminTaskAssignmentService;
use App\Services\Modules\Purchasing\Admin\AdminTaskService;
use Illuminate\Support\Facades\Log;

class PurchaseRequestObserver
{
    public function __construct(
        protected AdminTaskService $adminTaskService,
        protected AdminTaskAssignmentService $assignmentService
    ) {}

    /**
     * Handle the PurchaseRequest "updated" event.
     * Triggers admin task creation when PR is approved.
     */
    public function updated(PurchaseRequest $purchaseRequest): void
    {
        // Check if status changed to 'approved'
        if ($purchaseRequest->isDirty('status') && $purchaseRequest->status === 'approved') {
            try {
                $businessUnitId = $purchaseRequest->business_unit_id;

                if (! $businessUnitId) {
                    $fresh = PurchaseRequest::query()
                        ->select(['id', 'business_unit_id'])
                        ->find($purchaseRequest->id);

                    $businessUnitId = $fresh?->business_unit_id;
                }

                if (! $businessUnitId) {
                    Log::warning('Skip admin task creation due to missing PR business unit context', [
                        'pr_id' => $purchaseRequest->id,
                        'business_unit_id' => $businessUnitId,
                    ]);

                    return;
                }

                $purchasingDepartment = Department::query()
                    ->where('business_unit_id', $businessUnitId)
                    ->where('is_purchasing_department', true)
                    ->first();

                if (! $purchasingDepartment) {
                    Log::warning('Skip admin task creation due to missing purchasing department', [
                        'pr_id' => $purchaseRequest->id,
                        'business_unit_id' => $businessUnitId,
                    ]);

                    return;
                }

                $assignedAdminId = $this->assignmentService->determineAssignment(
                    $purchasingDepartment->id,
                    (int) $businessUnitId
                );

                $this->adminTaskService->createTask(
                    $purchaseRequest,
                    (int) $businessUnitId,
                    $purchasingDepartment->id,
                    $assignedAdminId
                );

                Log::info('Admin task created for approved PR', [
                    'pr_id' => $purchaseRequest->id,
                    'pr_number' => $purchaseRequest->pr_number,
                    'assigned_admin_id' => $assignedAdminId,
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to create admin task for approved PR', [
                    'pr_id' => $purchaseRequest->id,
                    'pr_number' => $purchaseRequest->pr_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
