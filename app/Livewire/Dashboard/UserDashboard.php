<?php

namespace App\Livewire\Dashboard;

use App\Models\Core\BusinessUnit;
use App\Models\Modules\PurchaseRequest\PrApproval;
use App\Models\Modules\PurchaseRequest\PurchaseRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

class UserDashboard extends Component
{
    // ✅ Livewire event listeners
    protected $listeners = [
        'business-unit-switched' => 'handleBusinessUnitSwitch',
    ];

    // ✅ Cache TTL Configuration (in minutes)
    const CACHE_TTL_STATS = 5;      // Stats cache for 5 minutes

    const CACHE_TTL_ACTIVITIES = 1;  // Activities cache for 1 minute (more dynamic)

    const CACHE_TTL_CHART = 5;       // Chart data cache for 5 minutes

    const CACHE_TTL_BUSINESS_UNITS = 60; // Business units cache for 1 hour (rarely changes)

    // Filter properties
    public $dateFilter = 'this_month';

    public $startDate;

    public $endDate;

    public $customRange = false;

    // Active Business Unit (single selection, not filter)
    public $activeBusinessUnitId;

    // Stats properties
    public $stats = [];

    public $recentActivities = [];

    public $chartData = [];

    public $businessUnits = [];

    public function mount(): void
    {
        $this->initializeDates();
        $this->businessUnits = $this->getAccessibleBusinessUnits();

        // ✅ FIX: Use the SAME session key as BusinessUnitSwitcher component!
        // Check global business unit session first (from header switcher)
        $globalBusinessUnitId = session('current_business_unit_id');

        if ($globalBusinessUnitId && collect($this->businessUnits)->contains('id', $globalBusinessUnitId)) {
            // Use global session value from header switcher
            $this->activeBusinessUnitId = $globalBusinessUnitId;
        } else {
            // Fallback: Check dashboard-specific session
            $dashboardSessionId = session('dashboard_active_business_unit_id');

            if ($dashboardSessionId && collect($this->businessUnits)->contains('id', $dashboardSessionId)) {
                $this->activeBusinessUnitId = $dashboardSessionId;
            } else {
                // Last fallback: Set default active BU to user's primary business unit
                $user = Auth::user();
                $primaryUserBU = $user->businessUnits->firstWhere('is_primary', true);

                // If user has primary BU, use it; otherwise use first available BU
                // ✅ FIX: Add null safety check for empty business units
                if (! empty($this->businessUnits)) {
                    $this->activeBusinessUnitId = $primaryUserBU?->business_unit_id ?? $this->businessUnits[0]['id'];
                } else {
                    // User has no business unit assignments - handle gracefully
                    $this->activeBusinessUnitId = null;
                    \Log::warning('User has no business unit assignments', ['user_id' => $user->id]);
                }
            }

            // Store in both sessions for consistency
            session([
                'current_business_unit_id' => $this->activeBusinessUnitId,
                'dashboard_active_business_unit_id' => $this->activeBusinessUnitId,
            ]);
        }

        \Log::info('🚀 Dashboard mount()', [
            'activeBusinessUnitId' => $this->activeBusinessUnitId,
            'global_session' => session('current_business_unit_id'),
            'dashboard_session' => session('dashboard_active_business_unit_id'),
        ]);

        $this->loadDashboardData();
    }

    protected function initializeDates(): void
    {
        // Set default date range based on filter
        match ($this->dateFilter) {
            'today' => [
                $this->startDate = now()->startOfDay()->format('Y-m-d'),
                $this->endDate = now()->endOfDay()->format('Y-m-d'),
            ],
            'this_week' => [
                $this->startDate = now()->startOfWeek()->format('Y-m-d'),
                $this->endDate = now()->endOfWeek()->format('Y-m-d'),
            ],
            'this_month' => [
                $this->startDate = now()->startOfMonth()->format('Y-m-d'),
                $this->endDate = now()->endOfMonth()->format('Y-m-d'),
            ],
            'this_year' => [
                $this->startDate = now()->startOfYear()->format('Y-m-d'),
                $this->endDate = now()->endOfYear()->format('Y-m-d'),
            ],
            'last_30_days' => [
                $this->startDate = now()->subDays(30)->format('Y-m-d'),
                $this->endDate = now()->format('Y-m-d'),
            ],
            'custom' => [
                $this->startDate = $this->startDate ?? now()->startOfMonth()->format('Y-m-d'),
                $this->endDate = $this->endDate ?? now()->endOfMonth()->format('Y-m-d'),
            ],
            default => [
                $this->startDate = now()->startOfMonth()->format('Y-m-d'),
                $this->endDate = now()->endOfMonth()->format('Y-m-d'),
            ],
        };
    }

