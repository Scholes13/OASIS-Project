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
                $departmentId = $stockRequest->department_id;
                $businessUnitId = $stockRequest->business_unit_id;

                if (! $departmentId || ! $businessUnitId) {
                    $fresh = StockRequest::query()
                        ->select(['id', 'department_id', 'business_unit_id'])
                        ->find($stockRequest->id);

                    $departmentId = $departmentId ?: $fresh?->department_id;
                    $businessUnitId = $businessUnitId ?: $fresh?->business_unit_id;
                }

                if (! $departmentId || ! $businessUnitId) {
                    Log::warning('Skip admin task creation due to missing ST context', [
                        'st_id' => $stockRequest->id,
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
                    $stockRequest,
                    (int) $businessUnitId,
                    (int) $departmentId,
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
