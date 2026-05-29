<?php

namespace App\Services\Modules\Purchasing\PurchaseRequest;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Dashboard cache invalidation helper for Purchase Request mutations.
 *
 * Owns the cache key strategy + invalidation previously inlined in
 * PurchaseRequestService. Behavior preserved verbatim.
 */
class PrCacheManager
{
    /**
     * Clear dashboard cache for all users affected by a Purchase Request mutation.
     *
     * Targets the PR creator, every approver involved, and the last modifier.
     * Call after any PR create / update / delete / approve / reject side effect.
     */
    public function clearForPurchaseRequest(PurchaseRequest $pr): void
    {
        // Clear cache for PR creator
        $this->clearForUser($pr->user_id);

        // Clear cache for all approvers involved
        $approvers = $pr->approvals()->pluck('approver_id')->unique();
        foreach ($approvers as $approverId) {
            $this->clearForUser($approverId);
        }

        // Clear cache for last modifier if exists
        if ($pr->last_modified_by) {
            $this->clearForUser($pr->last_modified_by);
        }
    }

    /**
     * Clear dashboard cache for a specific user.
     *
     * Walks every supported date filter variation and clears stats, chart, and
     * activities buckets for the user's full set of business unit IDs.
     */
    public function clearForUser(int $userId): void
    {
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        // Get all possible business unit combinations for this user
        $businessUnits = $user->businessUnits()->with('businessUnit.children')->get();
        $buIds = [];
        foreach ($businessUnits as $userBU) {
            if ($userBU->businessUnit) {
                $buIds[] = $userBU->businessUnit->id;
            }
        }

        if (empty($buIds)) {
            return;
        }

        $buHash = md5(implode(',', $buIds));
        $dateFilters = ['today', 'this_week', 'this_month', 'this_year', 'last_7_days', 'last_30_days', 'custom'];

        // Clear stats cache for all date filters
        foreach ($dateFilters as $filter) {
            $dates = $this->getFilterDates($filter);
            $key = sprintf(
                'dashboard.stats.u%s.bu%s.f%s.d%s-%s',
                $userId,
                $buHash,
                $filter,
                $dates['start'],
                $dates['end']
            );
            Cache::forget($key);
        }

        // Clear activities cache
        Cache::forget(sprintf('dashboard.activities.u%s.bu%s', $userId, $buHash));

        // Clear chart cache
        foreach ($dateFilters as $filter) {
            $dates = $this->getFilterDates($filter);
            $key = sprintf(
                'dashboard.chart.bu%s.f%s.d%s-%s',
                $buHash,
                $filter,
                $dates['start'],
                $dates['end']
            );
            Cache::forget($key);
        }

        // Clear business units cache
        Cache::forget(sprintf('dashboard.business_units.u%s', $userId));

        Log::info("✅ Dashboard cache cleared for user {$userId}");
    }

    /**
     * Get start and end dates for a given filter.
     *
     * @return array{start: string, end: string}
     */
    protected function getFilterDates(string $filter): array
    {
        return match ($filter) {
            'today' => [
                'start' => now()->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ],
            'this_week' => [
                'start' => now()->startOfWeek()->format('Y-m-d'),
                'end' => now()->endOfWeek()->format('Y-m-d'),
            ],
            'this_month' => [
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end' => now()->endOfMonth()->format('Y-m-d'),
            ],
            'this_year' => [
                'start' => now()->startOfYear()->format('Y-m-d'),
                'end' => now()->endOfYear()->format('Y-m-d'),
            ],
            'last_7_days' => [
                'start' => now()->subDays(7)->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ],
            'last_30_days' => [
                'start' => now()->subDays(30)->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ],
            default => [
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end' => now()->endOfMonth()->format('Y-m-d'),
            ],
        };
    }
}