    public function updatedDateFilter(): void
    {
        $this->customRange = $this->dateFilter === 'custom';
        $this->initializeDates();

        // ✅ Clear cache when date filter changes
        $this->clearDashboardCache();

        $this->loadDashboardData();
    }

    public function applyCustomDateRange(): void
    {
        $this->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        // ✅ Clear cache when custom date range is applied
        $this->clearDashboardCache();

        $this->loadDashboardData();
    }

    /**
     * Switch active business unit (single selection)
     */
    public function switchBusinessUnit(int $businessUnitId): void
    {
        \Log::info('🔄 Dashboard switchBusinessUnit CALLED', [
            'from' => $this->activeBusinessUnitId,
            'to' => $businessUnitId,
            'session_before' => session('current_business_unit_id'),
        ]);

        // Get BU details for session update
        $businessUnit = collect($this->businessUnits)->firstWhere('id', $businessUnitId);

        if (! $businessUnit) {
            \Log::warning('❌ Business unit not found', ['id' => $businessUnitId]);

            return;
        }

        // Update session with full BU data (sync with BusinessUnitSwitcher)
        session([
            'current_business_unit_id' => $businessUnit['id'],
            'current_business_unit_code' => $businessUnit['code'],
            'current_business_unit_name' => $businessUnit['name'],
            'dashboard_active_business_unit_id' => $businessUnit['id'],
        ]);

        // Update local property
        $this->activeBusinessUnitId = $businessUnitId;

        \Log::info('✅ Dashboard switchBusinessUnit COMPLETED', [
            'activeBusinessUnitId' => $this->activeBusinessUnitId,
            'global_session' => session('current_business_unit_id'),
        ]);

        // Clear cache when business unit changes
        $this->clearDashboardCache();

        // Reload dashboard data
        $this->loadDashboardData();

        // ✅ FIX: Emit same event as BusinessUnitSwitcher for consistency
        // This will trigger navbar to update too
        $this->dispatch('business-unit-switched', businessUnitId: $businessUnitId);
    }

    /**
     * Handle business unit switch event from header switcher
     * ✅ UX IMPROVED: Stay on dashboard, just refresh data with new BU
     * ✅ FIX: Update session FIRST, then property, then reload data
     */
    public function handleBusinessUnitSwitch($businessUnitId): void
    {
        \Log::info('🔄 Dashboard received business-unit-switched event', [
            'new_business_unit_id' => $businessUnitId,
            'old_business_unit_id' => $this->activeBusinessUnitId,
            'old_session' => session('current_business_unit_id'),
        ]);

        // ✅ CRITICAL ORDER: Update session FIRST (single source of truth)
        session([
            'current_business_unit_id' => $businessUnitId,
            'dashboard_active_business_unit_id' => $businessUnitId,
        ]);

        // ✅ Then update property (for UI binding)
        $this->activeBusinessUnitId = $businessUnitId;

        // Clear cache for new BU
        $this->clearDashboardCache();

        // ✅ Now reload data (will read from session, guaranteed fresh)
        $this->loadDashboardData();

        // Reload business units list (in case access changed)
        $this->businessUnits = $this->getAccessibleBusinessUnits();

        \Log::info('✅ Dashboard refreshed with new business unit', [
            'new_activeBusinessUnitId' => $this->activeBusinessUnitId,
            'new_session' => session('current_business_unit_id'),
        ]);
    }

    public function loadDashboardData(): void
    {
        $this->stats = $this->getStats();
        $this->recentActivities = $this->getRecentActivities();
        $this->chartData = $this->getChartData();

        // Dispatch event to update charts on frontend
        $this->dispatch('chartDataUpdated', chartData: $this->chartData);
    }

