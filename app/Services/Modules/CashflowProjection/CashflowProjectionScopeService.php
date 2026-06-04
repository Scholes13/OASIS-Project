<?php

namespace App\Services\Modules\CashflowProjection;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionLinkedUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CashflowProjectionScopeService
{
    public function __construct(
        protected CashflowProjectionAccessService $accessService
    ) {}

    /**
     * @return array<int, int>
     */
    public function allowedBusinessUnitIds(User $user, int $activeBusinessUnitId): array
    {
        if ($user->isSuperAdmin()) {
            $activeBusinessUnit = BusinessUnit::query()->with('descendants')->find($activeBusinessUnitId);

            return $activeBusinessUnit?->getAccessibleBusinessUnits() ?? [$activeBusinessUnitId];
        }

        if (! $this->accessService->isFinanceUser($user, $activeBusinessUnitId)) {
            return [$activeBusinessUnitId];
        }

        $linkedBusinessUnitIds = CashflowProjectionLinkedUnit::query()
            ->where('host_business_unit_id', $activeBusinessUnitId)
            ->pluck('linked_business_unit_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique(array_merge([$activeBusinessUnitId], $linkedBusinessUnitIds)));
    }

    /**
     * @return Collection<int, Department>
     */
    public function allowedDepartments(User $user, int $activeBusinessUnitId): Collection
    {
        if ($user->isSuperAdmin() || $this->accessService->isFinanceUser($user, $activeBusinessUnitId)) {
            return Department::query()
                ->with('businessUnit')
                ->whereIn('business_unit_id', $this->allowedBusinessUnitIds($user, $activeBusinessUnitId))
                ->where('is_active', true)
                ->orderBy('business_unit_id')
                ->orderBy('code')
                ->get();
        }

        return $user->activeBusinessUnits()
            ->with(['department.businessUnit', 'department.activeChildren.businessUnit'])
            ->where('business_unit_id', $activeBusinessUnitId)
            ->get()
            ->pluck('department')
            ->filter(fn ($department) => $department instanceof Department && $department->is_active)
            ->flatMap(function (Department $department) {
                return Department::query()
                    ->with(['businessUnit', 'activeChildren'])
                    ->whereIn('id', $department->descendantIds())
                    ->where('is_active', true)
                    ->get();
            })
            ->unique('id')
            ->sortBy('code')
            ->values();
    }

    public function allowedDepartmentQuery(User $user, int $activeBusinessUnitId): Builder
    {
        return Department::query()
            ->whereIn('id', $this->allowedDepartments($user, $activeBusinessUnitId)->pluck('id'));
    }

    public function financeCanTargetDepartment(User $user, int $activeBusinessUnitId, Department $department): bool
    {
        return $department->is_active
            && in_array((int) $department->business_unit_id, $this->allowedBusinessUnitIds($user, $activeBusinessUnitId), true);
    }

    public function nonFinanceCanTargetDepartment(User $user, int $activeBusinessUnitId, Department $department): bool
    {
        return $department->is_active
            && $user->activeBusinessUnits()
                ->where('business_unit_id', $activeBusinessUnitId)
                ->where('department_id', $department->id)
                ->exists();
    }

    public function currentActorDepartment(User $user, int $activeBusinessUnitId): ?Department
    {
        $currentDepartmentId = (int) session('current_department_id', 0);

        if ($currentDepartmentId > 0) {
            return Department::query()->find($currentDepartmentId);
        }

        return $user->activeBusinessUnits()
            ->with('department')
            ->where('business_unit_id', $activeBusinessUnitId)
            ->first()?->department;
    }
}
