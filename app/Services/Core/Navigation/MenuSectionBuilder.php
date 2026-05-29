<?php

namespace App\Services\Core\Navigation;

use App\Models\Core\User;

/**
 * Builds the static menu sections rendered by NavigationService.
 *
 * Extracted from {@see \App\Services\Core\NavigationService} to keep that
 * orchestrator under the 350-line service cap.  Visibility checks live in
 * {@see MenuVisibilityResolver} — this class is responsible only for
 * shaping the array structure once a section has been deemed visible.
 */
class MenuSectionBuilder
{
    public function __construct(
        protected MenuVisibilityResolver $visibility,
    ) {}

    /** Dashboard section (always visible). */
    public function dashboardSection(User $user): array
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

    /** Purchasing section (PR/Stock/Approvals + admin sub-tree). */
    public function purchasingSection(User $user, int $businessUnitId): array
    {
        $items = [];

        $purchasingChildren = [
            [
                'name' => 'Purchase Requests',
                'href' => route('purchase-requests.index'),
                'icon' => 'file-text',
                'active' => request()->routeIs('purchase-requests.*') && ! request()->routeIs('purchase-requests.all'),
            ],
            [
                'name' => 'Stock Requests',
                'href' => route('stock-requests.index'),
                'icon' => 'package',
                'active' => request()->routeIs('stock-requests.*'),
            ],
            [
                'name' => 'All Requests',
                'href' => route('purchase-requests.all'),
                'icon' => 'list',
                'active' => request()->routeIs('purchase-requests.all'),
            ],
            [
                'name' => 'Approvals',
                'href' => route('approvals.index'),
                'icon' => 'check-circle',
                'active' => request()->routeIs('approvals.*'),
            ],
        ];

        $items[] = [
            'name' => 'Purchasing',
            'href' => route('purchase-requests.index'),
            'icon' => 'shopping-cart',
            'active' => request()->routeIs('purchase-requests.*')
                || request()->routeIs('stock-requests.*')
                || request()->routeIs('approvals.*'),
            'children' => $purchasingChildren,
        ];

        if ($this->visibility->canAccessPurchasingAdmin($user, $businessUnitId)) {
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
                        'active' => request()->routeIs('purchasing.admin.tasks*')
                            && ! request()->routeIs('purchasing.admin.task-history*'),
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

        return ['name' => 'Purchasing', 'items' => $items];
    }

    /** Activity tracking section. */
    public function activityTrackingSection(User $user, int $businessUnitId): array
    {
        $items = [];

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

        if ($this->visibility->canAccessActivityAdmin($user, $businessUnitId)) {
            $items[0]['children'][] = [
                'name' => 'Activity Admin',
                'href' => route('activity.admin.dashboard'),
                'icon' => 'shield-check',
                'active' => request()->routeIs('activity.admin.*'),
            ];
        }

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

        return ['name' => 'Activity Tracking', 'items' => $items];
    }

    /** Sales CRM section (visible only when feature flag + super-admin). */
    public function salesCrmSection(User $user, int $businessUnitId): array
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

    /** Administration section (super-admin only). */
    public function administrationSection(User $user): array
    {
        if (! $user->isSuperAdmin()) {
            return [];
        }

        $items = [
            [
                'name' => 'Users',
                'href' => route('admin.users.index'),
                'icon' => 'user',
                'active' => request()->routeIs('admin.users.*'),
            ],
            [
                'name' => 'Activity Admin Assignment',
                'href' => route('admin.activity-admins.index'),
                'icon' => 'shield-check',
                'active' => request()->routeIs('admin.activity-admins.*'),
            ],
            [
                'name' => 'Purchasing Admin Assignment',
                'href' => route('admin.purchasing-admins.index'),
                'icon' => 'shopping-cart',
                'active' => request()->routeIs('admin.purchasing-admins.*'),
            ],
            [
                'name' => 'IT Support Admin Assignment',
                'href' => route('admin.it-support-admins.index'),
                'icon' => 'headphones',
                'active' => request()->routeIs('admin.it-support-admins.*'),
            ],
            [
                'name' => 'Business Units',
                'href' => route('admin.business-units.index'),
                'icon' => 'briefcase',
                'active' => request()->routeIs('admin.business-units.*'),
            ],
            [
                'name' => 'Departments',
                'href' => route('admin.departments.index'),
                'icon' => 'office-building',
                'active' => request()->routeIs('admin.departments.*'),
            ],
            [
                'name' => 'Email Notifications',
                'href' => route('admin.notification-settings.index'),
                'icon' => 'mail',
                'active' => request()->routeIs('admin.notification-settings.*'),
            ],
        ];

        return ['name' => 'Administration', 'items' => $items];
    }

    /** Docs &amp; help section (always visible). */
    public function docsHelpSection(): array
    {
        $items = [
            [
                'name' => 'Docs & Help',
                'href' => route('docs-help'),
                'icon' => 'book-open',
                'active' => request()->routeIs('docs-help'),
            ],
        ];

        // Knowledge Base lives under the IT Support route group. Skip the
        // entry when the IT Support feature flag is off so we don't call
        // route() with a name that no longer exists.
        if (config('features.it_support', true)) {
            $items[] = [
                'name' => 'Knowledge Base',
                'href' => route('it-support.knowledge'),
                'icon' => 'library',
                'active' => request()->routeIs('it-support.knowledge')
                    || request()->routeIs('it-support.knowledge.*'),
            ];
        }

        return [
            'name' => 'Support',
            'items' => $items,
        ];
    }

    /**
     * Cashflow projection section.
     * Finance users see all 3 pages; non-finance (HoD) only see Entries.
     */
    public function cashflowProjectionSection(User $user, int $businessUnitId): array
    {
        $isFinance = $this->visibility->isFinanceUser($user, $businessUnitId);

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
                    'href' => $isFinance
                        ? route('cashflow-projection.index')
                        : route('cashflow-projection.entries'),
                    'icon' => 'briefcase',
                    'active' => request()->routeIs('cashflow-projection.*'),
                    'children' => $children,
                ],
            ],
        ];
    }
}
