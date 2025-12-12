<?php

namespace App\Observers;

use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Modules\Purchasing\Admin\AdminTaskAssignmentService;
use App\Services\Modules\Purchasing\Admin\AdminTaskService;
use Illuminate\Support\Facades\Log;

class StockRequestObserver
{
    public function __construct(
        protected AdminTaskService $adminTaskService,
        protected AdminTaskAssignmentService $assignmentService
    ) {}

    /**
     * Handle the StockRequest "updated" event.
     * Triggers admin task creation when ST is approved.
     */
    public function updated(StockRequest $stockRequest): void
    {
        // Check if status changed to 'approved'
        if ($stockRequest->isDirty('status') && $stockRequest->status === 'approved') {
            try {
                // Determine if task should be auto-assigned
                $assignedAdminId = $this->assignmentService->determineAssignment(
                    $stockRequest->department_id,
                    $stockRequest->business_unit_id
                );

                // Create admin task
                $this->adminTaskService->createTask(
                    $stockRequest,
                    $stockRequest->business_unit_id,
                    $stockRequest->department_id,
                    $assignedAdminId
                );

                Log::info('Admin task created for approved ST', [
                    'st_id' => $stockRequest->id,
                    'st_number' => $stockRequest->st_number,
                    'assigned_admin_id' => $assignedAdminId,
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
