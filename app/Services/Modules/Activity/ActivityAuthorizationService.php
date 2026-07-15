<?php

namespace App\Services\Modules\Activity;

use App\Models\Core\User;
use App\Models\Modules\Activity\BackdatePermission;
use App\Models\Modules\Activity\EmployeeTask;

class ActivityAuthorizationService
{
    public function belongsToBusinessUnit(?User $user, mixed $businessUnitId): bool
    {
        if (! $user || ! $businessUnitId) {
            return false;
        }

        return $user->isSuperAdmin() || $user->activeBusinessUnits()
            ->where('business_unit_id', (int) $businessUnitId)
            ->exists();
    }

    public function canViewDepartmentTasks(User $user, int $businessUnitId): bool
    {
        if (! $this->belongsToBusinessUnit($user, $businessUnitId)) {
            return false;
        }

        if ($user->isSuperAdmin() || in_array(
            $user->getAccessLevel($businessUnitId),
            ['team_leader', 'department_head', 'general_manager', 'executive', 'c_level'],
            true,
        )) {
            return true;
        }

        return $user->activeBusinessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->where('is_activity_admin', true)
            ->where('is_activity_report_access', true)
            ->exists();
    }

    public function canEditTask(User $user, EmployeeTask $task, int $businessUnitId): bool
    {
        return $this->belongsToBusinessUnit($user, $businessUnitId)
            && (int) $task->business_unit_id === $businessUnitId
            && ((int) $task->created_by === (int) $user->id || $user->isSuperAdmin());
    }

    public function canParticipate(User $user, int $businessUnitId, int $departmentId): bool
    {
        return $user->activeBusinessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->where('department_id', $departmentId)
            ->exists();
    }

    public function canDecideBackdate(User $user, BackdatePermission $permission, int $businessUnitId): bool
    {
        if ((int) $permission->business_unit_id !== $businessUnitId
            || ! $this->belongsToBusinessUnit($user, $businessUnitId)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $assignment = $user->activeBusinessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->where('department_id', $permission->department_id)
            ->whereHas('position', fn ($query) => $query->whereIn('access_level', [
                'department_head', 'general_manager', 'executive', 'c_level',
            ]))
            ->exists();

        return $assignment || $user->isAdminInBuOrAncestor('is_activity_admin', $businessUnitId);
    }
}