    /**
     * Get active business unit ID and its descendants
     */
    /**
     * Get business unit IDs to filter by (based on selected business unit)
     * ✅ OPTIMIZED: Single query with eager loading
     * ✅ FIX: Use session as single source of truth (fixes sync issue)
     */
    protected function getFilteredBusinessUnitIds(): array
    {
        // ✅ FIX: Always read from session first (single source of truth)
        $activeBusinessUnitId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;

        \Log::info('📊 getFilteredBusinessUnitIds', [
            'activeBusinessUnitId_property' => $this->activeBusinessUnitId,
            'activeBusinessUnitId_session' => session('current_business_unit_id'),
            'activeBusinessUnitId_used' => $activeBusinessUnitId,
        ]);

        // If no active BU set, return all accessible
        if (! $activeBusinessUnitId) {
            return $this->getAccessibleBusinessUnitIds();
        }

        // ✅ Get the active business unit with children eager-loaded
        $businessUnit = BusinessUnit::with('children')->find($activeBusinessUnitId);

        if (! $businessUnit) {
            return $this->getAccessibleBusinessUnitIds();
        }

        $ids = [$businessUnit->id];

        // If this is a parent business unit, include all descendants (already loaded)
        if ($businessUnit->children && $businessUnit->children->isNotEmpty()) {
            $descendants = $this->getAllDescendantIds($businessUnit);
            $ids = array_merge($ids, $descendants);
        }

        return $ids;
    }

    /**
     * Generate unique cache key for stats data
     * ✅ Cache invalidated when: BU changes, date filter changes, or user changes
     */
    protected function getStatsCacheKey(): string
    {
        $buIds = implode(',', $this->getFilteredBusinessUnitIds());

        return sprintf(
            'dashboard.stats.u%s.bu%s.f%s.d%s-%s',
            Auth::id(),
            md5($buIds),
            $this->dateFilter,
            $this->startDate,
            $this->endDate
        );
    }

    /**
     * Generate unique cache key for activities
     * ✅ Cache invalidated when: BU changes or user changes
     */
    protected function getActivitiesCacheKey(): string
    {
        $buIds = implode(',', $this->getFilteredBusinessUnitIds());

        return sprintf(
            'dashboard.activities.u%s.bu%s',
            Auth::id(),
            md5($buIds)
        );
    }

    /**
     * Generate unique cache key for chart data
     * ✅ Cache invalidated when: BU changes, date filter changes
     */
    protected function getChartCacheKey(): string
    {
        $buIds = implode(',', $this->getFilteredBusinessUnitIds());

        return sprintf(
            'dashboard.chart.bu%s.f%s.d%s-%s',
            md5($buIds),
            $this->dateFilter,
            $this->startDate,
            $this->endDate
        );
    }

    /**
     * Generate unique cache key for business units
     * ✅ Cache invalidated when: user changes (different accessible BUs)
     */
    protected function getBusinessUnitsCacheKey(): string
    {
        return sprintf(
            'dashboard.business_units.u%s',
            Auth::id()
        );
    }

