<?php

namespace App\Services\Modules\Activity;

use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Database\Eloquent\Builder;

/**
 * Scope/authorization helpers for the Activity module.
 *
 * Lifted verbatim from ActivityInertiaController to preserve behavior:
 *  - buildTaskScopeQuery
 *  - resolveSubDepartments
 *  - sanitizeDeptFilter
 *  - canViewTask
 *  - canEditTask
 */
class TaskScopeResolver
{
    /**
     * Build the base query for "my" or "department" scoped task listings.
     */
    public function buildTaskScopeQuery(int $buId, int $userId, ?int $departmentId, string $scope): Builder
    {
        $query = EmployeeTask::query()
            ->where('business_unit_id', $buId);

        if ($scope === 'my') {
            return $query->where(function ($taskQuery) use ($userId) {
                $taskQuery->whereHas('participants', fn ($participantQuery) => $participantQuery->where('user_id', $userId))
                    ->orWhere('created_by', $userId);
            });
        }

        return $query->where(function ($taskQuery) use ($userId, $departmentId) {
            $taskQuery->where('department_id', $departmentId)
                ->orWhereHas('participants', fn ($participantQuery) => $participantQuery->where('user_id', $userId));
        });
    }

    /**
     * Get sub-departments under the given dept (only when current is a root
     * with active children). Returns empty array when current dept is flat
     * (HR, ACC, etc.) so the frontend can hide the sub-dept dropdown.
     *
     * @return array<int, array{id: int, name: string, code: string}>
     */
    public function resolveSubDepartments(?int $departmentId): array
    {
        if ($departmentId === null) {
            return [];
        }

        $dept = Department::with('activeChildren:id,parent_department_id,code,name,is_active')
            ->find($departmentId);

        if (! $dept || $dept->activeChildren->isEmpty()) {
            return [];
        }

        return $dept->activeChildren
            ->sortBy('name')
            ->map(fn ($child) => [
                'id' => $child->id,
                'code' => $child->code,
                'name' => $child->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Sanitize requested dept_filter against the list of valid sub-departments.
     * Returns null when invalid, missing, or refers to a dept outside the
     * current root scope.
     *
     * @param  array<int, array{id: int, name: string, code: string}>  $validSubDepartments
     */
    public function sanitizeDeptFilter(mixed $requestedDeptId, array $validSubDepartments): ?int
    {
        if ($requestedDeptId === null || $requestedDeptId === '' || ! is_scalar($requestedDeptId)) {
            return null;
        }

        $deptId = filter_var((string) $requestedDeptId, FILTER_VALIDATE_INT);
        if ($deptId === false) {
            return null;
        }

        $validIds = array_column($validSubDepartments, 'id');

        return in_array($deptId, $validIds, true) ? $deptId : null;
    }

    /**
     * Determine whether the authenticated user can edit the given task.
     */
    public function canEditTask(EmployeeTask $task, ?User $user, mixed $businessUnitId): bool
    {
        if (! $user || ! $businessUnitId || (int) $task->business_unit_id !== (int) $businessUnitId) {
            return false;
        }

        if ((int) $task->created_by === (int) $user->id) {
            return true;
        }

        return $task->participants()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the authenticated user can view the given task.
     */
    public function canViewTask(EmployeeTask $task, ?User $user, mixed $businessUnitId): bool
    {
        if (! $user || ! $businessUnitId || (int) $task->business_unit_id !== (int) $businessUnitId) {
            return false;
        }

        if ($this->canEditTask($task, $user, $businessUnitId)) {
            return true;
        }

        $departmentId = $user->getCurrentDepartmentId();

        return $departmentId !== null && (int) $task->department_id === (int) $departmentId;
    }
}
