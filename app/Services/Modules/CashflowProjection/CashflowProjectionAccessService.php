<?php

namespace App\Services\Modules\CashflowProjection;

use App\Models\Core\User;

class CashflowProjectionAccessService
{
    /**
     * User can access module when they are super admin, top management
     * (CEO/MD/Chief of Staff at executive level), Head Department, or
     * Finance/CFC in current BU.
     *
     * Top management widening (PO 2026-05-26) keeps this gate consistent
     * with `access-purchasing-admin`, `access-it-support`, `view-reports`
     * etc. that already accept any active top-management position.
     */
    public function canAccess(User $user, ?int $businessUnitId): bool
    {
        if (! $businessUnitId) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasTopManagementAccess()) {
            return true;
        }

        return $this->isDepartmentHeadInBusinessUnit($user, $businessUnitId)
            || $this->isFinanceInBusinessUnit($user, $businessUnitId);
    }

    /**
     * User can manage module (MVP same as access).
     */
    public function canManage(User $user, ?int $businessUnitId): bool
    {
        return $this->canAccess($user, $businessUnitId);
    }

    /**
     * Check if user is a Finance/CFC member in the given BU.
     * Finance users can: view dashboard, manage settings, export data.
     * Non-finance (HoD) can only: input entries for their department.
     */
    public function isFinanceUser(User $user, ?int $businessUnitId): bool
    {
        if (! $businessUnitId) {
            return false;
        }

        return $this->isFinanceInBusinessUnit($user, $businessUnitId);
    }

    protected function isDepartmentHeadInBusinessUnit(User $user, int $businessUnitId): bool
    {
        return $user->activeBusinessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->whereHas('position', function ($query) {
                $query->where('level', 'hod')
                    ->orWhere('access_level', 'department_head');
            })
            ->exists();
    }

    protected function isFinanceInBusinessUnit(User $user, int $businessUnitId): bool
    {
        return $user->activeBusinessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->whereHas('department', function ($query) {
                // Strict whitelist on department `code`.  We previously also
                // matched on a fuzzy `name LIKE '%Finance%'` clause, which
                // accidentally granted finance access to any department
                // whose display name happened to contain the word
                // "Finance" (e.g. "Project Finance Reporting").
                $query->whereIn('code', ['CFC', 'FIN']);
            })
            ->exists();
    }
}
