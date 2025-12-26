<?php

namespace App\Livewire\Layout;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class Sidebar extends Component
{
    public $currentRoute;

    public $navigationItems;

    public $isInitialized = false;

    public function mount()
    {
        $this->currentRoute = Route::currentRouteName();
        $this->navigationItems = $this->getNavigationItems();
        $this->isInitialized = true;
    }

    public function hydrate()
    {
        // Only re-initialize if route changed or not initialized
        $currentRoute = Route::currentRouteName();
        if (! $this->isInitialized || $this->currentRoute !== $currentRoute) {
            $this->currentRoute = $currentRoute;
            $this->navigationItems = $this->getNavigationItems();
            $this->isInitialized = true;
        }
    }

    public function render()
    {
        return view('livewire.layout.sidebar');
    }

    /**
     * Get navigation items based on user role and permissions
     */
    public function getNavigationItems()
    {
        $user = Auth::user();
        $currentRole = session('current_user_role', 'user');

        // Check if user has any active business units
        $hasBusinessUnit = $user && $user->activeBusinessUnits()->exists();
        $currentBusinessUnitId = session('current_business_unit_id');

        // Base navigation
        $navigation = [];

        // Purchasing module - parent menu with Dashboard, PR, SR, Approvals
        if ($hasBusinessUnit && $currentBusinessUnitId) {
            $navigation[] = [
                'name' => 'Purchasing',
                'href' => route('dashboard'),
                'icon' => 'shopping-cart',
                'current' => ($this->currentRoute === 'dashboard'
                          || str_starts_with($this->currentRoute, 'purchase-requests') 
                          || str_starts_with($this->currentRoute, 'stock-requests')
                          || ($this->currentRoute !== null && str_starts_with($this->currentRoute, 'purchasing') && !str_starts_with($this->currentRoute, 'purchasing.admin'))
                          || str_starts_with($this->currentRoute, 'approvals')),
                'children' => [
                    [
                        'name' => 'Dashboard',
                        'href' => route('dashboard'),
                        'current' => $this->currentRoute === 'dashboard',
                    ],
                    [
                        'name' => 'Purchase Request',
                        'href' => route('purchase-requests.index'),
                        'current' => $this->currentRoute === 'purchase-requests.index',
                    ],
                    [
                        'name' => 'Stock Request',
                        'href' => route('stock-requests.index'),
                        'current' => $this->currentRoute === 'stock-requests.index',
                    ],
                    [
                        'name' => 'All Requests',
                        'href' => route('purchasing.all-requests'),
                        'current' => $this->currentRoute === 'purchasing.all-requests',
                    ],
                    [
                        'name' => 'Approvals',
                        'href' => route('approvals.index'),
                        'current' => str_starts_with($this->currentRoute, 'approvals'),
                    ],
                ],
            ];
        }

        // Purchasing Admin - only for authorized users
        if ($user && $user->can('access-purchasing-admin')) {
            $purchasingAdminChildren = [];
            
            // Check if user is top management (view-only access)
            $managementPositions = ['Top Management', 'Director', 'CEO', 'General Manager', 'Managing Director'];
            
            // First check direct assignment to current BU
            $userBu = $user->businessUnits()
                ->where('business_unit_id', $currentBusinessUnitId)
                ->with('position')
                ->first();
            $isTopManagement = $userBu && $userBu->position && in_array($userBu->position->name, $managementPositions);
            
            // If no direct assignment, check if user is top management in parent BU
            // This allows WG top management to view child BUs (WNS, UK, MRP)
            if (!$isTopManagement && !$userBu) {
                $currentBu = \App\Models\Core\BusinessUnit::find($currentBusinessUnitId);
                if ($currentBu && $currentBu->parent_id) {
                    // Check if user is top management in parent BU
                    $parentUserBu = $user->businessUnits()
                        ->where('business_unit_id', $currentBu->parent_id)
                        ->with('position')
                        ->first();
                    $isTopManagement = $parentUserBu && $parentUserBu->position && in_array($parentUserBu->position->name, $managementPositions);
                }
            }
            
            // Check if user is purchasing admin
            $isPurchasingAdmin = $userBu && $userBu->is_purchasing_admin;

            // Determine which audit history page to show based on role
            if ($user->global_role === 'super_admin') {
                // Super Admin sees all audit history
                $purchasingAdminChildren[] = [
                    'name' => 'Audit History',
                    'href' => route('purchasing.admin.audit-history'),
                    'current' => $this->currentRoute === 'purchasing.admin.audit-history',
                ];
            } elseif ($isTopManagement) {
                // Top management sees aggregated history from all admins
                $purchasingAdminChildren[] = [
                    'name' => 'History',
                    'href' => route('purchasing.admin.management-history'),
                    'current' => $this->currentRoute === 'purchasing.admin.management-history',
                ];
            } else {
                // Non-management users see department audit and personal history
                // Check if user is a department manager
                $isDepartmentManager = $user->businessUnits()
                    ->where('business_unit_id', $currentBusinessUnitId)
                    ->whereHas('position', fn($q) => $q->whereIn('access_level', [1, 2, 3]))
                    ->exists();

                if ($isDepartmentManager) {
                    // Department managers see department audit history
                    $purchasingAdminChildren[] = [
                        'name' => 'Department Audit',
                        'href' => route('purchasing.admin.department-audit-history'),
                        'current' => $this->currentRoute === 'purchasing.admin.department-audit-history',
                    ];
                }

                // All purchasing admins see their personal history
                if ($isPurchasingAdmin) {
                    $purchasingAdminChildren[] = [
                        'name' => 'My Task History',
                        'href' => route('purchasing.admin.personal-task-history'),
                        'current' => $this->currentRoute === 'purchasing.admin.personal-task-history',
                    ];
                }
            }
            
            // Build base menu items
            $baseMenuItems = [
                [
                    'name' => 'Dashboard',
                    'href' => route('purchasing.admin.dashboard'),
                    'current' => $this->currentRoute === 'purchasing.admin.dashboard',
                ],
            ];
            
            // Tasks menu - only for purchasing admins, not for top management
            if ($isPurchasingAdmin && !$isTopManagement) {
                $baseMenuItems[] = [
                    'name' => 'Tasks',
                    'href' => route('purchasing.admin.tasks'),
                    'current' => $this->currentRoute === 'purchasing.admin.tasks',
                ];
            }

            $navigation[] = [
                'name' => 'Purchasing Admin',
                'href' => route('purchasing.admin.dashboard'),
                'icon' => 'clipboard-list',
                'current' => str_starts_with($this->currentRoute, 'purchasing.admin'),
                'children' => array_merge($baseMenuItems, $purchasingAdminChildren),
            ];
        }

        // Add Sales CRM section (permission-based)
        if ($user && ($user->can('view_activities') || $user->can('view_contacts'))) {
            $salesCrmChildren = [];

            // Activities menu item
            if ($user->can('view_activities')) {
                $salesCrmChildren[] = [
                    'name' => 'Activities',
                    'href' => route('sales-crm.activities.index'),
                    'current' => str_starts_with($this->currentRoute, 'sales-crm.activities'),
                ];
            }

            // Contacts menu item
            if ($user->can('view_contacts')) {
                $salesCrmChildren[] = [
                    'name' => 'Contacts',
                    'href' => route('sales-crm.contacts.index'),
                    'current' => str_starts_with($this->currentRoute, 'sales-crm.contacts'),
                ];
            }

            // Only add Sales CRM section if user has at least one permission
            if (! empty($salesCrmChildren)) {
                $navigation[] = [
                    'name' => 'Sales CRM',
                    'href' => $salesCrmChildren[0]['href'], // First available menu
                    'icon' => 'briefcase',
                    'current' => str_starts_with($this->currentRoute, 'sales-crm'),
                    'children' => $salesCrmChildren,
                ];
            }
        }

        // Add admin sections for managers and admins
        if (in_array($currentRole, ['manager', 'admin', 'super_admin'])) {
            $reportsChildren = [];

            // Check if reports routes exist and add them conditionally
            try {
                route('reports.purchase-requests');
                $reportsChildren[] = [
                    'name' => 'PR Statistics',
                    'href' => route('reports.purchase-requests'),
                    'current' => $this->currentRoute === 'reports.purchase-requests',
                ];
            } catch (\Exception) {
                // Route doesn't exist, don't add the menu item
            }

            try {
                route('reports.approvals');
                $reportsChildren[] = [
                    'name' => 'Approval Analytics',
                    'href' => route('reports.approvals'),
                    'current' => $this->currentRoute === 'reports.approvals',
                ];
            } catch (\Exception) {
                // Route doesn't exist, don't add the menu item
            }

            // Only add Reports section if there are child items
            if (! empty($reportsChildren)) {
                $navigation[] = [
                    'name' => 'Reports',
                    'href' => ! empty($reportsChildren) ? $reportsChildren[0]['href'] : '#',
                    'icon' => 'chart-bar',
                    'current' => str_starts_with($this->currentRoute, 'reports'),
                    'children' => $reportsChildren,
                ];
            }
        }

        // Docs & Help - available for all authenticated users
        $navigation[] = [
            'name' => 'Docs & Help',
            'href' => route('docs-help'),
            'icon' => 'book-open',
            'current' => $this->currentRoute === 'docs-help',
            'children' => [],
        ];

        // Add super admin menu items directly (no submenu)
        if ($user && $user->global_role === 'super_admin') {
            // User Management
            $navigation[] = [
                'name' => 'User Management',
                'href' => route('admin.users.index'),
                'icon' => 'users',
                'current' => str_starts_with($this->currentRoute, 'admin.users'),
                'children' => [],
            ];

            // Business Units
            $navigation[] = [
                'name' => 'Business Units',
                'href' => route('admin.business-units.index'),
                'icon' => 'office-building',
                'current' => str_starts_with($this->currentRoute, 'admin.business-units'),
                'children' => [],
            ];

            // Departments
            $navigation[] = [
                'name' => 'Departments',
                'href' => route('admin.departments.index'),
                'icon' => 'collection',
                'current' => str_starts_with($this->currentRoute, 'admin.departments'),
                'children' => [],
            ];

            // PR Categories
            $navigation[] = [
                'name' => 'PR Categories',
                'href' => route('admin.pr-categories.index'),
                'icon' => 'tag',
                'current' => str_starts_with($this->currentRoute, 'admin.pr-categories'),
                'children' => [],
            ];

            // Email Notifications
            $navigation[] = [
                'name' => 'Email Notifications',
                'href' => route('admin.notification-settings.index'),
                'icon' => 'mail',
                'current' => str_starts_with($this->currentRoute, 'admin.notification-settings'),
                'children' => [],
            ];

            // SLA Settings
            $navigation[] = [
                'name' => 'SLA Settings',
                'href' => route('admin.sla-settings.index'),
                'icon' => 'clock',
                'current' => str_starts_with($this->currentRoute, 'admin.sla-settings'),
                'children' => [],
            ];
        }

        return $navigation;
    }
}
