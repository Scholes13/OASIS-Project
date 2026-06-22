<?php

namespace App\Services\Modules\Purchasing\Shared;

use App\Models\Core\User;

/**
 * Resolves the user holding a given approval-authority role for a
 * business unit / department context.
 *
 * Centralizes the lookups previously duplicated inside
 * `ApprovalWorkflowService` (department head, finance manager,
 * general manager, director).  The threshold-based rule engine that
 * decides *which* roles are required for a given purchase request
 * remains in `ApprovalWorkflowService` and calls into this resolver.
 *
 * Behaviour parity note: the historical lookups for finance manager,
 * general manager and director scoped by business unit; the historical
 * department head lookup was scoped by department only.  We preserve
 * those exact rules here and accept (but do not apply) `business_unit_id`
 * for `findDepartmentHead` so the API remains uniform across roles and
 * leaves room for future BU-scoped tightening without further callsite
 * changes.
 */
class ApprovalAuthorityResolver
{
    /**
     * Find the department head for a given department.
     *
     * The historical query filters by `primary_department_id` and the
     * `department_head` role only; the BU id is currently unused but
     * kept in the signature for symmetry with the other lookups.
     */
    public function findDepartmentHead(int $businessUnitId, int $departmentId): ?User
    {
        return User::whereHas('roles', function ($query) {
            $query->where('name', 'department_head');
        })
            ->where('primary_department_id', $departmentId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Find the finance manager scoped to the given business unit.
     */
    public function findFinanceManager(int $businessUnitId): ?User
    {
        return $this->findRoleHolderInBusinessUnit('finance_manager', $businessUnitId);
    }

    /**
     * Find the general manager scoped to the given business unit.
     */
    public function findGeneralManager(int $businessUnitId): ?User
    {
        return $this->findRoleHolderInBusinessUnit('general_manager', $businessUnitId);
    }

    /**
     * Find the director scoped to the given business unit.
     */
    public function findDirector(int $businessUnitId): ?User
    {
        return $this->findRoleHolderInBusinessUnit('director', $businessUnitId);
    }

    /**
     * Internal helper: locate the first active user that holds `$roleName`
     * and has an active `businessUnits` membership in `$businessUnitId`.
     */
    private function findRoleHolderInBusinessUnit(string $roleName, int $businessUnitId): ?User
    {
        return User::whereHas('roles', function ($query) use ($roleName) {
            $query->where('name', $roleName);
        })
            ->whereHas('businessUnits', function ($query) use ($businessUnitId) {
                $query->where('business_unit_id', $businessUnitId)
                    ->where('is_active', true);
            })
            ->where('is_active', true)
            ->first();
    }
}
