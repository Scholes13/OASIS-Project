<?php

namespace App\Livewire\Modules\SalesCrm;

use App\Livewire\Traits\HasFilters;
use App\Livewire\Traits\HasLazyLoading;
use App\Models\Modules\SalesCrm\Activity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityIndex extends Component
{
    use HasFilters, HasLazyLoading, WithPagination;

    // Cache TTL constants (following v.2.2 pattern)
    const CACHE_TTL_STATS = 300; // 5 minutes for stats

    // Session & BU context
    public $businessUnitId;

    // Event listeners
    protected $listeners = [
        'business-unit-switched' => 'handleBusinessUnitSwitch',
        'activity-created' => 'refreshActivities',
        'activity-updated' => 'refreshActivities',
    ];

    public function mount(): void
    {
        $this->businessUnitId = session('current_business_unit_id');

        // Initialize filters with defaults
        $this->filters = [
            'search' => '',
            'activity_type' => '',
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'contact_id' => '',
        ];
    }

    public function hydrate(): void
    {
        // Re-check BU after each request
        $sessionBuId = session('current_business_unit_id');
        if ($this->businessUnitId != $sessionBuId) {
            $this->businessUnitId = $sessionBuId;
            $this->resetLazyLoad(); // Trigger data reload
        }
    }

    public function handleBusinessUnitSwitch($businessUnitId): void
    {
        // Update session FIRST (single source of truth - Bug #5 pattern)
        session(['current_business_unit_id' => $businessUnitId]);

        // Update property (for UI binding)
        $this->businessUnitId = $businessUnitId;

        // Reload data (reads from session)
        $this->resetLazyLoad();
        $this->resetFilters();

        // Dispatch completion event to hide loader
        $this->dispatch('business-unit-switched-complete');
    }

    public function refreshActivities(): void
    {
        $this->clearCache();
        $this->resetLazyLoad();
    }

    /**
     * Clear cached stats when data changes
     */
    protected function clearCache(): void
    {
        $buId = session('current_business_unit_id') ?? $this->businessUnitId;
        $userId = Auth::id();

        Cache::forget("activity_stats_{$buId}_{$userId}");
    }

    #[Computed]
    public function activities()
    {
        if (! $this->readyToLoad) {
            return collect(); // Empty while loading
        }

        // Get BU ID from session (single source of truth)
        $buId = session('current_business_unit_id') ?? $this->businessUnitId;
        $user = Auth::user();

        $query = Activity::query()
            ->where('business_unit_id', $buId)
            ->when(! $user->hasAnyRole(['super_admin', 'admin']), fn ($q) => $q->where('user_id', $user->id))
            ->when($this->filters['activity_type'] ?? null, fn ($q, $type) => $q->where('activity_type', $type))
            ->when($this->filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($this->filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('activity_date', '>=', $date))
            ->when($this->filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('activity_date', '<=', $date))
            ->when($this->filters['contact_id'] ?? null, fn ($q, $contactId) => $q->where('contact_id', $contactId));

        // Optimized search using FULLTEXT index if available
        if ($search = $this->filters['search'] ?? null) {
            // Sanitize search input to prevent SQL injection
            $sanitizedSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);

            if (config('database.default') === 'mysql') {
                // Sanitize FULLTEXT Boolean mode special characters: + - * < > ( ) ~ "
                $fulltextSearch = preg_replace('/[+\-*<>()~"]/', ' ', $search);
                $fulltextSearch = trim($fulltextSearch);

                // Only execute FULLTEXT search if non-empty after sanitization
                if ($fulltextSearch) {
                    // Use FULLTEXT search for better performance on large datasets
                    $query->where(function ($q) use ($fulltextSearch, $sanitizedSearch) {
                        $q->whereRaw(
                            'MATCH(title, description) AGAINST(? IN BOOLEAN MODE)',
                            [$fulltextSearch.'*']
                        )->orWhere('location', 'like', "%{$sanitizedSearch}%");
                    });
                } else {
                    // Fallback to LIKE if sanitized search is empty
                    $query->where(function ($q) use ($sanitizedSearch) {
                        $q->where('title', 'like', "%{$sanitizedSearch}%")
                            ->orWhere('description', 'like', "%{$sanitizedSearch}%")
                            ->orWhere('location', 'like', "%{$sanitizedSearch}%");
                    });
                }
            } else {
                // Fallback for SQLite/PostgreSQL
                $query->where(function ($q) use ($sanitizedSearch) {
                    $q->where('title', 'like', "%{$sanitizedSearch}%")
                        ->orWhere('description', 'like', "%{$sanitizedSearch}%")
                        ->orWhere('location', 'like', "%{$sanitizedSearch}%");
                });
            }
        }

        // Eager load relationships to prevent N+1
        // Only load contact if contact_id is not null
        return $query
            ->with([
                'user:id,name,email', // Select only needed columns
                'contact:id,code,name,company,email,phone', // Select only needed columns
            ])
            ->latest('activity_date')
            ->latest('created_at')
            ->paginate(20);
    }

    #[Computed]
    public function stats()
    {
        if (! $this->readyToLoad) {
            return [
                'total' => 0,
                'completed' => 0,
                'planned' => 0,
                'today' => 0,
            ];
        }

        $buId = session('current_business_unit_id') ?? $this->businessUnitId;
        $user = Auth::user();

        // Cache key based on user and business unit
        $cacheKey = "activity_stats_{$buId}_{$user->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL_STATS, function () use ($buId, $user) {
            $baseQuery = Activity::where('business_unit_id', $buId);

            if (! $user->hasAnyRole(['super_admin', 'admin'])) {
                $baseQuery->where('user_id', $user->id);
            }

            // Single optimized query using conditional aggregation
            // Use database-agnostic date comparison for cross-platform compatibility
            $today = now()->toDateString();
            $stats = $baseQuery
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = "planned" THEN 1 ELSE 0 END) as planned,
                    SUM(CASE WHEN DATE(activity_date) = ? THEN 1 ELSE 0 END) as today
                ', [$today])
                ->first();

            return [
                'total' => (int) $stats->total,
                'completed' => (int) $stats->completed,
                'planned' => (int) $stats->planned,
                'today' => (int) $stats->today,
            ];
        });
    }

    public function render()
    {
        return view('livewire.modules.sales-crm.activity-index')
            ->layout('layouts.app');
    }
}
