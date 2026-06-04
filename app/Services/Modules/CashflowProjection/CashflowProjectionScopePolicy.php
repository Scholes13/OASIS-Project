<?php

namespace App\Services\Modules\CashflowProjection;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use Illuminate\Support\Collection;

/**
 * Finance assignment + department option helpers for the Cashflow
 * Projection module.
 *
 * Lifted verbatim from CashflowProjectionController:
 *  - userAssignedToDepartment
 *  - userHasFinanceAssignment
 *  - department resolution (own + linked) used by index() and export()
 *  - linked business unit option payload
 */
class CashflowProjectionScopePolicy
{
    public function userAssignedToDepartment(User $user, int $businessUnitId, int $departmentId): bool
    {
        return $user->activeBusinessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->where('department_id', $departmentId)
            ->exists();
    }

    public function userHasFinanceAssignment(User $user, int $businessUnitId): bool
    {
        return $user->activeBusinessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->whereHas('department', function ($query) {
                $query->whereIn('code', ['CFC', 'FIN']);
            })
            ->exists();
    }

    /**
     * Resolve the departments visible on the dashboard/export for the
     * current user (finance users see entire BU + linked BUs; non-finance
     * users see only their own department assignments).
     *
     * @param  array<int, int>  $linkedBuIds
     * @return Collection<int, Department>
     */
    public function resolveDashboardDepartments(User $user, int $businessUnitId, bool $canManageFinance, array $linkedBuIds): Collection
    {
        if ($canManageFinance) {
            $departments = Department::query()
                ->with('businessUnit')
                ->where('business_unit_id', $businessUnitId)
                ->where('is_active', true)
                ->get();
        } else {
            $assignments = $user->activeBusinessUnits()
                ->with(['department.businessUnit', 'position'])
                ->where('business_unit_id', $businessUnitId)
                ->get();

            $departments = $assignments
                ->pluck('department')
                ->filter(fn ($department) => $department instanceof Department && $department->is_active)
                ->unique('id')
                ->values();
        }

        if ($canManageFinance && count($linkedBuIds) > 0) {
            $linkedDepartments = Department::query()
                ->with('businessUnit')
                ->whereIn('business_unit_id', $linkedBuIds)
                ->where('is_active', true)
                ->get();
            $departments = $departments->merge($linkedDepartments)->unique('id')->values();
        }

        return $departments;
    }

    /**
     * @param  array<int, int>  $linkedBuIds
     * @return array<int, array{id: int, code: string, name: string}>
     */
    public function buildLinkedBusinessUnitPayload(array $linkedBuIds): array
    {
        if ($linkedBuIds === []) {
            return [];
        }

        return BusinessUnit::query()
            ->whereIn('id', $linkedBuIds)
            ->where('is_active', true)
            ->get()
            ->map(fn (BusinessUnit $bu) => [
                'id' => $bu->id,
                'code' => $bu->code,
                'name' => $bu->name,
            ])
            ->values()
            ->all();
    }
}
