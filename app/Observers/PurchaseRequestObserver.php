<?php

namespace App\Observers;

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
                $departmentId = $purchaseRequest->department_id;
                $businessUnitId = $purchaseRequest->business_unit_id;

                if (! $departmentId || ! $businessUnitId) {
                    $fresh = PurchaseRequest::query()
                        ->select(['id', 'department_id', 'business_unit_id'])
                        ->find($purchaseRequest->id);

                    $departmentId = $departmentId ?: $fresh?->department_id;
                    $businessUnitId = $businessUnitId ?: $fresh?->business_unit_id;
                }

                if (! $departmentId || ! $businessUnitId) {
                    Log::warning('Skip admin task creation due to missing PR context', [
                        'pr_id' => $purchaseRequest->id,
                        'department_id' => $departmentId,
                        'business_unit_id' => $businessUnitId,
                    ]);

                    return;
                }

                // Determine if task should be auto-assigned
                $assignedAdminId = $this->assignmentService->determineAssignment(
                    (int) $departmentId,
                    (int) $businessUnitId
                );

                // Create admin task
                $this->adminTaskService->createTask(
                    $purchaseRequest,
                    (int) $businessUnitId,
                    (int) $departmentId,
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
