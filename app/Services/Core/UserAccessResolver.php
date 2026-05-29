<?php

namespace App\Services\Core;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;

/**
 * Resolves a user's access level, position context, BU/department reach, and
 * admin-cascade authority.
 *
 * Extracted from {@see User} to keep the most-imported model under the
 * 400-line cap.  Public method semantics mirror the legacy User methods
 * exactly — the User model proxies through this resolver via the service
 * container, so all existing `$user->xxx(...)` call sites continue to work.
 */
class UserAccessResolver
{
    /**
     * Get user's access level, optionally scoped to a specific business unit.
     */
    public function getAccessLevel(User $user, ?int $businessUnitId = null): string
    {
        if ($user->isSuperAdmin()) {
            return 'super_admin';
        }

        $position = $businessUnitId
            ? $this->getPositionInBusinessUnit($user, $businessUnitId)
            : $user->primaryPosition;

        if ($position && $position->access_level === 'executive') {
            return 'executive';
        }

        if ($position && $position->access_level === 'general_manager') {
            return 'general_manager';
        }

        if ($this->isGeneralManager($user)) {
            return 'general_manager';
        }

        if ($position && $position->level) {
            switch ($position->level) {
                case 'c_level':
                    return 'executive';
                case 'hod':
                    return 'department_head';
                case 'leader':
                    return 'team_leader';
                case 'staff':
                    return 'staff';
            }
        }

        if ($position && $position->access_level && $position->access_level !== 'staff') {
            return $position->access_level;
        }

        return 'staff';
    }

    /**
     * Get user's position in a specific business unit.
     */
    public function getPositionInBusinessUnit(User $user, int $businessUnitId): ?Position
    {
        $assignment = $user->activeBusinessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->with('position')
            ->first();

        if ($assignment && $assignment->position) {
            return $assignment->position;
        }

        return $user->primaryPosition;
    }

    /**
     * Check if user has top management access in any active business unit.
     */
    public function hasTopManagementAccess(User $user): bool
    {
        return $user->activeBusinessUnits()
            ->whereHas('position', fn ($q) => $q->topManagement())
            ->exists();
    }

    /**
     * Check if user has manager-and-above access in any active business unit.
     */
    public function hasManagerAccess(User $user): bool
    {
        return $user->activeBusinessUnits()
            ->whereHas('position', fn ($q) => $q->managerAndAbove())
            ->exists();
    }

    /**
     * Check if user has top management access in a specific parent/root BU.
     */
    public function hasTopManagementInParentBU(User $user): bool
    {
        return $user->activeBusinessUnits()
            ->whereHas('businessUnit', fn ($q) => $q->whereNull('parent_id'))
            ->whereHas('position', fn ($q) => $q->topManagement())
            ->exists();
    }

    /**
     * Determine if the user acts as a general manager for any business unit.
     */
    public function isGeneralManager(User $user): bool
    {
        return BusinessUnit::where('manager_id', $user->id)->exists();
    }

    /**
     * Business unit IDs where the user is assigned as general manager.
     *
     * @return array<int, int>
     */
    public function managedBusinessUnitIds(User $user): array
    {
        return BusinessUnit::where('manager_id', $user->id)->pluck('id')->toArray();
    }

    /**
     * Business unit IDs the user can access as general manager.
     *
     * @return array<int, int>
     */
    public function generalManagerBusinessUnitIds(User $user): array
    {
        return array_values(array_unique(array_merge(
            $this->managedBusinessUnitIds($user),
            $user->activeBusinessUnits()->pluck('business_unit_id')->toArray()
        )));
    }
}
