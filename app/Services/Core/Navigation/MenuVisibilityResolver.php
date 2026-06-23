<?php

namespace App\Services\Core\Navigation;

use App\Models\Core\Department;
use App\Models\Core\User;
use App\Services\Modules\CashflowProjection\CashflowProjectionAccessService;

/**
 * Centralizes the canAccess* checks used by the navigation builder.
 *
 * Pulled out of {@see \App\Services\Core\NavigationService} so the
 * orchestrator stays under the 350-line service cap.  Each method here
 * mirrors the legacy private NavigationService check verbatim — see the
 * original code for behaviour parity rationale.
 */
class MenuVisibilityResolver
{
    public function __construct(
        protected CashflowProjectionAccessService $cashflowProjectionAccessService,
    ) {}

    /** Purchasing module visibility. */
    public function canAccessPurchasing(User $user, int $businessUnitId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasTopManagementAccess()) {
            return true;
        }

        return $user->businessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->exists();
    }

    /** Purchasing admin visibility (cascade-aware). */
    public function canAccessPurchasingAdmin(User $user, int $businessUnitId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasTopManagementAccess()) {
            return true;
        }

        return $user->isAdminInBuOrAncestor('is_purchasing_admin', $businessUnitId);
    }

    public function canAccessGaStockReview(User $user, int $businessUnitId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $departmentId = (int) session('current_department_id');

        if ($departmentId <= 0) {
            return false;
        }

        return Department::query()
            ->where('id', $departmentId)
            ->where('business_unit_id', $businessUnitId)
            ->where('is_ga_stock_review_department', true)
            ->exists();
    }

    /** Activity tracking module visibility. */
    public function canAccessActivityTracking(User $user, int $businessUnitId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasTopManagementAccess()) {
            return true;
        }

        return $user->businessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->exists();
    }

    /** Activity admin visibility (cascade-aware). */
    public function canAccessActivityAdmin(User $user, int $businessUnitId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasTopManagementAccess()) {
            return true;
        }

        return $user->isAdminInBuOrAncestor('is_activity_admin', $businessUnitId);
    }

    /**
     * Sales CRM visibility.
     *
     * NOTE: currently restricted to Super Admin only (Beta feature).
     * Wrapped in a feature flag so deployment toggles cleanly.
     */
    public function canAccessSalesCrm(User $user, int $businessUnitId): bool
    {
        if (! config('features.sales_crm')) {
            return false;
        }

        return $user->isSuperAdmin();
    }

    /** Administration section visibility. */
    public function canAccessAdministration(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /** IT Support admin visibility (cascade-aware). */
    public function canAccessItSupportAdmin(User $user, int $businessUnitId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasTopManagementAccess()) {
            return true;
        }

        return $user->isAdminInBuOrAncestor('is_it_support_admin', $businessUnitId);
    }

    /** Cashflow projection module visibility. */
    public function canAccessCashflowProjection(User $user, int $businessUnitId): bool
    {
        return $this->cashflowProjectionAccessService->canAccess($user, $businessUnitId);
    }

    /** Whether the current cashflow user is finance (drives 3-page layout). */
    public function isFinanceUser(User $user, int $businessUnitId): bool
    {
        return $this->cashflowProjectionAccessService->isFinanceUser($user, $businessUnitId);
    }
}
