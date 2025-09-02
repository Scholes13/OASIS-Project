<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Livewire\Layout\Sidebar;

class VerifySidebarSimplification extends Command
{
    protected $signature = 'verify:sidebar-simplification';
    protected $description = 'Verify sidebar simplification is working correctly';

    public function handle()
    {
        $this->info('🔍 Verifying Sidebar Simplification');
        $this->line('==================================');
        $this->newLine();

        // Get super admin user
        $superAdmin = User::where('email', 'admin@wns.com')->first();
        
        if (!$superAdmin) {
            $this->error('❌ Super admin user not found');
            return 1;
        }

        $this->info("👤 Super Admin: {$superAdmin->name}");
        $this->newLine();

        // Simulate authentication
        auth()->login($superAdmin);
        
        // Create sidebar instance
        $sidebar = new Sidebar();
        $sidebar->currentRoute = 'dashboard';
        
        // Get navigation items
        $navigationItems = $sidebar->getNavigationItems();
        
        $this->info('📋 Current Sidebar Structure:');
        $this->line('============================');
        
        $checks = [
            'has_administration' => false,
            'has_user_management_direct' => false,
            'has_business_units_direct' => false,
            'dashboard_count' => 0,
            'total_menus' => count($navigationItems)
        ];
        
        foreach ($navigationItems as $item) {
            $childrenCount = count($item['children']);
            $indent = $childrenCount > 0 ? '├──' : '├──';
            
            $this->line("{$indent} {$item['name']} ({$item['icon']})");
            
            // Check for Administration menu
            if ($item['name'] === 'Administration') {
                $checks['has_administration'] = true;
            }
            
            // Check for direct User Management
            if ($item['name'] === 'User Management' && $childrenCount === 0) {
                $checks['has_user_management_direct'] = true;
            }
            
            // Check for direct Business Units
            if ($item['name'] === 'Business Units' && $childrenCount === 0) {
                $checks['has_business_units_direct'] = true;
            }
            
            // Count dashboards
            if (str_contains(strtolower($item['name']), 'dashboard')) {
                $checks['dashboard_count']++;
            }
            
            // Show children if any
            if ($childrenCount > 0) {
                foreach ($item['children'] as $child) {
                    $this->line("│   ├── {$child['name']}");
                    
                    // Count dashboards in children too
                    if (str_contains(strtolower($child['name']), 'dashboard')) {
                        $checks['dashboard_count']++;
                    }
                }
            }
        }
        
        $this->newLine();
        $this->info('✅ Simplification Checklist:');
        $this->line('============================');
        
        // Check results
        $allGood = true;
        
        if (!$checks['has_administration']) {
            $this->line('✅ Administration submenu: REMOVED');
        } else {
            $this->error('❌ Administration submenu: Still exists');
            $allGood = false;
        }
        
        if ($checks['has_user_management_direct']) {
            $this->line('✅ User Management: Direct menu');
        } else {
            $this->error('❌ User Management: Not direct or missing');
            $allGood = false;
        }
        
        if ($checks['has_business_units_direct']) {
            $this->line('✅ Business Units: Direct menu');
        } else {
            $this->error('❌ Business Units: Not direct or missing');
            $allGood = false;
        }
        
        if ($checks['dashboard_count'] === 1) {
            $this->line('✅ Dashboard: Single instance (no duplicates)');
        } else {
            $this->error("❌ Dashboard: Found {$checks['dashboard_count']} instances");
            $allGood = false;
        }
        
        $this->line("✅ Total menu items: {$checks['total_menus']} (efficient count)");
        
        $this->newLine();
        
        // Test icon availability
        $this->info('🎨 Icon Availability:');
        $this->line('====================');
        
        $requiredIcons = ['users', 'office-building', 'home', 'document-text', 'check-circle'];
        
        foreach ($requiredIcons as $icon) {
            $iconPath = "resources/views/components/icons/{$icon}.blade.php";
            if (file_exists($iconPath)) {
                $this->line("✅ {$icon}.blade.php: Available");
            } else {
                $this->error("❌ {$icon}.blade.php: Missing");
                $allGood = false;
            }
        }
        
        $this->newLine();
        
        // Test route accessibility
        $this->info('🔗 Route Accessibility:');
        $this->line('======================');
        
        $routes = [
            'admin.users.index' => 'User Management',
            'admin.business-units.index' => 'Business Units'
        ];
        
        foreach ($routes as $routeName => $description) {
            try {
                $url = route($routeName);
                $this->line("✅ {$description}: {$url}");
            } catch (\Exception $e) {
                $this->error("❌ {$description}: Route error");
                $allGood = false;
            }
        }
        
        $this->newLine();
        
        if ($allGood) {
            $this->info('🎉 SIDEBAR SIMPLIFICATION SUCCESSFUL!');
            $this->newLine();
            
            $this->comment('✨ Improvements achieved:');
            $this->line('• Removed unnecessary "Administration" submenu');
            $this->line('• User Management is now directly accessible');
            $this->line('• Business Units is now directly accessible');
            $this->line('• Eliminated duplicate Dashboard menus');
            $this->line('• Reduced navigation complexity');
            $this->line('• Improved user experience for super admin');
            
            $this->newLine();
            $this->comment('🚀 Ready to use:');
            $this->line('1. Login as admin@wns.com');
            $this->line('2. See simplified sidebar with direct access');
            $this->line('3. Click "User Management" - direct access');
            $this->line('4. Click "Business Units" - direct access');
            
        } else {
            $this->error('❌ SIDEBAR SIMPLIFICATION INCOMPLETE');
            $this->line('Please check the failed items above.');
        }
        
        return $allGood ? 0 : 1;
    }
}