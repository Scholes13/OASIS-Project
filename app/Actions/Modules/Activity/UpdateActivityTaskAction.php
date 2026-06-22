<?php

namespace App\Actions\Modules\Activity;

use App\Http\Requests\Modules\Activity\UpdateActivityTaskRequest;
use App\Models\Core\User;
use App\Models\Modules\Activity\EmployeeTask;
use App\Notifications\Activity\TaskTaggedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Update an existing Activity task.
 *
 * Lifted verbatim from ActivityInertiaController::update() to preserve:
 *  - partial/status quick-update path (with allowed-transition guard)
 *  - full update path (with started_at/completed_at correction logic)
 *  - participant sync + new-participant notifications
 *
 * Returns a structured result the controller maps to redirects/flash.
 */
class UpdateActivityTaskAction
{
    /**
     * @return array{ok: true, type: 'partial'|'full', task: EmployeeTask}|array{ok: false, error: string}
     */
    public function execute(UpdateActivityTaskRequest $request, EmployeeTask $task, User $user, ?int $buId): array
    {
        $isPartialUpdate = $request->has('due_date') && ! $request->has('task_title');
        $isStatusUpdate = $request->has('status') && ! $request->has('task_title');

        if ($isPartialUpdate || $isStatusUpdate) {
            return $this->applyPartialUpdate($request, $task, $user, $buId);
        }

        return $this->applyFullUpdate($request, $task, $user, $buId);
    }

    /**
     * @return array{ok: true, type: 'partial', task: EmployeeTask}|array{ok: false, error: string}
     */
    private function applyPartialUpdate(UpdateActivityTaskRequest $request, EmployeeTask $task, User $user, ?int $buId): array
    {
        $validated = $request->validated();
        $updateData = [];

        if (isset($validated['due_date'])) {
            $updateData['due_date'] = $validated['due_date'];
        }

        if (isset($validated['status'])) {
            $hasResetConfirmation = ! empty($validated['confirm_reset_execution']);
            $allowedTransitions = [
                'planned' => ['in_progress', 'completed', 'cancelled'],
                'in_progress' => ['completed', 'cancelled', 'planned'],
                'completed' => $hasResetConfirmation ? ['planned'] : [],
                'cancelled' => $hasResetConfirmation ? ['planned'] : [],
            ];

            $currentStatus = $task->status;
            $newStatus = $validated['status'];

            if ($currentStatus === $newStatus || ! in_array($newStatus, $allowedTransitions[$currentStatus] ?? [], true)) {
                return ['ok' => false, 'error' => 'Invalid status transition.'];
            }

            $updateData['status'] = $newStatus;

            if ($validated['status'] === 'in_progress' && ! $task->started_at) {
                $updateData['started_at'] = now();
            }

            if ($validated['status'] === 'completed') {
                $completedAt = now();
                $updateData['completed_at'] = $completedAt;
                $updateData['completed_by'] = $user->id;

                $effectiveStartedAt = $task->started_at ?? ($updateData['started_at'] ?? null);
                if ($effectiveStartedAt) {
                    $updateData['duration_minutes'] = Carbon::parse($effectiveStartedAt)->diffInMinutes($completedAt);
                }
            }

            if ($validated['status'] === 'planned') {
                $updateData['started_at'] = null;
                $updateData['completed_at'] = null;
                $updateData['completed_by'] = null;
                $updateData['duration_minutes'] = null;
            }
        }

        $updateData['edited_at'] = now();
        $updateData['edited_by'] = $user->id;

        $task->update($updateData);
        Cache::forget("activity_stats_{$buId}_{$user->id}");

        return ['ok' => true, 'type' => 'partial', 'task' => $task];
    }

