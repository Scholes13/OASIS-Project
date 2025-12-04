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

        // Load current BU from session (logo is set by middleware or switchBusinessUnit)
        $this->currentBusinessUnit = [
            'id' => session('current_business_unit_id'),
            'code' => session('current_business_unit_code'),
            'name' => session('current_business_unit_name'),
            'logo' => session('current_business_unit_logo'),
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
                    'logo' => $assignment->businessUnit->logo,
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
                'current_business_unit_logo' => $businessUnit->logo,
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
                'logo' => $businessUnit->logo,
            ];

            // Mark as not loaded to force refresh on next hydrate
            $this->isLoaded = false;

            // ✅ Clear business unit cache when switching (not dashboard cache)
            $this->clearBusinessUnitCache($user->id);

            // ✅ UX IMPROVED: Emit event to refresh current page instead of redirecting
            // Each component that listens will process and dispatch 'complete' when done
            $this->dispatch('business-unit-switched', businessUnitId: $businessUnit->id);

            // ✅ ORCHESTRATOR: Header acknowledges immediately since it's already updated
            // The header (this component) updates its own state synchronously above
            $this->dispatch('bu-switch-acknowledge', component: 'header');

            return;
        }

        session()->flash('error', 'Unable to switch to selected business unit.');
    }

    /**
     * Clear business unit cache for specific user
     * ✅ Ensures fresh BU list loaded after switch
     */
    protected function clearBusinessUnitCache(int $userId): void
    {
        // Clear business units cache for this user (will reload on next request)
        Cache::forget("business_units.user.{$userId}");

        // Note: Dashboard cache is managed by Dashboard component itself
        // Dashboard detects BU change via session and clears its own cache
        // This method only handles BU switcher's cache

        \Log::info("✅ Business unit cache cleared for user {$userId}");
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
