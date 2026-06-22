<?php

namespace App\Services\Modules\Activity;

use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Hydrates and formats Activity task payloads for the Inertia layer.
 *
 * Lifted verbatim from ActivityInertiaController:
 *  - hydrateTaskAvatars
 *  - getSelectedTaskForModal / getSelectedTaskModal
 *  - getDepartmentActivityTypes / getValidActivityTypeIds
 *  - formatPrioritizedActivityTypes
 */
class TaskPresenter
{
    public function __construct(
        protected TaskScopeResolver $scopeResolver
    ) {}

    /**
     * Ensure task owner/participant user payloads always expose an avatar_url key.
     */
    public function hydrateTaskAvatars(EmployeeTask $task): void
    {
        if ($task->relationLoaded('creator') && $task->creator) {
            $task->creator->setAttribute('avatar_url', $task->creator->getAttribute('avatar_url'));
        }

        if (! $task->relationLoaded('participants')) {
            return;
        }

        $task->participants->each(function ($participant): void {
            $participant->setAttribute('avatar_url', $participant->getAttribute('avatar_url'));
        });
    }

    /**
     * Get the selected task modal intent from the request.
     */
    public function getSelectedTaskModal(Request $request): ?string
    {
        $modal = $request->string('modal')->toString();

        return in_array($modal, ['detail', 'edit'], true) ? $modal : null;
    }

    /**
     * Load the selected task when the task index is opened with modal intent.
     */
    public function getSelectedTaskForModal(Request $request): ?EmployeeTask
    {
        $taskId = $request->integer('task');
        $modal = $this->getSelectedTaskModal($request);
        $user = Auth::user();
        $buId = session('current_business_unit_id');
        $departmentId = $user?->getCurrentDepartmentId();

        if (! $taskId || ! in_array($modal, ['detail', 'edit'], true) || ! $user || ! $buId) {
            return null;
        }

        $query = EmployeeTask::query()
            ->where('business_unit_id', $buId)
            ->where(function ($query) use ($user, $departmentId) {
                $query->where('created_by', $user->id)
                    ->orWhereHas('participants', fn ($participantQuery) => $participantQuery->where('user_id', $user->id));

                if ($departmentId) {
                    $query->orWhere('department_id', $departmentId);
                }
            })
            ->when($modal === 'edit', function ($query) use ($user) {
                $query->where(function ($editableQuery) use ($user) {
                    $editableQuery->where('created_by', $user->id)
                        ->orWhereHas('participants', fn ($participantQuery) => $participantQuery->where('user_id', $user->id));
                });
            })
            ->with([
                'activityType',
                'subActivity',
                'participants',
                'creator',
                'department',
                'attachments',
                'comments' => fn ($q) => $q->with('user:id,name')->whereNull('deleted_at')->orderBy('created_at', 'asc')->limit(50),
            ]);

        $task = $query->find($taskId);

        if ($task) {
            $task->comments_data = $task->comments->map(fn ($c) => [
                'id' => $c->id,
                'user' => $c->user ? ['id' => $c->user->id, 'name' => $c->user->name] : null,
                'body' => $c->body,
                'edited_at' => $c->edited_at?->toISOString(),
                'created_at' => $c->created_at->toISOString(),
                'can_edit' => $c->user_id === auth()->id(),
                'can_delete' => $c->user_id === auth()->id(),
            ]);
        }

        return $task;
    }

    /**
     * Get activity types assigned to a department, ordered by pivot sort_order
     *
     * Queries via department_activity_types pivot table (Requirements 5.1)
     * Orders by department's configured sort_order (Requirements 5.3)
     */
    public function getDepartmentActivityTypes(?int $departmentId): array
    {
        if (! $departmentId) {
            return [];
        }

        $department = Department::find($departmentId);
        if (! $department) {
            return [];
        }

        return $department->activeActivityTypes()
            ->select(['employee_activity_types.id', 'employee_activity_types.code', 'employee_activity_types.name', 'employee_activity_types.color'])
            ->with(['subActivities' => function ($query) {
                $query->where('is_active', true)
                    ->select(['id', 'activity_type_id', 'code', 'name'])
                    ->orderBy('sort_order')
                    ->orderBy('name');
            }])
            ->get()
            ->map(fn ($type) => [
                'id' => $type->id,
                'code' => $type->code,
                'name' => $type->name,
                'color' => $type->color,
                'sub_activities' => $type->subActivities->map(fn ($sub) => [
                    'id' => $sub->id,
                    'activity_type_id' => $sub->activity_type_id,
                    'code' => $sub->code,
                    'name' => $sub->name,
                ])->values()->toArray(),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get valid activity type IDs for a department (Requirements 5.1).
     *
     * @return array<int, int>
     */
    public function getValidActivityTypeIds(?int $departmentId): array
    {
        if (! $departmentId) {
            return [];
        }

        $department = Department::find($departmentId);
        if (! $department) {
            return [];
        }

        return $department->activeActivityTypes()
            ->pluck('employee_activity_types.id')
            ->toArray();
    }

    /**
     * Format prioritized activity types for frontend consumption.
     *
     * Returns activity types grouped by priority with sub-activities loaded.
     *
     * @param  array<string, \Illuminate\Support\Collection>  $prioritized
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function formatPrioritizedActivityTypes(array $prioritized): array
    {
        $format = function ($types, string $priority) {
            return $types->map(function ($type) use ($priority) {
                return [
                    'id' => $type->id,
                    'code' => $type->code,
                    'name' => $type->name,
                    'color' => $type->color,
                    'priority' => $priority,
                    'sub_activities' => $type->subActivities->map(fn ($s) => [
                        'id' => $s->id,
                        'activity_type_id' => $s->activity_type_id,
                        'code' => $s->code,
                        'name' => $s->name,
                    ])->values()->toArray(),
                ];
            });
        };

        return [
            'favorites' => $format($prioritized['favorites'], 'favorite')->values()->toArray(),
            'department' => $format($prioritized['department'], 'department')->values()->toArray(),
            'others' => $format($prioritized['others'], 'other')->values()->toArray(),
        ];
    }
}
