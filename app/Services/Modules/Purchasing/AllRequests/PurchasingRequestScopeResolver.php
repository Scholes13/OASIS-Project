<?php

namespace App\Services\Modules\Purchasing\AllRequests;

use App\Models\Core\BusinessUnit;
use App\Models\Core\User;

class PurchasingRequestScopeResolver
{
    /** @return array<int, int> */
    public function businessUnitIds(User $user, int $selectedBusinessUnitId): array
    {
        if ($selectedBusinessUnitId <= 0
            || ! in_array($selectedBusinessUnitId, $user->getAccessibleBusinessUnitIds(), true)) {
            return [];
        }

        if (! $user->isSuperAdmin() && ! $user->hasTopManagementAccess()) {
            return [$selectedBusinessUnitId];
        }

        return BusinessUnit::find($selectedBusinessUnitId)?->getAccessibleBusinessUnits() ?? [];
    }

    public function canAccess(User $user, int $selectedBusinessUnitId, int $requestBusinessUnitId): bool
    {
        return in_array($requestBusinessUnitId, $this->businessUnitIds($user, $selectedBusinessUnitId), true);
    }
}
