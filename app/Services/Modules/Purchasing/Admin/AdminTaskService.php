<?php

namespace App\Services\Modules\Purchasing\Admin;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Notifications\Purchasing\Admin\TaskAssigned;
use App\Notifications\Purchasing\Admin\TaskAvailable;
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

            $this->notifyAssignedAdmin($task);
            $this->broadcastAvailableTask($task);

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
        return DB::transaction(function () use ($task) {
            $task = AdminTask::where('id', $task->id)
                ->lockForUpdate()
                ->first();

            if (! $task) {
                throw new \Exception('Task is no longer available');
            }

            if ($task->status !== 'pending_followup') {
                throw new \Exception('Task must be in pending_followup status to start');
            }

            if ($task->assigned_admin_id !== auth()->id()) {
                throw new \Exception('Task must be assigned to you to start');
            }

            $startedAt = now();

            // Calculate follow-up time in minutes (entered_at to started_at, always positive)
            $followupTimeMinutes = abs($task->entered_at->diffInMinutes($startedAt));

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
        $task->loadMissing('taskable');
        $isStockRequest = $task->taskable instanceof StockRequest;

        if (! $isStockRequest && $realizedTotalPrice <= 0) {
            throw new \Exception('Realized price must be greater than zero');
        }

        return DB::transaction(function () use ($task, $realizedTotalPrice, $notes) {
            $task = AdminTask::where('id', $task->id)
                ->lockForUpdate()
                ->with('taskable')
                ->first();

            if (! $task) {
                throw new \Exception('Task is no longer available');
            }

            if ($task->status !== 'in_progress') {
                throw new \Exception('Task must be in_progress status to complete');
            }

            if ($task->assigned_admin_id !== auth()->id()) {
                throw new \Exception('Task must be assigned to you to complete');
            }

            $completedAt = now();

            // Calculate completion time in minutes (started_at to completed_at, always positive)
            $completionTimeMinutes = $task->started_at ? abs($task->started_at->diffInMinutes($completedAt)) : 0;

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

            if ($task->taskable instanceof PurchaseRequest || $task->taskable instanceof StockRequest) {
                $task->taskable->forceFill(['status' => 'done'])->saveQuietly();
            }

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

            $this->notifyAssignedAdmin($task);

            activity()
                ->performedOn($task)
                ->causedBy($adminId)
                ->log('Task claimed');

            return $task->fresh();
        });
    }

    protected function notifyAssignedAdmin(AdminTask $task): void
    {
        if (! $task->assigned_admin_id) {
            return;
        }

        $admin = User::query()->find($task->assigned_admin_id);

        if (! $admin) {
            return;
        }

        $admin->notify(new TaskAssigned($task->fresh(['taskable'])));
    }

    /**
     * Broadcast an "available to claim" notification to all purchasing admins
     * in the same department (excluding any user who is the assigned admin
     * and excluding read-only admins).
     */
    protected function broadcastAvailableTask(AdminTask $task): void
    {
        $recipients = User::query()
            ->whereHas('businessUnits', function ($q) use ($task) {
                $q->where('business_unit_id', $task->business_unit_id)
                    ->where('department_id', $task->department_id)
                    ->where('is_purchasing_admin', true)
                    ->where('is_purchasing_readonly', false)
                    ->where('is_active', true);
            })
            ->where('users.id', '!=', $task->assigned_admin_id ?? 0)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = new TaskAvailable($task->fresh(['taskable']));

        foreach ($recipients as $recipient) {
            $recipient->notify($notification);
        }
    }
}
