<?php

namespace App\Services\Core;

use App\Models\Core\User;
use App\Services\Modules\CashflowProjection\CashflowProjectionAccessService;

class NavigationService
{
    public function __construct(
        protected CashflowProjectionAccessService $cashflowProjectionAccessService,
    ) {}

    /**
     * Build navigation menu for the given user and business unit.
     */
    public function buildMenuForUser(User $user, ?int $businessUnitId): array
    {
        if (! $businessUnitId) {
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

        // IT Support section (visible to all authenticated users)
        $sections[] = $this->getItSupportSection($user, $businessUnitId);

        // Cashflow Projection section
        if ($this->canAccessCashflowProjection($user, $businessUnitId)) {
            $sections[] = $this->getCashflowProjectionSection($user, $businessUnitId);
        }

        // Administration section
        if ($this->canAccessAdministration($user)) {
            $sections[] = $this->getAdministrationSection($user);
        }

        // Docs & Help section (available to all users)
        $sections[] = $this->getDocsHelpSection();

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
            'active' => request()->routeIs('purchase-requests.*') && ! request()->routeIs('purchase-requests.all'),
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
                        'active' => request()->routeIs('purchasing.admin.tasks*') && ! request()->routeIs('purchasing.admin.task-history*'),
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

        // PR Categories (Super Admin only)
        if ($user->isSuperAdmin()) {
            $items[] = [
                'name' => 'PR Categories',
                'href' => route('admin.pr-categories.index'),
                'icon' => 'tag',
                'active' => request()->routeIs('admin.pr-categories.*'),
            ];

            $items[] = [
                'name' => 'SLA Settings',
                'href' => route('admin.sla-settings.index'),
                'icon' => 'clock',
                'active' => request()->routeIs('admin.sla-settings.*'),
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
        $items = [];

        // Activity menu with children
        $items[] = [
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
        ];

        if ($this->canAccessActivityAdmin($user, $businessUnitId)) {
            $items[0]['children'][] = [
                'name' => 'Activity Admin',
                'href' => route('activity.admin.dashboard'),
                'icon' => 'shield-check',
                'active' => request()->routeIs('activity.admin.*'),
            ];
        }

        // Activity Configuration (Super Admin only) — unified page for types + sub-activities
        if ($user->isSuperAdmin()) {
            $items[] = [
                'name' => 'Activity Configuration',
                'href' => route('admin.activity-configuration.index'),
                'icon' => 'settings',
                'active' => request()->routeIs('admin.activity-configuration.*')
                         || request()->routeIs('admin.activity-types.*')
                         || request()->routeIs('admin.sub-activities.*'),
            ];
        }

        return [
            'name' => 'Activity Tracking',
            'items' => $items,
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

        if (! $user->isSuperAdmin()) {
            return [];
        }

        // User & Access
        $items[] = [
            'name' => 'Users',
            'href' => route('admin.users.index'),
            'icon' => 'user',
            'active' => request()->routeIs('admin.users.*'),
        ];

        $items[] = [
            'name' => 'Activity Admin Assignment',
            'href' => route('admin.activity-admins.index'),
            'icon' => 'shield-check',
            'active' => request()->routeIs('admin.activity-admins.*'),
        ];

        $items[] = [
            'name' => 'Purchasing Admin Assignment',
            'href' => route('admin.purchasing-admins.index'),
            'icon' => 'shopping-cart',
            'active' => request()->routeIs('admin.purchasing-admins.*'),
        ];

        $items[] = [
            'name' => 'IT Support Admin Assignment',
            'href' => route('admin.it-support-admins.index'),
            'icon' => 'headphones',
            'active' => request()->routeIs('admin.it-support-admins.*'),
        ];

        // Organization
        $items[] = [
            'name' => 'Business Units',
            'href' => route('admin.business-units.index'),
            'icon' => 'briefcase',
            'active' => request()->routeIs('admin.business-units.*'),
        ];

        $items[] = [
            'name' => 'Departments',
            'href' => route('admin.departments.index'),
            'icon' => 'office-building',
            'active' => request()->routeIs('admin.departments.*'),
        ];

        // Settings
        $items[] = [
            'name' => 'Email Notifications',
            'href' => route('admin.notification-settings.index'),
            'icon' => 'mail',
            'active' => request()->routeIs('admin.notification-settings.*'),
        ];

        return [
            'name' => 'Administration',
            'items' => $items,
        ];
    }

    /**
     * Get docs & help section.
     */
    protected function getDocsHelpSection(): array
    {
        return [
            'name' => 'Support',
            'items' => [
                [
                    'name' => 'Docs & Help',
                    'href' => route('docs-help'),
                    'icon' => 'book-open',
                    'active' => request()->routeIs('docs-help'),
                ],
            ],
        ];
    }

    /**
     * Check if user can access purchasing module.
     */
    protected function canAccessPurchasing(User $user, int $businessUnitId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasTopManagementAccess()) {
            return true;
        }

        return $user->businessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->exists();
    }

    /**
     * Check if user can access purchasing admin features.
     */
    protected function canAccessPurchasingAdmin(User $user, int $businessUnitId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasTopManagementAccess()) {
            return true;
        }

        return $user->isAdminInBuOrAncestor('is_purchasing_admin', $businessUnitId);
    }

    /**
     * Check if user can access activity tracking module.
     */
    protected function canAccessActivityTracking(User $user, int $businessUnitId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasTopManagementAccess()) {
            return true;
        }

        return $user->businessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->exists();
    }

    /**
     * Check if user can access activity admin features.
     */
    protected function canAccessActivityAdmin(User $user, int $businessUnitId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasTopManagementAccess()) {
            return true;
        }

        return $user->isAdminInBuOrAncestor('is_activity_admin', $businessUnitId);
    }

