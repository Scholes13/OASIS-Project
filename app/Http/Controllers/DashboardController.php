<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard.
     * 
     * This is the landing page after login, providing quick access to:
     * - Recent activities
     * - Quick actions (Create PR, Create ST, View Tasks)
     * - Statistics overview
     * - Notifications
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $currentBusinessUnitId = session('current_business_unit_id');

        // Get quick stats
        $stats = $this->getQuickStats($user, $currentBusinessUnitId);

        // Get recent activities
        $recentActivities = $this->getRecentActivities($user, $currentBusinessUnitId);

        // Get pending approvals count
        $pendingApprovalsCount = $this->getPendingApprovalsCount($user, $currentBusinessUnitId);

        // Get quick actions based on user permissions
        $quickActions = $this->getQuickActions($user);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'pendingApprovalsCount' => $pendingApprovalsCount,
            'quickActions' => $quickActions,
        ]);
    }

    /**
     * Get quick statistics for the dashboard.
     */
    protected function getQuickStats($user, $currentBusinessUnitId): array
    {
        $stats = [
            'my_purchase_requests' => 0,
            'my_stock_requests' => 0,
            'pending_approvals' => 0,
            'my_tasks' => 0,
        ];

        // My Purchase Requests
        $stats['my_purchase_requests'] = \App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest::query()
            ->where('user_id', $user->id)
            ->where('business_unit_id', $currentBusinessUnitId)
            ->whereIn('status', ['draft', 'submitted', 'in_approval'])
            ->count();

        // My Stock Requests
        $stats['my_stock_requests'] = \App\Models\Modules\Purchasing\StockRequest\StockRequest::query()
            ->where('user_id', $user->id)
            ->where('business_unit_id', $currentBusinessUnitId)
            ->whereIn('status', ['draft', 'submitted', 'in_approval'])
            ->count();

        // Pending Approvals (PRs)
        $stats['pending_approvals'] = \App\Models\Modules\Purchasing\PurchaseRequest\PrApproval::query()
            ->where('approver_id', $user->id)
            ->where('status', 'pending')
            ->count();

        // Add Stock Request approvals
        $stats['pending_approvals'] += \App\Models\Modules\Purchasing\StockRequest\StockApproval::query()
            ->where('approver_id', $user->id)
            ->where('status', 'pending')
            ->count();

        // My Active Tasks (Activity Tracking)
        if (class_exists('\App\Models\Modules\Activity\Task')) {
            $stats['my_tasks'] = \App\Models\Modules\Activity\Task::query()
                ->whereHas('participants', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->whereIn('status', ['planned', 'in_progress'])
                ->count();
        }

        return $stats;
    }

    /**
     * Get recent activities for the dashboard.
     */
    protected function getRecentActivities($user, $currentBusinessUnitId): array
    {
        $activities = [];

        // Recent PRs
        $recentPRs = \App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest::query()
            ->where('user_id', $user->id)
            ->where('business_unit_id', $currentBusinessUnitId)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($pr) {
                return [
                    'type' => 'purchase_request',
                    'title' => $pr->pr_number,
                    'status' => $pr->status,
                    'date' => $pr->created_at->format('Y-m-d H:i'),
                    'url' => route('purchase-requests.show', $pr->id),
                ];
            });

        // Recent STs
        $recentSTs = \App\Models\Modules\Purchasing\StockRequest\StockRequest::query()
            ->where('user_id', $user->id)
            ->where('business_unit_id', $currentBusinessUnitId)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($st) {
                return [
                    'type' => 'stock_request',
                    'title' => $st->st_number,
                    'status' => $st->status,
                    'date' => $st->created_at->format('Y-m-d H:i'),
                    'url' => route('stock-requests.show', $st->id),
                ];
            });

        // Merge and sort by date
        $activities = $recentPRs->concat($recentSTs)
            ->sortByDesc('date')
            ->take(10)
            ->values()
            ->toArray();

        return $activities;
    }

    /**
     * Get pending approvals count.
     */
    protected function getPendingApprovalsCount($user, $currentBusinessUnitId): int
    {
        $count = 0;

        // PR Approvals
        $count += \App\Models\Modules\Purchasing\PurchaseRequest\PrApproval::query()
            ->where('approver_id', $user->id)
            ->where('status', 'pending')
            ->count();

        // ST Approvals
        $count += \App\Models\Modules\Purchasing\StockRequest\StockApproval::query()
            ->where('approver_id', $user->id)
            ->where('status', 'pending')
            ->count();

        return $count;
    }

    /**
     * Get quick actions based on user permissions.
     */
    protected function getQuickActions($user): array
    {
        $actions = [];

        // Create Purchase Request
        $actions[] = [
            'title' => 'Create Purchase Request',
            'description' => 'Submit a new purchase request',
            'icon' => 'shopping-cart',
            'url' => route('purchase-requests.create'),
            'color' => 'indigo',
        ];

        // Create Stock Request
        $actions[] = [
            'title' => 'Create Stock Request',
            'description' => 'Request stock items',
            'icon' => 'package',
            'url' => route('stock-requests.create'),
            'color' => 'blue',
        ];

        // View Approvals
        if ($this->getPendingApprovalsCount($user, session('current_business_unit_id')) > 0) {
            $actions[] = [
                'title' => 'Pending Approvals',
                'description' => 'Review and approve requests',
                'icon' => 'clipboard-check',
                'url' => route('approvals.index'),
                'color' => 'amber',
                'badge' => $this->getPendingApprovalsCount($user, session('current_business_unit_id')),
            ];
        }

        // Activity Tracking
        if (class_exists('\App\Models\Modules\Activity\Task')) {
            $actions[] = [
                'title' => 'My Tasks',
                'description' => 'View and manage your tasks',
                'icon' => 'calendar',
                'url' => route('activity.task.index'),
                'color' => 'emerald',
            ];
        }

        // Purchasing Admin (if user has access)
        if ($user->can('access-purchasing-admin')) {
            $actions[] = [
                'title' => 'Purchasing Admin',
                'description' => 'Manage procurement tasks',
                'icon' => 'clipboard-list',
                'url' => route('purchasing.admin.dashboard'),
                'color' => 'purple',
            ];
        }

        return $actions;
    }
}
