<?php

namespace App\Observers;

use App\Models\Core\BusinessUnit;
use App\Models\Core\User;
use Illuminate\Support\Facades\Cache;

class BusinessUnitObserver
{
    /**
     * Invalidate the cached BU id => parent_id map whenever the hierarchy
     * is mutated.  Without this, {@see User::isAdminInBuOrAncestor()}
     * would keep walking the old hierarchy after a parent reassignment
     * until the cache TTL expires.
     */
    public function saved(BusinessUnit $businessUnit): void
    {
        if ($businessUnit->wasChanged('parent_id') || $businessUnit->wasRecentlyCreated) {
            Cache::forget(User::BU_PARENT_MAP_CACHE_KEY);
        }
    }

    public function deleted(BusinessUnit $businessUnit): void
    {
        Cache::forget(User::BU_PARENT_MAP_CACHE_KEY);
    }

    public function restored(BusinessUnit $businessUnit): void
    {
        Cache::forget(User::BU_PARENT_MAP_CACHE_KEY);
    }
}