    /**
     * Check if user can access sales CRM module.
     * NOTE: Currently restricted to Super Admin only (Beta feature)
     */
    protected function canAccessSalesCrm(User $user, int $businessUnitId): bool
    {
        if (! config('features.sales_crm')) {
            return false;
        }

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

    /**
     * Get IT Support section.
     * All users see ticket submission and knowledge base.
     * IT Support admins additionally see admin tools.
     */
    protected function getItSupportSection(User $user, int $businessUnitId): array
    {
        $isAdmin = $this->canAccessItSupportAdmin($user, $businessUnitId);

        $children = [
            [
                'name' => 'Submit Ticket',
                'href' => route('it-support.submit'),
                'icon' => 'plus-circle',
                'active' => request()->routeIs('it-support.submit'),
            ],
            [
                'name' => 'My Tickets',
                'href' => route('it-support.my-tickets'),
                'icon' => 'ticket',
                'active' => request()->routeIs('it-support.my-tickets*'),
            ],
            [
                'name' => 'Knowledge Base',
                'href' => route('it-support.knowledge'),
                'icon' => 'book-open',
                'active' => request()->routeIs('it-support.knowledge*')
                         && ! request()->routeIs('it-support.admin.knowledge.*'),
            ],
        ];

        if ($isAdmin) {
            $children[] = [
                'name' => 'Dashboard',
                'href' => route('it-support.admin.dashboard'),
                'icon' => 'chart-pie',
                'active' => request()->routeIs('it-support.admin.dashboard'),
            ];

            $children[] = [
                'name' => 'All Tickets',
                'href' => route('it-support.admin.tickets.index'),
                'icon' => 'list',
                'active' => request()->routeIs('it-support.admin.tickets.*'),
            ];

            $children[] = [
                'name' => 'Reporting',
                'href' => route('it-support.admin.reporting'),
                'icon' => 'bar-chart',
                'active' => request()->routeIs('it-support.admin.reporting'),
            ];

            $children[] = [
                'name' => 'Categories',
                'href' => route('it-support.admin.categories.index'),
                'icon' => 'tag',
                'active' => request()->routeIs('it-support.admin.categories.*'),
            ];

            $children[] = [
                'name' => 'Manage Knowledge',
                'href' => route('it-support.admin.knowledge.index'),
                'icon' => 'library',
                'active' => request()->routeIs('it-support.admin.knowledge.*'),
            ];

            $children[] = [
                'name' => 'SLA Settings',
                'href' => route('it-support.admin.sla-settings'),
                'icon' => 'clock',
                'active' => request()->routeIs('it-support.admin.sla-settings'),
            ];
        }

        return [
            'name' => 'IT Support',
            'items' => [
                [
                    'name' => 'IT Support',
                    'href' => route('it-support.my-tickets'),
                    'icon' => 'headphones',
                    'active' => request()->routeIs('it-support.*'),
                    'children' => $children,
                ],
            ],
        ];
    }

    /**
     * Check if user can access IT Support admin features.
     */
    protected function canAccessItSupportAdmin(User $user, int $businessUnitId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasTopManagementAccess()) {
            return true;
        }

        return $user->isAdminInBuOrAncestor('is_it_support_admin', $businessUnitId);
    }

    /**
     * Get cashflow projection section.
     * Finance users see all 3 pages; non-finance (HoD) only see Entries.
     */
    protected function getCashflowProjectionSection(User $user, int $businessUnitId): array
    {
        $isFinance = $this->cashflowProjectionAccessService->isFinanceUser($user, $businessUnitId);

        $children = [];

        if ($isFinance) {
            $children[] = [
                'name' => 'Dashboard',
                'href' => route('cashflow-projection.index'),
                'icon' => 'chart-pie',
                'active' => request()->routeIs('cashflow-projection.index'),
            ];
        }

        $children[] = [
            'name' => 'Entries',
            'href' => route('cashflow-projection.entries'),
            'icon' => 'pencil-square',
            'active' => request()->routeIs('cashflow-projection.entries'),
        ];

        if ($isFinance) {
            $children[] = [
                'name' => 'Settings',
                'href' => route('cashflow-projection.settings'),
                'icon' => 'cog-6-tooth',
                'active' => request()->routeIs('cashflow-projection.settings'),
            ];
        }

        return [
            'name' => 'Cashflow Projection',
            'items' => [
                [
                    'name' => 'Cashflow',
                    'href' => $isFinance ? route('cashflow-projection.index') : route('cashflow-projection.entries'),
                    'icon' => 'briefcase',
                    'active' => request()->routeIs('cashflow-projection.*'),
                    'children' => $children,
                ],
            ],
        ];
    }

    /**
     * Check if user can access cashflow projection module.
     */
    protected function canAccessCashflowProjection(User $user, int $businessUnitId): bool
    {
        return $this->cashflowProjectionAccessService->canAccess($user, $businessUnitId);
    }
}
