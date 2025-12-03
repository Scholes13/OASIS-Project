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

        // Base navigation - Dashboard always visible
        $navigation = [
            [
                'name' => 'Dashboard',
                'href' => route('dashboard'),
                'icon' => 'home',
                'current' => $this->currentRoute === 'dashboard',
                'children' => [],
            ],
        ];

        // Purchase Request module - only visible if user has active business unit
        if ($hasBusinessUnit && $currentBusinessUnitId) {
            $navigation[] = [
                'name' => 'Purchase Requests',
                'href' => route('purchase-requests.index'),
                'icon' => 'document-text',
                'current' => str_starts_with($this->currentRoute, 'purchase-requests'),
                'children' => [
                    [
                        'name' => 'Create New PR',
                        'href' => route('purchase-requests.create'),
                        'current' => $this->currentRoute === 'purchase-requests.create',
                    ],
                    [
                        'name' => 'My History',
                        'href' => route('purchase-requests.index'),
                        'current' => $this->currentRoute === 'purchase-requests.index',
                    ],
                    [
                        'name' => 'All Requests',
                        'href' => route('purchase-requests.all'),
                        'current' => $this->currentRoute === 'purchase-requests.all',
                    ],
                ],
            ];

            // Approvals - also requires business unit
            $navigation[] = [
                'name' => 'Approvals',
                'href' => route('approvals.index'),
                'icon' => 'check-circle',
                'current' => str_starts_with($this->currentRoute, 'approvals'),
                'children' => [],
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
        }

        return $navigation;
    }
}
