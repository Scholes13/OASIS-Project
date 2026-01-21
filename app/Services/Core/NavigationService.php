<?php

namespace App\Services\Core;

use App\Models\Core\User;
use Illuminate\Support\Facades\Gate;

class NavigationService
{
    /**
     * Build navigation menu for the given user and business unit.
     */
    public function buildMenuForUser(User $user, ?int $businessUnitId): array
    {
        if (!$businessUnitId) {
            return ['sections' => []];
        }

        $sections = [];

        // Dashboard section
        $sections[] = $this->getDashboardSection($user);

        // Purchasing section
        if ($this->canAccessPurchasing($user, $businessUnitId)) {
            $sections[] = $this->getPurchasingSection($user, $businessUnitId);
        }

        // Activity Tracking section
        if ($this->canAccessActivityTracking($user, $businessUnitId)) {
            $sections[] = $this->getActivityTrackingSection($user, $businessUnitId);
        }

        // Sales CRM section
        if ($this->canAccessSalesCrm($user, $businessUnitId)) {
            $sections[] = $this->getSalesCrmSection($user, $businessUnitId);
        }

        // Administration section
        if ($this->canAccessAdministration($user)) {
            $sections[] = $this->getAdministrationSection($user);
        }

        return ['sections' => array_filter($sections)];
    }

    /**
     * Get dashboard section.
     */
    protected function getDashboardSection(User $user): array
    {
        return [
            'name' => 'Dashboard',
            'items' => [
                [
                    'name' => 'Dashboard',
                    'href' => route('dashboard'),
                    'icon' => 'home',
                    'active' => request()->routeIs('dashboard'),
                ],
            ],
        ];
    }

    /**
     * Get purchasing section.
     */
    protected function getPurchasingSection(User $user, int $businessUnitId): array
    {
        $items = [];

        // Purchasing parent menu with children (dropdown)
        $purchasingChildren = [];

        // Purchase Requests submenu
        $purchasingChildren[] = [
            'name' => 'Purchase Requests',
            'href' => route('purchase-requests.index'),
            'icon' => 'file-text',
            'active' => request()->routeIs('purchase-requests.*') && !request()->routeIs('purchase-requests.all'),
        ];

        // Stock Requests submenu
        $purchasingChildren[] = [
            'name' => 'Stock Requests',
            'href' => route('stock-requests.index'),
            'icon' => 'package',
            'active' => request()->routeIs('stock-requests.*'),
        ];

        // All Requests submenu
        $purchasingChildren[] = [
            'name' => 'All Requests',
            'href' => route('purchase-requests.all'),
            'icon' => 'list',
            'active' => request()->routeIs('purchase-requests.all'),
        ];

        // Approvals submenu
        $purchasingChildren[] = [
            'name' => 'Approvals',
            'href' => route('approvals.index'),
            'icon' => 'check-circle',
            'active' => request()->routeIs('approvals.*'),
        ];

        // Main Purchasing menu item with children
        $items[] = [
            'name' => 'Purchasing',
            'href' => route('purchase-requests.index'), // Default link when clicked
            'icon' => 'shopping-cart',
            'active' => request()->routeIs('purchase-requests.*') || request()->routeIs('stock-requests.*') || request()->routeIs('approvals.*'),
            'children' => $purchasingChildren,
        ];

        // Purchasing Admin (if user has permission) - separate menu item
        if ($this->canAccessPurchasingAdmin($user, $businessUnitId)) {
            $items[] = [
                'name' => 'Purchasing Admin',
                'href' => route('purchasing.admin.dashboard'),
                'icon' => 'clipboard-list',
                'active' => request()->routeIs('purchasing.admin.*'),
                'children' => [
                    [
                        'name' => 'Dashboard',
                        'href' => route('purchasing.admin.dashboard'),
                        'icon' => 'home',
                        'active' => request()->routeIs('purchasing.admin.dashboard'),
                    ],
                    [
                        'name' => 'Tasks',
                        'href' => route('purchasing.admin.tasks'),
                        'icon' => 'check-circle',
                        'active' => request()->routeIs('purchasing.admin.tasks*') && !request()->routeIs('purchasing.admin.task-history*'),
                    ],
                    [
                        'name' => 'My Task History',
                        'href' => route('purchasing.admin.task-history'),
                        'icon' => 'clock',
                        'active' => request()->routeIs('purchasing.admin.task-history*'),
                    ],
                ],
            ];
        }

        return [
            'name' => 'Purchasing',
            'items' => $items,
        ];
    }

