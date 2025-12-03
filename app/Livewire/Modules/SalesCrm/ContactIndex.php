<?php

namespace App\Livewire\Modules\SalesCrm;

use App\Livewire\Traits\HasFilters;
use App\Livewire\Traits\HasLazyLoading;
use App\Models\Modules\SalesCrm\Contact;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ContactIndex extends Component
{
    use HasFilters, HasLazyLoading, WithPagination;

    // Cache TTL constants (following v.2.2 pattern)
    const CACHE_TTL_STATS = 300; // 5 minutes for stats

    // Session & BU context
    public $businessUnitId;

    // Event listeners
    protected $listeners = [
        'business-unit-switched' => 'handleBusinessUnitSwitch',
        'contact-created' => 'refreshContacts',
        'contact-updated' => 'refreshContacts',
    ];

    public function mount(): void
    {
        $this->businessUnitId = session('current_business_unit_id');

        // Initialize filters with defaults
        $this->filters = [
            'search' => '',
            'status' => '',
            'category' => '',
            'company' => '',
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

    public function refreshContacts(): void
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

        Cache::forget("contact_stats_{$buId}_{$userId}");
    }

    #[Computed]
    public function contacts()
    {
        if (! $this->readyToLoad) {
            return collect(); // Empty while loading
        }

        // Get BU ID from session (single source of truth)
        $buId = session('current_business_unit_id') ?? $this->businessUnitId;
        $user = Auth::user();

        $query = Contact::query()
            ->where('business_unit_id', $buId)
            ->when(! $user->hasAnyRole(['super_admin', 'admin']), fn ($q) => $q->where('assigned_to', $user->id))
            ->when($this->filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($this->filters['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->when($this->filters['company'] ?? null, fn ($q, $company) => $q->where('company', 'like', "%{$company}%"));

        // Optimized search using FULLTEXT index if available
        if ($search = $this->filters['search'] ?? null) {
            if (config('database.default') === 'mysql') {
                // Use FULLTEXT search for better performance on large datasets
                $query->whereRaw(
                    'MATCH(name, company, email, phone, mobile, position) AGAINST(? IN BOOLEAN MODE)',
                    [$search.'*']
                );
            } else {
                // Fallback for SQLite/PostgreSQL
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%");
                });
            }
        }

        // Eager load relationships to prevent N+1
        // Select only needed columns to reduce memory usage
        return $query
            ->with([
                'assignedTo:id,name,email',
                'source:id,contact_id,source_type,activity_type',
                'lastActivity:id,contact_id,activity_type,activity_date,status',
            ])
            ->select([
                'id', 'business_unit_id', 'code', 'name', 'email', 'phone', 'mobile',
                'company', 'department', 'position', 'status', 'category',
                'assigned_to', 'created_at', 'updated_at',
            ])
            ->latest('created_at')
            ->paginate(20);
    }

    #[Computed]
    public function stats()
    {
        if (! $this->readyToLoad) {
            return [
                'total' => 0,
                'active' => 0,
                'leads' => 0,
                'customers' => 0,
            ];
        }

        $buId = session('current_business_unit_id') ?? $this->businessUnitId;
        $user = Auth::user();

        // Cache key based on user and business unit
        $cacheKey = "contact_stats_{$buId}_{$user->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL_STATS, function () use ($buId, $user) {
            $baseQuery = Contact::where('business_unit_id', $buId);

            if (! $user->hasAnyRole(['super_admin', 'admin'])) {
                $baseQuery->where('assigned_to', $user->id);
            }

            // Single optimized query using conditional aggregation
            $stats = $baseQuery
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN category = "lead" THEN 1 ELSE 0 END) as leads,
                    SUM(CASE WHEN category = "customer" THEN 1 ELSE 0 END) as customers
                ')
                ->first();

            return [
                'total' => (int) $stats->total,
                'active' => (int) $stats->active,
                'leads' => (int) $stats->leads,
                'customers' => (int) $stats->customers,
            ];
        });
    }

    public function render()
    {
        return view('livewire.modules.sales-crm.contact-index')
            ->layout('layouts.app');
    }
}
