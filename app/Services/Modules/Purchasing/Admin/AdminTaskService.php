<?php

namespace App\Services\Modules\Purchasing\Admin;

use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminTaskService
{
    protected AdminTaskNotificationService $notificationService;

    public function __construct(
        protected PriceEfficiencyService $priceEfficiencyService,
        ?AdminTaskNotificationService $notificationService = null,
    ) {
        $this->notificationService = $notificationService ?? app(AdminTaskNotificationService::class);
    }

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

            DB::afterCommit(function () use ($task) {
                try {
                    $committedTask = AdminTask::query()->findOrFail($task->id);
                    $this->notifyAssignedAdmin($committedTask);
                    $this->broadcastAvailableTask($committedTask);
                } catch (\Throwable $exception) {
                    Log::error('Failed to send admin task notifications', [
                        'task_id' => $task->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });

            activity()
                ->performedOn($task)
                ->causedBy(auth()->user())
                ->log('Admin task created');

            return $task;
        });
    }

    public function createForStockRequest(StockRequest $stockRequest): AdminTask
    {
        return DB::transaction(function () use ($stockRequest) {
            StockRequest::query()->lockForUpdate()->findOrFail($stockRequest->id);

            $existingTask = AdminTask::query()
                ->where('taskable_type', StockRequest::class)
                ->where('taskable_id', $stockRequest->id)
                ->first();

            if ($existingTask) {
                return $existingTask;
            }

            $strategicSourcingDepartments = Department::query()
                ->where('business_unit_id', $stockRequest->business_unit_id)
                ->where('code', 'SS')
                ->where('is_active', true)
                ->get();
            $departments = $strategicSourcingDepartments->isNotEmpty()
                ? $strategicSourcingDepartments
                : Department::query()
                    ->where('business_unit_id', $stockRequest->business_unit_id)
                    ->where('is_purchasing_department', true)
                    ->get();

            if ($departments->count() !== 1) {
                throw new \DomainException('Exactly one Purchasing department must be configured for this business unit.');
            }

            $department = $departments->firstOrFail();

            return $this->createTask(
                $stockRequest,
                $stockRequest->business_unit_id,
                $department->id,
            );
        });
    }

    /**
     * Start working on a task
     *
     *
     * @throws \Exception
     */
    public function startTask(AdminTask $task, ?User $actor = null): AdminTask
    {
        $actor ??= auth()->user();
        if (! $actor) {
            throw new \DomainException('Authenticated purchasing admin is required.');
        }
        $this->assertActorCanMutate($task, $actor);

        return DB::transaction(function () use ($task, $actor) {
            $task = AdminTask::where('id', $task->id)
                ->lockForUpdate()
                ->first();

            if (! $task) {
                throw new \Exception('Task is no longer available');
            }

            if ($task->status !== 'pending_followup') {
                throw new \Exception('Task must be in pending_followup status to start');
            }

            if ($task->assigned_admin_id !== $actor->id) {
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
                ->causedBy($actor)
                ->withProperties([
                    'followup_time_minutes' => $followupTimeMinutes,
                ])
                ->log('Task started');

            return $task->fresh();
        });
    }

    public function updateStatus(AdminTask $task, User $actor, string $status): AdminTask
    {
        $this->assertActorCanMutate($task, $actor);

        if ($status !== 'in_progress') {
            throw new \DomainException('Tasks must be completed through the completion form.');
        }

        return $this->startTask($task, $actor);
    }

    /**
     * Complete a task with realized price
     *
     *
     * @throws \Exception
     */
    public function completeTask(AdminTask $task, float $realizedTotalPrice, ?string $notes = null, ?User $actor = null): AdminTask
    {
        $actor ??= auth()->user();
        if (! $actor) {
            throw new \DomainException('Authenticated purchasing admin is required.');
        }
        $this->assertActorCanMutate($task, $actor);
        $task->loadMissing('taskable');
        $isStockRequest = $task->taskable instanceof StockRequest;

        if (! $isStockRequest && $realizedTotalPrice <= 0) {
            throw new \Exception('Realized price must be greater than zero');
        }

        return DB::transaction(function () use ($task, $realizedTotalPrice, $notes, $actor) {
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

            if ($task->assigned_admin_id !== $actor->id) {
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
                ->causedBy($actor)
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
        $actor = User::query()->findOrFail($adminId);
        $this->assertActorCanMutate($task, $actor, requireOwnership: false);
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

            DB::afterCommit(function () use ($task) {
                try {
                    $this->notifyAssignedAdmin($task);
                } catch (\Throwable $exception) {
                    Log::error('Failed to send claimed task notification', [
                        'task_id' => $task->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });

            activity()
                ->performedOn($task)
                ->causedBy($adminId)
                ->log('Task claimed');

            return $task->fresh();
        });
    }

    private function assertActorCanMutate(AdminTask $task, User $actor, bool $requireOwnership = true): void
    {
        $eligible = UserBusinessUnit::query()
            ->where('user_id', $actor->id)
            ->where('business_unit_id', $task->business_unit_id)
            ->where('department_id', $task->department_id)
            ->where('is_active', true)
            ->where('is_purchasing_admin', true)
            ->where('is_purchasing_readonly', false)
            ->exists();

        if (! $eligible) {
            throw new \DomainException('You are not eligible to mutate this task.');
        }

        if ($requireOwnership && $task->assigned_admin_id !== $actor->id) {
            throw new \DomainException('Task is not assigned to you.');
        }
    }

    protected function notifyAssignedAdmin(AdminTask $task): void
    {
        $this->notificationService->notifyAssignedAdmin($task);
    }

    /**
     * Broadcast an "available to claim" notification to all purchasing admins
     * in the same department (excluding any user who is the assigned admin
     * and excluding read-only admins).
     */
    protected function broadcastAvailableTask(AdminTask $task): void
    {
        $this->notificationService->broadcastAvailableTask($task);
    }
}
