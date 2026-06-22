<?php

namespace App\Actions\Modules\Activity;

use App\Http\Requests\Modules\Activity\StoreActivityTaskRequest;
use App\Models\Core\User;
use App\Models\Modules\Activity\EmployeeTask;
use App\Notifications\Activity\TaskTaggedNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Create a new Activity task and attach participants/notifications.
 *
 * Lifted verbatim from ActivityInertiaController::store() to preserve
 * status/started_at/completed_at/duration calculation behavior.
 */
class CreateActivityTaskAction
{
    /**
     * Execute the create flow.
     *
     * @return array{ok: true, task: EmployeeTask, redirect_url: string}|array{ok: false, error: string}
     */
    public function execute(StoreActivityTaskRequest $request, User $user, ?int $buId, ?int $departmentId): array
    {
        $validated = $request->validated();
        $taskDate = Carbon::parse($validated['task_date']);
        $isTodayTask = $taskDate->isSameDay(now());
        $status = $validated['status'];

        $startedAt = null;
        $completedAt = null;
        $durationMinutes = null;
        $completedDate = $validated['completed_date'] ?? $validated['task_date'];

        if ($status === 'in_progress') {
            if (! $isTodayTask && ! empty($validated['start_time'])) {
                $startedAt = Carbon::parse($validated['task_date'].' '.$validated['start_time'], config('app.timezone'));
            } else {
                $startedAt = now();
            }
        } elseif ($status === 'completed') {
            $startedAt = Carbon::parse($validated['task_date'].' '.$validated['start_time'], config('app.timezone'));
            $completedAt = Carbon::parse($completedDate.' '.$validated['end_time'], config('app.timezone'));
            $durationMinutes = $startedAt->diffInMinutes($completedAt);
        }

        DB::beginTransaction();
        try {
            $task = EmployeeTask::create([
                'business_unit_id' => $buId,
                'department_id' => $departmentId,
                'created_by' => $user->id,
                'activity_type_id' => $validated['activity_type_id'],
                'sub_activity_id' => $validated['sub_activity_id'] ?? null,
                'task_title' => $validated['task_title'],
                'task_description' => $validated['task_description'] ?? null,
                'status' => $status,
                'priority' => $validated['priority'],
                'task_date' => $validated['task_date'],
                'due_date' => $validated['due_date'] ?? null,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'completed_by' => $status === 'completed' ? $user->id : null,
                'duration_minutes' => $durationMinutes,
            ]);

            $task->participants()->attach($user->id, [
                'is_owner' => true,
                'joined_at' => now(),
            ]);

            if (! empty($validated['participant_ids'])) {
                foreach ($validated['participant_ids'] as $participantId) {
                    if ((int) $participantId !== (int) $user->id) {
                        $task->participants()->attach($participantId, [
                            'is_owner' => false,
                            'joined_at' => now(),
                        ]);

                        $participant = User::query()->find($participantId);

                        if ($participant) {
                            $participant->notify(new TaskTaggedNotification($task, $user));
                        }
                    }
                }
            }

            $this->clearCache($buId, $user->id);
            DB::commit();

            return [
                'ok' => true,
                'task' => $task,
                'redirect_url' => $this->resolveTaskCreateRedirectUrl($request),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return ['ok' => false, 'error' => 'Failed to create task.'];
        }
    }

    private function clearCache(?int $buId, int $userId): void
    {
        Cache::forget("activity_stats_{$buId}_{$userId}");
    }

    /**
     * Resolve the post-create redirect target without reviving stale modal intent.
     */
    private function resolveTaskCreateRedirectUrl(Request $request): string
    {
        $fallbackUrl = route('activity.task.index');
        $referer = $request->headers->get('referer');

        if (! is_string($referer) || $referer === '') {
            return $fallbackUrl;
        }

        $parsedReferer = parse_url($referer);
        if ($parsedReferer === false) {
            return $fallbackUrl;
        }

        $appUrl = config('app.url') ?: url('/');
        $appHost = parse_url($appUrl, PHP_URL_HOST);
        $refererHost = $parsedReferer['host'] ?? $appHost;

        if ($appHost !== null && $refererHost !== $appHost) {
            return $fallbackUrl;
        }

        $refererPath = $parsedReferer['path'] ?? '';
        $taskCreatePath = parse_url(route('activity.task.create'), PHP_URL_PATH) ?: '';
        $taskIndexPath = parse_url(route('activity.task.index'), PHP_URL_PATH) ?: '';

        parse_str($parsedReferer['query'] ?? '', $query);
        unset($query['modal'], $query['task'], $query['date']);

        $targetPath = $refererPath === $taskCreatePath ? $taskIndexPath : $refererPath;
        if ($targetPath === '') {
            return $fallbackUrl;
        }

        $scheme = $parsedReferer['scheme'] ?? parse_url($appUrl, PHP_URL_SCHEME) ?? 'http';
        $port = isset($parsedReferer['port']) ? ':'.$parsedReferer['port'] : '';
        $targetUrl = "{$scheme}://{$refererHost}{$port}{$targetPath}";

        if ($query !== []) {
            $targetUrl .= '?'.http_build_query($query);
        }

        return $targetUrl;
    }
}
