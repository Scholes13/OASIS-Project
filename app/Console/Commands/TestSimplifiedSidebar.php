<?php

namespace App\Console\Commands;

use App\Livewire\Layout\Sidebar;
use App\Models\User;
use Illuminate\Console\Command;

class TestSimplifiedSidebar extends Command
{
    protected $signature = 'test:simplified-sidebar';

    protected $description = 'Test simplified sidebar for super admin';

    public function handle()
    {
        $this->info('🧪 Testing Simplified Sidebar for Super Admin');
        $this->line('==========================================');
        $this->newLine();

        // Get super admin user
        $superAdmin = User::where('email', 'admin@wns.com')->first();

        if (! $superAdmin) {
            $this->error('❌ Super admin user not found');

            return 1;
        }

        $this->info("👤 Testing for user: {$superAdmin->name} ({$superAdmin->email})");
        $this->line("🔑 Global Role: {$superAdmin->global_role}");
        $this->line('✅ Is Super Admin: '.($superAdmin->isSuperAdmin() ? 'Yes' : 'No'));
        $this->newLine();

        // Simulate authentication
        auth()->login($superAdmin);

        // Create sidebar instance
        $sidebar = new Sidebar;
        $sidebar->currentRoute = 'dashboard';

        // Get navigation items
        $navigationItems = $sidebar->getNavigationItems();

        $this->info('📋 Simplified Navigation Menu:');
        $this->line('=============================');

        $menuCount = 0;
        $hasSubmenus = false;

        foreach ($navigationItems as $item) {
            $menuCount++;
            $icon = $item['icon'] ?? 'none';
            $childrenCount = count($item['children']);

            if ($childrenCount > 0) {
                $hasSubmenus = true;
                $this->line("├── {$item['name']} ({$icon}) - {$childrenCount} submenu(s)");
                foreach ($item['children'] as $child) {
                    $this->line("│   ├── {$child['name']}");
                }
            } else {
                $this->line("├── {$item['name']} ({$icon}) - Direct menu");
            }
        }

        $this->newLine();
        $this->info('📊 Menu Analysis:');
        $this->line('================');
        $this->line("Total menu items: {$menuCount}");
        $this->line('Has submenus: '.($hasSubmenus ? 'Yes' : 'No'));

        // Check for specific improvements
        $improvements = [];

        // Check if Administration submenu is removed
        $hasAdministration = collect($navigationItems)->contains('name', 'Administration');
        if (! $hasAdministration) {
            $improvements[] = '✅ Removed "Administration" submenu';
        } else {
            $improvements[] = '❌ Still has "Administration" submenu';
        }

        // Check if User Management is direct menu
        $userManagement = collect($navigationItems)->firstWhere('name', 'User Management');
        if ($userManagement && count($userManagement['children']) === 0) {
            $improvements[] = '✅ User Management is direct menu';
        } else {
            $improvements[] = '❌ User Management not found or has submenu';
        }

        // Check if Business Units is direct menu
        $businessUnits = collect($navigationItems)->firstWhere('name', 'Business Units');
        if ($businessUnits && count($businessUnits['children']) === 0) {
            $improvements[] = '✅ Business Units is direct menu';
        } else {
            $improvements[] = '❌ Business Units not found or has submenu';
        }

        // Check for duplicate Dashboard
        $dashboardCount = collect($navigationItems)->filter(function ($item) {
            return str_contains(strtolower($item['name']), 'dashboard');
        })->count();
        if ($dashboardCount === 1) {
            $improvements[] = '✅ No duplicate Dashboard';
        } else {
            $improvements[] = "❌ Found {$dashboardCount} Dashboard menus";
        }

        $this->newLine();
        $this->info('🎯 Improvements Status:');
        $this->line('======================');
        foreach ($improvements as $improvement) {
            $this->line($improvement);
        }

        $this->newLine();

        // Test route access
        $this->info('🔗 Testing Route Access:');
        $this->line('========================');

        try {
            $userManagementUrl = route('admin.users.index');
            $this->line("✅ User Management: {$userManagementUrl}");
        } catch (\Exception $e) {
            $this->error("❌ User Management route error: {$e->getMessage()}");
        }

        try {
            $businessUnitsUrl = route('admin.business-units.index');
            $this->line("✅ Business Units: {$businessUnitsUrl}");
        } catch (\Exception $e) {
            $this->error("❌ Business Units route error: {$e->getMessage()}");
        }

        $this->newLine();

        if (! $hasSubmenus && $userManagement && $businessUnits) {
            $this->info('🎉 SUCCESS: Sidebar has been simplified!');
            $this->newLine();
            $this->comment('Sidebar improvements:');
            $this->line('• Removed nested "Administration" menu');
            $this->line('• User Management is now direct menu item');
            $this->line('• Business Units is now direct menu item');
            $this->line('• No duplicate Dashboard menus');
            $this->line('• Cleaner, more efficient navigation');
        } else {
            $this->error('❌ Sidebar still needs improvements');
        }

        return 0;
    }
}
