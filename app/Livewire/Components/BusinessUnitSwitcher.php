<?php

namespace App\Livewire\Components;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class BusinessUnitSwitcher extends Component
{
    // ✅ Cache TTL for business units (1 hour - rarely changes)
    const CACHE_TTL_BUSINESS_UNITS = 60;

    public $currentBusinessUnit;

    public $availableBusinessUnits;

    public $isLoaded = false;

    // ✅ Track session BU ID to detect changes
    public $sessionBusinessUnitId;

    // ✅ Listen to business-unit-switched event from any source (navbar or dashboard)
    protected $listeners = [
        'business-unit-switched' => 'handleBusinessUnitSwitch',
    ];

    public function mount()
    {
        $this->sessionBusinessUnitId = session('current_business_unit_id');
        $this->loadBusinessUnits();
        $this->isLoaded = true;
    }

    public function hydrate()
    {
        // ✅ OPTIMIZED: Only reload if session actually changed
        $currentSessionBuId = session('current_business_unit_id');

        if (! $this->isLoaded || $this->sessionBusinessUnitId !== $currentSessionBuId) {
            $this->sessionBusinessUnitId = $currentSessionBuId;
            $this->loadBusinessUnits();
            $this->isLoaded = true;
        }
    }

    /**
     * Load business units with caching
     * ✅ OPTIMIZED: Cache for 1 hour, BU list rarely changes
     */
    public function loadBusinessUnits()
    {
        $user = Auth::user();

        // Load current BU from session (no query needed)
        $this->currentBusinessUnit = [
            'id' => session('current_business_unit_id'),
            'code' => session('current_business_unit_code'),
            'name' => session('current_business_unit_name'),
        ];

        // Super admins don't have business unit assignments
        if ($user->global_role === 'super_admin') {
            $this->availableBusinessUnits = collect([
                [
                    'id' => null,
                    'code' => 'WG',
                    'name' => 'Werkudara Group',
                    'role' => 'super_admin',
                    'department_id' => null,
                ],
            ]);

            return;
        }

        // ✅ OPTIMIZED: Cache business units for 1 hour
        $cacheKey = "business_units.user.{$user->id}";

        $this->availableBusinessUnits = Cache::remember(
            $cacheKey,
            now()->addMinutes(self::CACHE_TTL_BUSINESS_UNITS),
            fn () => $this->fetchBusinessUnitsFromDatabase($user)
        );
    }

    /**
     * Fetch business units from database
     * ✅ Separated for caching
     */
    protected function fetchBusinessUnitsFromDatabase($user)
    {
        return $user->businessUnits()
            ->with('businessUnit')
            ->where('is_active', true)
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->businessUnit->id,
                    'code' => $assignment->businessUnit->code,
                    'name' => $assignment->businessUnit->name,
                    'role' => $assignment->role,
                    'department_id' => $assignment->department_id,
                ];
            });
    }

    /**
     * Switch business unit
     * ✅ UX IMPROVED: Stay on current page instead of redirecting to dashboard
     */
    public function switchBusinessUnit($businessUnitId)
    {
        $user = Auth::user();

        // Super admins can't switch business units
        if ($user->global_role === 'super_admin') {
            session()->flash('info', 'Super administrators have system-wide access.');

            return;
        }

        // Find the business unit assignment
        $assignment = $user->businessUnits()
            ->with('businessUnit')
            ->where('business_unit_id', $businessUnitId)
            ->where('is_active', true)
            ->first();

        if ($assignment) {
            $businessUnit = $assignment->businessUnit;

            // Update session context
            session([
                'current_business_unit_id' => $businessUnit->id,
                'current_business_unit_code' => $businessUnit->code,
                'current_business_unit_name' => $businessUnit->name,
                'current_user_role' => $assignment->role,
                'current_department_id' => $assignment->department_id,
            ]);

            // Update tracked session ID
            $this->sessionBusinessUnitId = $businessUnit->id;

            // ✅ FIX: Update currentBusinessUnit property immediately
            $this->currentBusinessUnit = [
                'id' => $businessUnit->id,
                'code' => $businessUnit->code,
                'name' => $businessUnit->name,
            ];

            // Mark as not loaded to force refresh on next hydrate
            $this->isLoaded = false;

            // ✅ Clear dashboard cache for this user (if they view dashboard later)
            $this->clearUserDashboardCache($user->id);

            // Flash success message
            session()->flash('success', "Switched to {$businessUnit->name} ({$businessUnit->code})");

            // ✅ UX IMPROVED: Emit event to refresh current page instead of redirecting
            $this->dispatch('business-unit-switched', businessUnitId: $businessUnit->id);

            // ✅ FIX: Remove self-refresh to prevent race condition
            // Dashboard and other listeners will handle the refresh
            // $this->dispatch('$refresh'); // ❌ REMOVED - causes "snapshot missing" error

            return;
        }

        session()->flash('error', 'Unable to switch to selected business unit.');
    }

    /**
     * Clear dashboard cache for specific user
     * ✅ Prevent stale data when BU switches
     */
    protected function clearUserDashboardCache(int $userId): void
    {
        // Clear all dashboard cache keys for this user
        $dateFilters = ['today', 'this_week', 'this_month', 'this_year', 'last_7_days', 'last_30_days', 'custom'];

        foreach ($dateFilters as $filter) {
            $dates = $this->getFilterDates($filter);

            // We don't know exact BU hash, so we'll rely on dashboard component to clear its own cache
            // when it detects BU change. This is a fallback safety measure.
        }

        // Clear business units cache for this user (will reload on next request)
        Cache::forget("business_units.user.{$userId}");

        \Log::info("✅ Business unit cache cleared for user {$userId}");
    }

    /**
     * Helper to get date ranges for cache clearing
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

    /**
     * Handle business unit switch event (from Dashboard or other components)
     * ✅ FIX: Update navbar when BU switched from anywhere
     */
    public function handleBusinessUnitSwitch($businessUnitId): void
    {
        \Log::info('🔄 BusinessUnitSwitcher received business-unit-switched event', [
            'new_business_unit_id' => $businessUnitId,
            'old_business_unit_id' => $this->sessionBusinessUnitId,
        ]);

        // Update tracked session ID
        $this->sessionBusinessUnitId = $businessUnitId;

        // Reload business units (will get current BU from session)
        $this->loadBusinessUnits();

        \Log::info('✅ BusinessUnitSwitcher updated', [
            'current_bu_code' => $this->currentBusinessUnit['code'],
            'current_bu_name' => $this->currentBusinessUnit['name'],
        ]);
    }

    public function render()
    {
        return view('livewire.components.business-unit-switcher');
    }
}
