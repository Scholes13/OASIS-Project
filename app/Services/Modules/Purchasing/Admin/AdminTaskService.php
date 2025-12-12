<?php

namespace App\Services\Modules\Purchasing\Admin;

use App\Models\Modules\Purchasing\Admin\AdminTask;
use Illuminate\Support\Facades\DB;

class AdminTaskService
{
    public function __construct(
        protected PriceEfficiencyService $priceEfficiencyService
    ) {}

    /**
     * Create a new admin task from a taskable (PR or ST)
     *
     * @param  \Illuminate\Database\Eloquent\Model  $taskable  The PR or ST model
     * @param  int|null  $assignedAdminId  Optional admin ID for auto-assignment
     */
    public function createTask(
        $taskable,
        int $businessUnitId,
        int $departmentId,
        ?int $assignedAdminId = null
    ): AdminTask {
        return DB::transaction(function () use ($taskable, $businessUnitId, $departmentId, $assignedAdminId) {
            $task = AdminTask::create([
                'taskable_type' => get_class($taskable),
                'taskable_id' => $taskable->id,
                'business_unit_id' => $businessUnitId,
                'department_id' => $departmentId,
                'assigned_admin_id' => $assignedAdminId,
                'status' => 'pending_followup',
                'entered_at' => now(),
                'estimated_total_price' => $taskable->total_amount ?? 0,
                'savings_amount' => null,
                'savings_percentage' => null,
            ]);

            activity()
                ->performedOn($task)
                ->causedBy(auth()->user())
                ->log('Admin task created');

            return $task;
        });
    }

    /**
     * Start working on a task
     *
     *
     * @throws \Exception
     */
    public function startTask(AdminTask $task): AdminTask
    {
        if ($task->status !== 'pending_followup') {
            throw new \Exception('Task must be in pending_followup status to start');
        }

        if ($task->assigned_admin_id !== auth()->id()) {
            throw new \Exception('Task must be assigned to you to start');
        }

        return DB::transaction(function () use ($task) {
            $startedAt = now();

            // Calculate follow-up time in minutes
            $followupTimeMinutes = $task->entered_at->diffInMinutes($startedAt);

            $task->update([
                'status' => 'in_progress',
                'started_at' => $startedAt,
                'followup_time_minutes' => $followupTimeMinutes,
            ]);

            activity()
                ->performedOn($task)
                ->causedBy(auth()->user())
                ->withProperties([
                    'followup_time_minutes' => $followupTimeMinutes,
                ])
                ->log('Task started');

            return $task->fresh();
        });
    }

    /**
     * Complete a task with realized price
     *
     *
     * @throws \Exception
     */
    public function completeTask(AdminTask $task, float $realizedTotalPrice, ?string $notes = null): AdminTask
    {
        if ($task->status !== 'in_progress') {
            throw new \Exception('Task must be in_progress status to complete');
        }

        if ($task->assigned_admin_id !== auth()->id()) {
            throw new \Exception('Task must be assigned to you to complete');
        }

        if ($realizedTotalPrice <= 0) {
            throw new \Exception('Realized price must be greater than zero');
        }

        return DB::transaction(function () use ($task, $realizedTotalPrice, $notes) {
            $completedAt = now();

            // Calculate completion time in minutes
            $completionTimeMinutes = $task->started_at->diffInMinutes($completedAt);

            // Calculate savings
            $savingsData = $this->priceEfficiencyService->calculateSavings(
                $task->estimated_total_price,
                $realizedTotalPrice
            );

            $task->update([
                'status' => 'done',
                'completed_at' => $completedAt,
                'realized_total_price' => $realizedTotalPrice,
                'completion_time_minutes' => $completionTimeMinutes,
                'savings_amount' => $savingsData['savings_amount'],
                'savings_percentage' => $savingsData['savings_percentage'],
                'notes' => $notes,
            ]);

            activity()
                ->performedOn($task)
                ->causedBy(auth()->user())
                ->withProperties([
                    'completion_time_minutes' => $completionTimeMinutes,
                    'realized_total_price' => $realizedTotalPrice,
                    'savings_amount' => $savingsData['savings_amount'],
                    'savings_percentage' => $savingsData['savings_percentage'],
                ])
                ->log('Task completed');

            return $task->fresh();
        });
    }

    /**
     * Claim an unassigned task
     *
     *
     * @throws \Exception
     */
    public function claimTask(AdminTask $task, int $adminId): AdminTask
    {
        if ($task->assigned_admin_id !== null) {
            throw new \Exception('Task is already assigned');
        }

        if ($task->status !== 'pending_followup') {
            throw new \Exception('Only pending tasks can be claimed');
        }

        return DB::transaction(function () use ($task, $adminId) {
            // Use lockForUpdate to prevent race conditions
            $task = AdminTask::where('id', $task->id)
                ->whereNull('assigned_admin_id')
                ->lockForUpdate()
                ->first();

            if (! $task) {
                throw new \Exception('Task is no longer available');
            }

            $task->update([
                'assigned_admin_id' => $adminId,
            ]);

            activity()
                ->performedOn($task)
                ->causedBy($adminId)
                ->log('Task claimed');

            return $task->fresh();
        });
    }
}
