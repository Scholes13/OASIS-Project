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
                // Determine if task should be auto-assigned
                $assignedAdminId = $this->assignmentService->determineAssignment(
                    $purchaseRequest->department_id,
                    $purchaseRequest->business_unit_id
                );

                // Create admin task
                $this->adminTaskService->createTask(
                    $purchaseRequest,
                    $purchaseRequest->business_unit_id,
                    $purchaseRequest->department_id,
                    $assignedAdminId
                );

                Log::info('Admin task created for approved PR', [
                    'pr_id' => $purchaseRequest->id,
                    'pr_number' => $purchaseRequest->pr_number,
                    'assigned_admin_id' => $assignedAdminId,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create admin task for approved PR', [
                    'pr_id' => $purchaseRequest->id,
                    'pr_number' => $purchaseRequest->pr_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
