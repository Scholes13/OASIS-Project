<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VerifySidebarAlignment extends Command
{
    protected $signature = 'verify:sidebar-alignment';
    protected $description = 'Verify sidebar alignment fix is properly implemented';

    public function handle()
    {
        $this->info('🔍 VERIFYING SIDEBAR ALIGNMENT FIX');
        $this->line('═══════════════════════════════════════════════════════');
        
        // Check template structure
        $sidebarTemplate = file_get_contents(resource_path('views/livewire/layout/sidebar.blade.php'));
        
        $this->info('📋 Template Structure Check:');
        
        // Check for unified class usage
        if (strpos($sidebarTemplate, 'sidebar-menu-item') !== false) {
            $this->line('   ✅ Using unified .sidebar-menu-item class');
        } else {
            $this->line('   ❌ Missing .sidebar-menu-item class');
        }
        
        // Check for consistent icon container
        if (substr_count($sidebarTemplate, 'sidebar-icon-container') >= 2) {
            $this->line('   ✅ Consistent .sidebar-icon-container usage');
        } else {
            $this->line('   ❌ Inconsistent icon container usage');
        }
        
        // Check for removed flex w-full
        if (strpos($sidebarTemplate, 'flex w-full') === false) {
            $this->line('   ✅ Removed conflicting flex w-full classes');
        } else {
            $this->line('   ❌ Still contains flex w-full classes');
        }
        
        // Check for chevron class
        if (strpos($sidebarTemplate, 'sidebar-chevron') !== false) {
            $this->line('   ✅ Using .sidebar-chevron class for consistent chevron styling');
        } else {
            $this->line('   ❌ Missing .sidebar-chevron class');
        }
        
        $this->newLine();
        
        // Check CSS structure
        $cssFile = file_get_contents(resource_path('css/app.css'));
        
        $this->info('🎨 CSS Structure Check:');
        
        if (strpos($cssFile, '.sidebar-menu-item') !== false) {
            $this->line('   ✅ .sidebar-menu-item CSS defined');
        } else {
            $this->line('   ❌ Missing .sidebar-menu-item CSS');
        }
        
        if (strpos($cssFile, 'display: flex !important') !== false) {
            $this->line('   ✅ Forced flex display for menu items');
        } else {
            $this->line('   ❌ Missing forced flex display');
        }
        
        if (strpos($cssFile, 'width: 100% !important') !== false) {
            $this->line('   ✅ Forced full width for menu items');
        } else {
            $this->line('   ❌ Missing forced full width');
        }
        
        if (strpos($cssFile, 'gap: 0 !important') !== false) {
            $this->line('   ✅ Removed gap property for consistent spacing');
        } else {
            $this->line('   ❌ Missing gap removal');
        }
        
        if (strpos($cssFile, '.sidebar-chevron') !== false) {
            $this->line('   ✅ .sidebar-chevron CSS defined');
        } else {
            $this->line('   ❌ Missing .sidebar-chevron CSS');
        }
        
        $this->newLine();
        
        // Summary
        $this->info('📊 Alignment Fix Summary:');
        $this->line('   • Unified CSS class for all menu items');
        $this->line('   • Consistent icon container structure');
        $this->line('   • Removed conflicting Tailwind classes');
        $this->line('   • Forced alignment with !important rules');
        $this->line('   • Consistent chevron positioning');
        
        $this->newLine();
        
        $this->info('🧪 Next Steps:');
        $this->line('   1. Hard refresh browser (Ctrl+F5)');
        $this->line('   2. Check that all sidebar menu items are perfectly aligned');
        $this->line('   3. Verify no visual difference between expandable and simple items');
        $this->line('   4. Test responsive behavior on different screen sizes');
        
        return 0;
    }
}