    /**
     * Clear all dashboard caches for current user
     * ✅ Call this when PR is created/updated/deleted or when filters change
     */
    public function clearDashboardCache(): void
    {
        $userId = Auth::id();

        // For database cache driver, we need to clear specific keys
        // We'll clear all possible combinations for current user

        $dateFilters = ['today', 'this_week', 'this_month', 'this_year', 'last_7_days', 'last_30_days', 'custom'];
        $buIds = $this->getFilteredBusinessUnitIds();
        $buHash = md5(implode(',', $buIds));

        // Clear stats cache for all date filters
        foreach ($dateFilters as $filter) {
            // Calculate dates for each filter to match cache key
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

        // Clear chart cache for all date filters
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

        \Log::info("✅ Dashboard cache cleared for user {$userId}");
    }

    /**
     * Get start and end dates for a given filter
     * Helper method for cache key generation
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
                'start' => $this->startDate ?? now()->startOfMonth()->format('Y-m-d'),
                'end' => $this->endDate ?? now()->endOfMonth()->format('Y-m-d'),
            ],
        };
    }

    /**
     * Get dashboard statistics with caching
     * ✅ OPTIMIZED: Cached for 5 minutes, single query with CASE statements
     */
    protected function getStats(): array
    {
        $cacheKey = $this->getStatsCacheKey();

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_STATS), function () {
            return $this->fetchStatsFromDatabase();
        });
    }

    /**
     * Fetch stats from database (actual query logic)
     * ✅ OPTIMIZED: Consolidated from 8 queries to 2 queries
     */
    protected function fetchStatsFromDatabase(): array
    {
        $userId = Auth::id();
        $businessUnitIds = $this->getFilteredBusinessUnitIds();
        $buIdsPlaceholder = implode(',', array_fill(0, count($businessUnitIds), '?'));

        // ✅ OPTIMIZED: Single query with CASE statements for all PR stats
        // This reduces 7 separate queries to just 1 query!
        $prStats = DB::selectOne("
            SELECT 
                COUNT(CASE WHEN status IN ('submitted', 'in_approval') THEN 1 END) as active_prs,
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_prs,
                COUNT(CASE 
                    WHEN created_at >= ? 
                    AND created_at <= ? 
                    THEN 1 
                END) as period_prs,
                COUNT(CASE 
                    WHEN status = 'approved' 
                    AND created_at >= ? 
                    AND created_at <= ? 
                    THEN 1 
                END) as approved_prs,
                COUNT(CASE 
                    WHEN status = 'rejected' 
                    AND created_at >= ? 
                    AND created_at <= ? 
                    THEN 1 
                END) as rejected_prs,
                COALESCE(SUM(CASE 
                    WHEN status IN ('approved', 'in_approval', 'submitted')
                    AND created_at >= ? 
                    AND created_at <= ? 
                    THEN total_amount 
                    ELSE 0 
                END), 0) as total_amount
            FROM purchase_requests
            WHERE business_unit_id IN ({$buIdsPlaceholder})
        ", array_merge(
            [$this->startDate, $this->endDate], // period_prs
            [$this->startDate, $this->endDate], // approved_prs
            [$this->startDate, $this->endDate], // rejected_prs
            [$this->startDate, $this->endDate], // total_amount
            $businessUnitIds // WHERE IN clause
        ));

        // ✅ OPTIMIZED: Single query for approval stats
        // This combines 2 approval queries into 1
        $approvalStats = DB::selectOne("
            SELECT 
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_approvals,
                COUNT(CASE 
                    WHEN status = 'pending' 
                    AND due_date IS NOT NULL 
                    AND due_date < NOW() 
                    THEN 1 
                END) as overdue_approvals
            FROM pr_approvals
            WHERE approver_id = ?
        ", [$userId]);

        return [
            'active_prs' => (int) $prStats->active_prs,
            'pending_approvals' => (int) $approvalStats->pending_approvals,
            'period_prs' => (int) $prStats->period_prs,
            'total_amount' => (float) $prStats->total_amount,
            'draft_prs' => (int) $prStats->draft_prs,
            'approved_prs' => (int) $prStats->approved_prs,
            'rejected_prs' => (int) $prStats->rejected_prs,
            'overdue_approvals' => (int) $approvalStats->overdue_approvals,
        ];
    }

    /**
     * Get recent activities with caching
     * ✅ OPTIMIZED: Cached for 1 minute (more dynamic data)
     */
    protected function getRecentActivities(): array
    {
        $cacheKey = $this->getActivitiesCacheKey();

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_ACTIVITIES), function () {
            return $this->fetchActivitiesFromDatabase();
        });
    }

    /**
     * Fetch activities from database (actual query logic)
     * ✅ OPTIMIZED: Eager loading to prevent N+1
     */
    protected function fetchActivitiesFromDatabase(): array
    {
        $businessUnitIds = $this->getFilteredBusinessUnitIds();

        // ✅ OPTIMIZED: Get activities and eager load based on type
        $activities = Activity::where(function ($query) {
            $query->where('subject_type', PurchaseRequest::class)
                ->orWhere('subject_type', PrApproval::class);
        })
            ->with(['causer:id,name'])
            ->latest()
            ->limit(100)
            ->get();

        // ✅ Eager load 'subject' for all activities
        $activities->load('subject');

        // ✅ Eager load 'purchaseRequest' only for PrApproval activities
        $approvalActivities = $activities->filter(fn ($act) => $act->subject_type === PrApproval::class);
        if ($approvalActivities->isNotEmpty()) {
            $approvalActivities->load('subject.purchaseRequest');
        }

        return $activities
            ->filter(function ($activity) use ($businessUnitIds) {
                if (! $activity->subject) {
                    return false;
                }

                // Only show important activities
                $importantActions = ['created', 'submitted', 'approved', 'rejected'];
                if (! in_array($activity->description, $importantActions)) {
                    return false;
                }

                // Filter by business unit
                if (str_contains($activity->subject_type, 'PurchaseRequest')) {
                    return in_array($activity->subject->business_unit_id, $businessUnitIds);
                } elseif (str_contains($activity->subject_type, 'PrApproval')) {
                    $pr = $activity->subject->purchaseRequest;

                    return $pr && in_array($pr->business_unit_id, $businessUnitIds);
                }

                return false;
            })
            ->unique(function ($activity) {
                // Remove duplicates: Same PR + Same action
                $prNumber = '';
                if (str_contains($activity->subject_type, 'PurchaseRequest')) {
                    $prNumber = $activity->subject->pr_number;
                } elseif (str_contains($activity->subject_type, 'PrApproval')) {
                    $prNumber = $activity->subject->purchaseRequest?->pr_number;
                }

                return $prNumber.'_'.$activity->description;
            })
            ->take(5)
            ->map(function ($activity) {
                $data = [
                    'id' => $activity->id,
                    'created_at' => $activity->created_at,
                    'causer_name' => $activity->causer?->name ?? 'System',
                    'description' => $activity->description,
                ];

                if (str_contains($activity->subject_type, 'PurchaseRequest')) {
                    $pr = $activity->subject;
                    if ($pr) {
                        $data['pr_number'] = $pr->pr_number;
                        $data['status'] = $pr->status;
                        $data['message'] = $this->formatPRActivity($activity->description, $pr);
                        $data['icon'] = $this->getPRActivityIcon($activity->description, $pr->status);
                        $data['color'] = $this->getPRActivityColor($pr->status);
                    }
                } elseif (str_contains($activity->subject_type, 'PrApproval')) {
                    $approval = $activity->subject;
                    if ($approval && $approval->purchaseRequest) {
                        $data['pr_number'] = $approval->purchaseRequest->pr_number;
                        $data['status'] = $approval->status;
                        $data['message'] = $this->formatApprovalActivity($activity->description, $approval);
                        $data['icon'] = $this->getApprovalActivityIcon($approval->status);
                        $data['color'] = $this->getApprovalActivityColor($approval->status);
                    }
                }

                return $data;
            })
            ->filter(fn ($item) => isset($item['message']))
            ->values()
            ->toArray();
    }

    protected function formatPRActivity(string $description, $pr): string
    {
        return match ($description) {
            'created' => "New PR <strong>{$pr->pr_number}</strong> created",
            'updated' => "PR <strong>{$pr->pr_number}</strong> updated",
            'submitted' => "PR <strong>{$pr->pr_number}</strong> submitted for approval",
            default => "PR <strong>{$pr->pr_number}</strong> {$description}",
        };
    }

    protected function formatApprovalActivity(string $description, $approval): string
    {
        $pr = $approval->purchaseRequest;

        // Show actual approval status, not description
        return match ($approval->status) {
            'approved' => "PR <strong>{$pr->pr_number}</strong> was <strong class='text-green-600'>approved</strong>",
            'rejected' => "PR <strong>{$pr->pr_number}</strong> was <strong class='text-red-600'>rejected</strong>",
            'pending' => "PR <strong>{$pr->pr_number}</strong> - <strong class='text-yellow-600'>Pending Approval</strong>",
            default => "PR <strong>{$pr->pr_number}</strong> approval {$approval->status}",
        };
    }

    protected function getPRActivityIcon(string $description, string $status): string
    {
        return match ($status) {
            'approved' => 'check',
            'rejected' => 'x',
            'submitted', 'in_approval' => 'clock',
            'draft' => 'edit',
            default => 'plus',
        };
    }

    protected function getPRActivityColor(string $status): string
    {
        return match ($status) {
            'approved' => 'green',
            'rejected' => 'red',
            'submitted', 'in_approval' => 'yellow',
            'draft' => 'gray',
            default => 'blue',
        };
    }

    protected function getApprovalActivityIcon(string $status): string
    {
        return match ($status) {
            'approved' => 'check',
            'rejected' => 'x',
            'pending' => 'clock',
            default => 'circle',
        };
    }

    protected function getApprovalActivityColor(string $status): string
    {
        return match ($status) {
            'approved' => 'green',
            'rejected' => 'red',
            'pending' => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Get chart data for visualizations with caching
     * ✅ OPTIMIZED: Cached for 5 minutes, uses indexes and minimal queries
     */
    protected function getChartData(): array
    {
        $cacheKey = $this->getChartCacheKey();

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_CHART), function () {
            return $this->fetchChartDataFromDatabase();
        });
    }

    /**
     * Fetch chart data from database (actual query logic)
     * ✅ OPTIMIZED: Single query with GROUP BY for performance
     */
    protected function fetchChartDataFromDatabase(): array
    {
        $businessUnitIds = $this->getFilteredBusinessUnitIds();
        $buIdsPlaceholder = implode(',', array_fill(0, count($businessUnitIds), '?'));

        // ✅ OPTIMIZED: Single query for daily stats using raw SQL for better performance
        $dailyStats = DB::select("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                COALESCE(SUM(total_amount), 0) as amount
            FROM purchase_requests
            WHERE business_unit_id IN ({$buIdsPlaceholder})
              AND created_at >= ?
              AND created_at <= ?
            GROUP BY DATE(created_at)
            ORDER BY date
        ", array_merge($businessUnitIds, [$this->startDate, $this->endDate]));

        // ✅ OPTIMIZED: Single query for status distribution
        $statusStats = DB::select("
            SELECT 
                status,
                COUNT(*) as count
            FROM purchase_requests
            WHERE business_unit_id IN ({$buIdsPlaceholder})
              AND created_at >= ?
              AND created_at <= ?
            GROUP BY status
        ", array_merge($businessUnitIds, [$this->startDate, $this->endDate]));

        // Convert status stats to array format
        $statusArray = [];
        foreach ($statusStats as $stat) {
            $statusArray[$stat->status] = (int) $stat->count;
        }

        return [
            'daily' => array_map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => (int) $item->count,
                    'amount' => (float) $item->amount,
                ];
            }, $dailyStats),
            'status' => $statusArray,
        ];
    }

    /**
     * Get business unit IDs accessible by current user
     * Includes hierarchical access: if user is in parent BU, they can see all children
     * ✅ OPTIMIZED: Single query to load all business units with relationships
     */
    protected function getAccessibleBusinessUnitIds(): array
    {
        $user = Auth::user();

        // ✅ Eager load business units with their children relationships
        $directBusinessUnits = $user->businessUnits()->with('businessUnit.children')->get();
        $accessibleIds = [];

        foreach ($directBusinessUnits as $userBU) {
            $businessUnit = $userBU->businessUnit;

            if (! $businessUnit) {
                continue;
            }

            // Add the business unit itself
            $accessibleIds[] = $businessUnit->id;

            // If this has children (already loaded), add all descendants
            if ($businessUnit->children && $businessUnit->children->isNotEmpty()) {
                $descendants = $this->getAllDescendantIds($businessUnit);
                $accessibleIds = array_merge($accessibleIds, $descendants);
            }
        }

        return array_unique($accessibleIds);
    }

    /**
     * Recursively get all descendant business unit IDs
     * ✅ OPTIMIZED: Works with eager-loaded children to avoid N+1
     */
    protected function getAllDescendantIds(BusinessUnit $businessUnit): array
    {
        $ids = [];

        // Children are already eager-loaded, no additional query
        foreach ($businessUnit->children as $child) {
            $ids[] = $child->id;

            // Recursively get children's children (if loaded)
            if ($child->children && $child->children->isNotEmpty()) {
                $ids = array_merge($ids, $this->getAllDescendantIds($child));
            }
        }

        return $ids;
    }

    /**
     * Get accessible business unit IDs and their details with caching
     * ✅ OPTIMIZED: Cached for 1 hour (rarely changes), load all BUs in single query with eager loading
     */
    protected function getAccessibleBusinessUnits(): array
    {
        $cacheKey = $this->getBusinessUnitsCacheKey();

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_BUSINESS_UNITS), function () {
            return $this->fetchBusinessUnitsFromDatabase();
        });
    }

    /**
     * Fetch business units from database (actual query logic)
     * ✅ OPTIMIZED: Single query with eager loading
     */
    protected function fetchBusinessUnitsFromDatabase(): array
    {
        $accessibleIds = $this->getAccessibleBusinessUnitIds();

        return BusinessUnit::with('children')  // ✅ Eager load children
            ->whereIn('id', $accessibleIds)
            ->select('id', 'code', 'name', 'parent_id')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard.user-dashboard');
    }
}
