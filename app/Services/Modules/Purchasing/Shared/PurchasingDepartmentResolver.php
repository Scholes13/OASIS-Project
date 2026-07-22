<?php

namespace App\Services\Modules\Purchasing\Shared;

use App\Models\Core\Department;

class PurchasingDepartmentResolver
{
    public function resolveForBusinessUnit(int $businessUnitId): Department
    {
        $strategicSourcing = Department::query()
            ->where('business_unit_id', $businessUnitId)
            ->where('code', 'SS')
            ->where('is_active', true)
            ->where('is_purchasing_department', true)
            ->get();

        $departments = $strategicSourcing->isNotEmpty()
            ? $strategicSourcing
            : Department::query()
                ->where('business_unit_id', $businessUnitId)
                ->where('is_active', true)
                ->where('is_purchasing_department', true)
                ->get();

        if ($departments->count() !== 1) {
            throw new \DomainException('Exactly one Purchasing department must be configured for this business unit.');
        }

        return $departments->firstOrFail();
    }
}
