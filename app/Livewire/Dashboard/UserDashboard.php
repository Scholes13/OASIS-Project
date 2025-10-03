<?php

namespace App\Livewire\Dashboard;

use App\Models\BusinessUnit;
use App\Models\Modules\Wns\PrApproval;
use App\Models\Modules\Wns\PurchaseRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

class UserDashboard extends Component
{
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

        // Set default active BU to parent (WG - Werkudara Group)
        // Find parent BU (one with parent_id = null)
        $parentBU = collect($this->businessUnits)->firstWhere('parent_id', null);

        // If no parent, use first available BU
        $this->activeBusinessUnitId = $parentBU['id'] ?? $this->businessUnits[0]['id'];

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
        $this->loadDashboardData();
    }

    public function applyCustomDateRange(): void
    {
        $this->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        $this->loadDashboardData();
    }

    /**
     * Switch active business unit (single selection)
     */
    public function switchBusinessUnit(int $businessUnitId): void
    {
        // Simply switch to the clicked business unit
        $this->activeBusinessUnitId = $businessUnitId;

        $this->loadDashboardData();
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
    protected function getFilteredBusinessUnitIds(): array
    {
        // If no active BU set, return all accessible
        if (! $this->activeBusinessUnitId) {
            return $this->getAccessibleBusinessUnitIds();
        }

        // Get the active business unit
        $businessUnit = BusinessUnit::find($this->activeBusinessUnitId);

        if (! $businessUnit) {
            return $this->getAccessibleBusinessUnitIds();
        }

        $ids = [$businessUnit->id];

        // If this is a parent business unit, include all descendants
        if ($businessUnit->children()->exists()) {
            $descendants = $this->getAllDescendantIds($businessUnit);
            $ids = array_merge($ids, $descendants);
        }

        return $ids;
    }

    protected function getStats(): array
    {
        $userId = Auth::id();
        // Get filtered business unit IDs based on user selection
        $businessUnitIds = $this->getFilteredBusinessUnitIds();

        return [
            // Active PRs (submitted or in approval) - by business unit
            'active_prs' => PurchaseRequest::whereIn('business_unit_id', $businessUnitIds)
                ->whereIn('status', ['submitted', 'in_approval'])
                ->count(),

            // Pending approvals assigned to this user
            'pending_approvals' => PrApproval::where('approver_id', $userId)
                ->where('status', 'pending')
                ->count(),

            // PRs in selected date range - by business unit
            'period_prs' => PurchaseRequest::whereIn('business_unit_id', $businessUnitIds)
                ->whereBetween('created_at', [$this->startDate, $this->endDate])
                ->count(),

            // Total amount in selected date range - by business unit
            'total_amount' => PurchaseRequest::whereIn('business_unit_id', $businessUnitIds)
                ->whereIn('status', ['approved', 'in_approval', 'submitted'])
                ->whereBetween('created_at', [$this->startDate, $this->endDate])
                ->sum('total_amount'),

            // Additional stats - by business unit
            'draft_prs' => PurchaseRequest::whereIn('business_unit_id', $businessUnitIds)
                ->where('status', 'draft')
                ->count(),

            'approved_prs' => PurchaseRequest::whereIn('business_unit_id', $businessUnitIds)
                ->where('status', 'approved')
                ->whereBetween('created_at', [$this->startDate, $this->endDate])
                ->count(),

            'rejected_prs' => PurchaseRequest::whereIn('business_unit_id', $businessUnitIds)
                ->where('status', 'rejected')
                ->whereBetween('created_at', [$this->startDate, $this->endDate])
                ->count(),

            'overdue_approvals' => PrApproval::where('approver_id', $userId)
                ->where('status', 'pending')
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->count(),
        ];
    }

    protected function getRecentActivities(): array
    {
        // Get filtered business unit IDs based on user selection
        $businessUnitIds = $this->getFilteredBusinessUnitIds();

        // Simplified approach: Get all activities first, then filter in PHP
        // This avoids complex whereHasMorph issues with mixed class names
        $activities = Activity::where(function ($query) {
            $query->where('subject_type', PurchaseRequest::class)
                ->orWhere('subject_type', 'App\\Models\\Modules\\WNS\\PurchaseRequest') // Legacy uppercase
                ->orWhere('subject_type', PrApproval::class)
                ->orWhere('subject_type', 'App\\Models\\Modules\\WNS\\PrApproval'); // Legacy uppercase
        })
            ->with(['subject', 'causer']) // Load subject and causer
            ->latest()
            ->limit(100) // Get more for better filtering
            ->get()
            ->filter(function ($activity) use ($businessUnitIds) {
                // Filter by business unit in PHP
                if (! $activity->subject) {
                    return false;
                }

                // Only show important activities (skip 'updated' to avoid spam)
                $importantActions = ['created', 'submitted', 'approved', 'rejected'];
                if (! in_array($activity->description, $importantActions)) {
                    return false;
                }

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
            ->take(5) // Only 5 activities
            ->map(function ($activity) {
                $data = [
                    'id' => $activity->id,
                    'created_at' => $activity->created_at,
                    'causer_name' => $activity->causer?->name ?? 'System',
                    'description' => $activity->description,
                ];

                // Handle both 'Wns' and 'WNS' class names
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

        return $activities;
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
     * Get chart data for visualizations
     */
    protected function getChartData(): array
    {
        // Get filtered business unit IDs based on user selection
        $businessUnitIds = $this->getFilteredBusinessUnitIds();

        // Get daily PR count for the selected period - by business unit
        $dailyStats = PurchaseRequest::whereIn('business_unit_id', $businessUnitIds)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as amount')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get status distribution - by business unit
        $statusStats = PurchaseRequest::whereIn('business_unit_id', $businessUnitIds)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return [
            'daily' => $dailyStats->map(fn ($item) => [
                'date' => $item->date,
                'count' => $item->count,
                'amount' => (float) $item->amount,
            ])->toArray(),
            'status' => $statusStats,
        ];
    }

    /**
     * Get business unit IDs accessible by current user
     * Includes hierarchical access: if user is in parent BU, they can see all children
     */
    protected function getAccessibleBusinessUnitIds(): array
    {
        $user = Auth::user();

        // Get all business units the user has direct access to
        $directBusinessUnits = $user->businessUnits;
        $accessibleIds = [];

        foreach ($directBusinessUnits as $userBU) {
            $businessUnit = BusinessUnit::find($userBU->business_unit_id);

            if (! $businessUnit) {
                continue;
            }

            // Add the business unit itself
            $accessibleIds[] = $businessUnit->id;

            // If this is a parent business unit (has children), add all descendants
            if ($businessUnit->children()->exists()) {
                $descendants = $this->getAllDescendantIds($businessUnit);
                $accessibleIds = array_merge($accessibleIds, $descendants);
            }
        }

        return array_unique($accessibleIds);
    }

    /**
     * Recursively get all descendant business unit IDs
     */
    protected function getAllDescendantIds(BusinessUnit $businessUnit): array
    {
        $ids = [];

        foreach ($businessUnit->children as $child) {
            $ids[] = $child->id;

            // Recursively get children's children
            if ($child->children()->exists()) {
                $ids = array_merge($ids, $this->getAllDescendantIds($child));
            }
        }

        return $ids;
    }

    /**
     * Get accessible business unit IDs and their details
     */
    protected function getAccessibleBusinessUnits(): array
    {
        $accessibleIds = $this->getAccessibleBusinessUnitIds();

        return BusinessUnit::whereIn('id', $accessibleIds)
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
