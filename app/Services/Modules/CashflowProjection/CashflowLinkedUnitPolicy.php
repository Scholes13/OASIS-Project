<?php

namespace App\Services\Modules\CashflowProjection;

use App\Models\Core\BusinessUnit;
use App\Models\Core\User;
use Illuminate\Support\Collection;

class CashflowLinkedUnitPolicy
{
    public function canLink(User $user, int $hostBusinessUnitId, int $targetBusinessUnitId): bool
    {
        if ($hostBusinessUnitId === $targetBusinessUnitId) {
            return false;
        }

        $units = BusinessUnit::query()
            ->whereIn('id', [$hostBusinessUnitId, $targetBusinessUnitId])
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        if ($units->count() !== 2) {
            return false;
        }

        if ($user->activeBusinessUnits()->where('business_unit_id', $targetBusinessUnitId)->exists()) {
            return true;
        }

        $host = $units->get($hostBusinessUnitId);
        $target = $units->get($targetBusinessUnitId);

        return $host->isParentOf($target) || $target->isParentOf($host);
    }

    /** @return Collection<int, BusinessUnit> */
    public function options(User $user, int $hostBusinessUnitId, array $excludedIds = []): Collection
    {
        return BusinessUnit::query()
            ->where('is_active', true)
            ->where('id', '!=', $hostBusinessUnitId)
            ->whereNotIn('id', $excludedIds)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'parent_id'])
            ->filter(fn (BusinessUnit $unit): bool => $this->canLink($user, $hostBusinessUnitId, $unit->id))
            ->values();
    }
}
