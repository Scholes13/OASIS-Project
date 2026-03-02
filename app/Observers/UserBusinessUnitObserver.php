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
     * This includes navigation and business unit list caches.
     */
    protected function invalidateUserCache(int $userId, ?int $businessUnitId = null): void
    {
        // Clear business unit list cache
        Cache::forget("bu_list:{$userId}");

        // Clear navigation cache for specific BU
        if ($businessUnitId) {
            Cache::forget("nav:{$userId}:{$businessUnitId}");
        }

        // Also clear navigation cache for all BUs this user might have access to
        // This is a bit aggressive but ensures consistency
        $this->clearAllNavigationCacheForUser($userId);

        Log::debug('User cache invalidated', [
            'user_id' => $userId,
            'business_unit_id' => $businessUnitId,
        ]);
    }

    /**
     * Clear all navigation caches for a user across all business units.
     * Uses pattern matching if available (Redis), otherwise clears known BUs.
     */
    protected function clearAllNavigationCacheForUser(int $userId): void
    {
        // Get all BU IDs the user has assignments for
        $buIds = UserBusinessUnit::where('user_id', $userId)
            ->pluck('business_unit_id')
            ->unique();

        foreach ($buIds as $buId) {
            Cache::forget("nav:{$userId}:{$buId}");
        }
    }
}
