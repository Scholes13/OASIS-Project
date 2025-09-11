<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DebugSidebarAlignment extends Command
{
    protected $signature = 'debug:sidebar-alignment';
    protected $description = 'Debug sidebar alignment issues with detailed analysis';

    public function handle()
    {
        $this->info('🔍 DEBUG: Sidebar Alignment Issues - Detailed Analysis');
        $this->newLine();

        // Clear all caches
        $this->info('🧹 Clearing All Caches:');
        $this->call('config:clear');
        $this->call('view:clear');
        $this->call('cache:clear');
        $this->newLine();

        // Current implementation
        $this->info('📋 Current Implementation:');
        $this->line('   • Template: Uses .sidebar-icon-container for ALL items');
        $this->line('   • CSS: Single rule for .sidebar-icon-container');
        $this->line('   • Structure: Identical HTML for all menu items');
        $this->line('   • Removed conflicting CSS rules');
        $this->newLine();

        // CSS specifications
        $this->info('🎨 CSS Specifications:');
        $this->line('   .sidebar-icon-container {');
        $this->line('     display: flex !important;');
        $this->line('     align-items: center !important;');
        $this->line('     justify-content: center !important;');
        $this->line('     flex-shrink: 0 !important;');
        $this->line('     width: 24px-26.4px !important;');
        $this->line('     height: 24px-26.4px !important;');
        $this->line('     margin-right: 6px-7px !important;');
        $this->line('   }');
        $this->newLine();

        // Debugging steps
        $this->info('🔧 Browser Debugging Steps:');
        $this->line('   1. Hard refresh (Ctrl+F5)');
        $this->line('   2. Right-click "Purchase Requests" → Inspect');
        $this->line('   3. Look for .sidebar-icon-container element');
        $this->line('   4. Check computed styles in DevTools');
        $this->line('   5. Compare with Dashboard icon container');
        $this->newLine();

        // What to check
        $this->info('👀 What to Check in DevTools:');
        $this->line('   • Width: Should be 24px-26.4px for ALL containers');
        $this->line('   • Height: Should be 24px-26.4px for ALL containers');
        $this->line('   • Margin-right: Should be 6px-7px for ALL containers');
        $this->line('   • Display: Should be flex for ALL containers');
        $this->line('   • Justify-content: Should be center for ALL containers');
        $this->newLine();

        // Possible issues
        $this->info('⚠️ Possible Issues to Look For:');
        $this->line('   • CSS not loading (check Network tab)');
        $this->line('   • Browser cache (try incognito mode)');
        $this->line('   • External CSS overriding (check Computed styles)');
        $this->line('   • JavaScript modifying styles');
        $this->line('   • Different icon sizes causing visual misalignment');
        $this->newLine();

        // Icon size check
        $this->info('🎯 Icon Size Check:');
        $this->line('   • All icons should use .sidebar-icon-standard');
        $this->line('   • Size: 14px-15.2px (0.875rem-0.95rem)');
        $this->line('   • Centered within 24px-26.4px containers');
        $this->line('   • Same visual weight and positioning');
        $this->newLine();

        // Manual verification
        $this->info('📐 Manual Verification:');
        $this->line('   1. Take screenshot of sidebar');
        $this->line('   2. Draw vertical line through Dashboard icon');
        $this->line('   3. Check if Purchase Requests icon aligns');
        $this->line('   4. Check if Approvals icon aligns');
        $this->line('   5. All should be on same vertical line');
        $this->newLine();

        // If still not working
        $this->info('🚨 If Still Not Working:');
        $this->line('   • Check if icons have different intrinsic sizes');
        $this->line('   • Verify all icons are SVG with same viewBox');
        $this->line('   • Look for padding/margin on individual icons');
        $this->line('   • Check if Alpine.js is interfering');
        $this->newLine();

        // Next steps
        $this->info('🎯 Next Steps:');
        $this->line('   1. Test in browser with DevTools open');
        $this->line('   2. Report specific CSS values you see');
        $this->line('   3. Check if alignment works in different browsers');
        $this->line('   4. Verify icon files are identical in structure');
        $this->newLine();

        $this->info('✨ Debug analysis complete!');
        $this->info('🔍 Use browser DevTools to investigate further!');
        $this->info('📊 Report findings for targeted solution!');

        return 0;
    }
}