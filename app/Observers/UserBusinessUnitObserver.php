<?php

namespace App\Observers;

use App\Models\Core\UserBusinessUnit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Observer for UserBusinessUnit model.
 * Handles cache invalidation when user BU assignments change.
 */
class UserBusinessUnitObserver
{
    /**
     * Handle the UserBusinessUnit "saved" event.
     * Triggered on both create and update.
     */
    public function saved(UserBusinessUnit $mapping): void
    {
        $this->invalidateUserCache($mapping->user_id, $mapping->business_unit_id);
    }

    /**
     * Handle the UserBusinessUnit "deleted" event.
     */
    public function deleted(UserBusinessUnit $mapping): void
    {
        $this->invalidateUserCache($mapping->user_id, $mapping->business_unit_id);
    }

    /**
     * Invalidate all cached data for a user.
     *
     * Navigation is no longer cached (built fresh every request for
     * correctness), so only the business-unit list cache needs clearing.
     */
    protected function invalidateUserCache(int $userId, ?int $businessUnitId = null): void
    {
        Cache::forget("bu_list:{$userId}");

        Log::debug('User cache invalidated', [
            'user_id' => $userId,
            'business_unit_id' => $businessUnitId,
        ]);
    }
}