    /**
     * @return array{ok: true, type: 'full', task: EmployeeTask}|array{ok: false, error: string}
     */
    private function applyFullUpdate(UpdateActivityTaskRequest $request, EmployeeTask $task, User $user, ?int $buId): array
    {
        $validated = $request->validated();

        $participantIds = $validated['participant_ids'] ?? [];
        $existingParticipantIds = $task->participants()
            ->pluck('users.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $status = $validated['status'];
        $submittedTaskDate = Carbon::parse($validated['task_date']);
        $completedDate = $validated['completed_date'] ?? $validated['task_date'];
        $requiresStartCorrection = $this->requiresStartTimeCorrection($task, $submittedTaskDate, $status);
        $requiresCompletionCorrection = $this->requiresCompletionTimeCorrection($task, $submittedTaskDate, $validated);

        [$startedAt, $completedAt, $durationMinutes, $completedBy] = $this->resolveTimeFields(
            $task,
            $status,
            $submittedTaskDate,
            $completedDate,
            $validated,
            $requiresStartCorrection,
            $requiresCompletionCorrection,
            $user,
        );

        DB::beginTransaction();
        try {
            $task->update([
                'activity_type_id' => $validated['activity_type_id'],
                'sub_activity_id' => $validated['sub_activity_id'] ?? null,
                'task_title' => $validated['task_title'],
                'task_description' => $validated['task_description'] ?? null,
                'status' => $status,
                'priority' => $validated['priority'],
                'task_date' => $submittedTaskDate->format('Y-m-d'),
                'due_date' => $validated['due_date'] ?? null,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'completed_by' => $completedBy,
                'duration_minutes' => $durationMinutes,
                'edited_at' => now(),
                'edited_by' => $user->id,
            ]);

            $ownerId = $task->participants()->wherePivot('is_owner', true)->first()?->id;
            $newParticipants = [];

            if ($ownerId) {
                $newParticipants[$ownerId] = ['is_owner' => true, 'joined_at' => now()];
            }

            if (! empty($participantIds)) {
                foreach ($participantIds as $participantId) {
                    if (empty($participantId) || $participantId == $ownerId) {
                        continue;
                    }
                    $newParticipants[(int) $participantId] = ['is_owner' => false, 'joined_at' => now()];
                }
            }

            if (! empty($newParticipants)) {
                $task->participants()->sync($newParticipants);
            }

            $newlyAddedParticipantIds = array_values(array_diff(array_keys($newParticipants), $existingParticipantIds, [$ownerId]));

            if (! empty($newlyAddedParticipantIds)) {
                User::query()
                    ->whereIn('id', $newlyAddedParticipantIds)
                    ->get()
                    ->each(fn (User $participant): User => tap($participant, fn (User $recipient) => $recipient->notify(new TaskTaggedNotification($task, $user))));
            }

            Cache::forget("activity_stats_{$buId}_{$user->id}");
            DB::commit();

            return ['ok' => true, 'type' => 'full', 'task' => $task];
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return ['ok' => false, 'error' => 'Failed to update task.'];
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: ?Carbon, 1: ?Carbon, 2: ?int, 3: ?int}
     */
    private function resolveTimeFields(
        EmployeeTask $task,
        string $status,
        Carbon $submittedTaskDate,
        string $completedDate,
        array $validated,
        bool $requiresStartCorrection,
        bool $requiresCompletionCorrection,
        User $user,
    ): array {
        $startedAt = $task->started_at;
        $completedAt = $task->completed_at;
        $durationMinutes = $task->duration_minutes;
        $completedBy = $task->completed_by;

        if ($status === 'in_progress') {
            if ($task->started_at && ! empty($validated['start_time'])) {
                $startedAt = Carbon::parse($submittedTaskDate->format('Y-m-d').' '.$validated['start_time'], config('app.timezone'));
            } elseif ($task->started_at) {
                $startedAt = $task->started_at;
            } elseif ($requiresStartCorrection && ! empty($validated['start_time'])) {
                $startedAt = Carbon::parse($submittedTaskDate->format('Y-m-d').' '.$validated['start_time'], config('app.timezone'));
            } elseif (! $task->started_at && $submittedTaskDate->isSameDay(now())) {
                $startedAt = now();
            }
        } elseif ($status === 'completed') {
            if (($requiresStartCorrection || ! $task->started_at) && ! empty($validated['start_time'])) {
                $startedAt = Carbon::parse($submittedTaskDate->format('Y-m-d').' '.$validated['start_time'], config('app.timezone'));
            }

            if (($requiresCompletionCorrection || ! $task->completed_at) && ! empty($validated['end_time'])) {
                $completedAt = Carbon::parse($completedDate.' '.$validated['end_time'], config('app.timezone'));
            }

            if ($startedAt && $completedAt) {
                $durationMinutes = $startedAt->diffInMinutes($completedAt);
                $completedBy = $user->id;
            }
        } elseif ($status === 'planned') {
            $startedAt = null;
            $completedAt = null;
            $durationMinutes = null;
            $completedBy = null;
        }

        return [$startedAt, $completedAt, $durationMinutes, $completedBy];
    }

    private function requiresStartTimeCorrection(EmployeeTask $task, Carbon $submittedTaskDate, string $status): bool
    {
        if (! in_array($status, ['in_progress', 'completed'], true)) {
            return false;
        }

        if ($status === 'completed') {
            if (! $task->started_at) {
                return true;
            }

            return $submittedTaskDate->format('Y-m-d') !== $task->started_at->format('Y-m-d');
        }

        if (! $task->started_at) {
            return ! $submittedTaskDate->isSameDay(now());
        }

        return false;
    }

    private function requiresCompletionTimeCorrection(EmployeeTask $task, Carbon $submittedTaskDate, array $validated): bool
    {
        if (($validated['status'] ?? null) !== 'completed') {
            return false;
        }

        if (! $task->completed_at) {
            return true;
        }

        if (empty($validated['completed_date'])) {
            return false;
        }

        return $validated['completed_date'] !== $task->completed_at->format('Y-m-d');
    }
}