    /**
     * Get activity tracking section.
     */
    protected function getActivityTrackingSection(User $user, int $businessUnitId): array
    {
        return [
            'name' => 'Activity Tracking',
            'items' => [
                [
                    'name' => 'Activity',
                    'href' => route('activity.dashboard'),
                    'icon' => 'clipboard-list',
                    'active' => request()->routeIs('activity.*'),
                    'children' => [
                        [
                            'name' => 'Dashboard',
                            'href' => route('activity.dashboard'),
                            'icon' => 'chart-pie',
                            'active' => request()->routeIs('activity.dashboard'),
                        ],
                        [
                            'name' => 'My Tasks',
                            'href' => route('activity.task.index'),
                            'icon' => 'calendar',
                            'active' => request()->routeIs('activity.task.*'),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get sales CRM section.
     */
    protected function getSalesCrmSection(User $user, int $businessUnitId): array
    {
        return [
            'name' => 'Sales CRM',
            'items' => [
                [
                    'name' => 'Contacts',
                    'href' => route('sales-crm.contacts.index'),
                    'icon' => 'users',
                    'active' => request()->routeIs('sales-crm.contacts.*'),
                ],
                [
                    'name' => 'Activities',
                    'href' => route('sales-crm.activities.index'),
                    'icon' => 'calendar',
                    'active' => request()->routeIs('sales-crm.activities.*'),
                ],
            ],
        ];
    }

    /**
     * Get administration section.
     */
    protected function getAdministrationSection(User $user): array
    {
        $items = [];

        // Only Super Admin can access administration
        if (!$user->isSuperAdmin()) {
            return [];
        }

        $items[] = [
            'name' => 'Users',
            'href' => route('admin.users.index'),
            'icon' => 'user',
            'active' => request()->routeIs('admin.users.*'),
        ];

        $items[] = [
            'name' => 'Departments',
            'href' => route('admin.departments.index'),
            'icon' => 'office-building',
            'active' => request()->routeIs('admin.departments.*'),
        ];

        $items[] = [
            'name' => 'Business Units',
            'href' => route('admin.business-units.index'),
            'icon' => 'briefcase',
            'active' => request()->routeIs('admin.business-units.*'),
        ];

        return [
            'name' => 'Administration',
            'items' => $items,
        ];
    }

    /**
     * Check if user can access purchasing module.
     */
    protected function canAccessPurchasing(User $user, int $businessUnitId): bool
    {
        // Super Admin can access everything
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check if user has access to this business unit
        return $user->businessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->exists();
    }

    /**
     * Check if user can access purchasing admin features.
     */
    protected function canAccessPurchasingAdmin(User $user, int $businessUnitId): bool
    {
        // Super Admin can access everything
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Top Management in Parent BU
        $isTopManagementParent = $user->businessUnits()
            ->whereHas('businessUnit', fn($q) => $q->whereNull('parent_id'))
            ->whereHas('position', fn($q) => $q->whereIn('access_level', [1, 2]))
            ->exists();

        if ($isTopManagementParent) {
            return true;
        }

        // Purchasing Admin in current BU
        return $user->businessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->where('is_purchasing_admin', true)
            ->whereHas('department', fn($q) => $q->where('is_purchasing_department', true))
            ->exists();
    }

    /**
     * Check if user can access activity tracking module.
     */
    protected function canAccessActivityTracking(User $user, int $businessUnitId): bool
    {
        // Super Admin can access everything
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check if user has access to this business unit
        return $user->businessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->exists();
    }

    /**
     * Check if user can access sales CRM module.
     * NOTE: Currently restricted to Super Admin only (Beta feature)
     */
    protected function canAccessSalesCrm(User $user, int $businessUnitId): bool
    {
        // Beta: Only Super Admin can access Sales CRM for now
        return $user->isSuperAdmin();
    }

    /**
     * Check if user can access administration.
     */
    protected function canAccessAdministration(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
