<?php

namespace App\Services\Modules\Purchasing\PurchaseRequest;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;

class PRNumberingContextResolver
{
    public function resolveBusinessUnit(User $user, ?int $businessUnitId): ?BusinessUnit
    {
        if ($businessUnitId) {
            $businessUnit = BusinessUnit::find($businessUnitId);
            if ($businessUnit && $this->userHasAccessToBusinessUnit($user, $businessUnit)) {
                return $businessUnit;
            }
        }

        $sessionBuId = session('current_business_unit_id');
        if ($sessionBuId) {
            $businessUnit = BusinessUnit::find($sessionBuId);
            if ($businessUnit && $this->userHasAccessToBusinessUnit($user, $businessUnit)) {
                return $businessUnit;
            }
        }

        if ($user->global_role === 'super_admin') {
            return BusinessUnit::where('is_active', true)->orderBy('id')->first();
        }

        if ($user->primaryDepartment && $user->primaryDepartment->businessUnit) {
            return $user->primaryDepartment->businessUnit;
        }

        return null;
    }

    public function resolveDepartment(User $user, BusinessUnit $businessUnit, ?int $departmentId): ?Department
    {
        if ($departmentId) {
            $department = Department::where('id', $departmentId)
                ->where('business_unit_id', $businessUnit->id)
                ->where('is_active', true)
                ->first();
            if ($department) {
                return $department;
            }
        }

        if ($user->primaryDepartment && $user->primaryDepartment->business_unit_id === $businessUnit->id) {
            return $user->primaryDepartment;
        }

        if ($user->global_role === 'super_admin') {
            return $businessUnit->activeDepartments()->orderBy('id')->first();
        }

        $assignment = $user->businessUnits()
            ->where('business_unit_id', $businessUnit->id)
            ->where('is_active', true)
            ->first();

        return $assignment && $assignment->department_id
            ? Department::find($assignment->department_id)
            : null;
    }

    public function userHasAccessToBusinessUnit(User $user, BusinessUnit $businessUnit): bool
    {
        if ($user->global_role === 'super_admin') {
            return true;
        }

        return $user->businessUnits()
            ->where('business_unit_id', $businessUnit->id)
            ->where('is_active', true)
            ->exists();
    }

    public function availableBusinessUnits(User $user): array
    {
        if ($user->global_role === 'super_admin') {
            return BusinessUnit::where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn ($bu) => $this->businessUnitData($bu))
                ->toArray();
        }

        return $user->businessUnits()
            ->with(['businessUnit.activeDepartments'])
            ->where('is_active', true)
            ->get()
            ->map(function ($assignment) {
                return $this->businessUnitData($assignment->businessUnit, $assignment->role);
            })
            ->toArray();
    }

    private function businessUnitData(BusinessUnit $businessUnit, ?string $userRole = null): array
    {
        $data = [
            'id' => $businessUnit->id,
            'code' => $businessUnit->code,
            'name' => $businessUnit->name,
        ];

        if ($userRole !== null) {
            $data['user_role'] = $userRole;
        }

        $data['departments'] = $businessUnit->activeDepartments()
            ->orderBy('name')
            ->get()
            ->map(fn ($department) => [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
            ])
            ->toArray();

        return $data;
    }
}
