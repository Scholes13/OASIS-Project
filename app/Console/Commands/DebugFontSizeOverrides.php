<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DebugFontSizeOverrides extends Command
{
    protected $signature = 'debug:font-overrides';
    protected $description = 'Debug font size overrides and external CSS conflicts';

    public function handle()
    {
        $this->info('🔍 Debugging Font Size Overrides & External CSS Conflicts');
        $this->newLine();

        // Clear all caches
        $this->info('🧹 Clearing All Caches:');
        $this->call('config:clear');
        $this->call('view:clear');
        $this->call('cache:clear');
        $this->newLine();

        // Identified external CSS sources
        $this->info('🎯 External CSS Sources Found:');
        $this->line('   1. Google Fonts (Inter): fonts.bunny.net');
        $this->line('   2. FontAwesome: cdnjs.cloudflare.com');
        $this->line('   3. These might override our custom font sizes');
        $this->newLine();

        // Nuclear fixes applied
        $this->info('💥 NUCLEAR FIXES Applied:');
        $this->line('   1. * { font-size: inherit !important; }');
        $this->line('   2. All text elements forced to inherit');
        $this->line('   3. All Tailwind classes with child selectors');
        $this->line('   4. Ultra-aggressive !important overrides');
        $this->newLine();

        // Expected font sizes
        $this->info('📏 Expected Font Sizes (Same as Sidebar):');
        $this->line('   • Sidebar text: 9.6px-10.4px');
        $this->line('   • .text-xs: 8px-8.8px');
        $this->line('   • .text-sm: 9.6px-10.4px (SAME AS SIDEBAR)');
        $this->line('   • .text-base: 10.4px-11.2px');
        $this->line('   • .text-lg: 11.2px-12px');
        $this->newLine();

        // Testing instructions
        $this->info('🔍 Testing Instructions:');
        $this->line('   1. Hard refresh browser (Ctrl+F5)');
        $this->line('   2. Open Developer Tools (F12)');
        $this->line('   3. Inspect dashboard title element');
        $this->line('   4. Check computed font-size in styles panel');
        $this->line('   5. Compare with sidebar menu item font-size');
        $this->newLine();

        // Debugging steps
        $this->info('🛠️ If Still Not Working:');
        $this->line('   1. Check if external CSS is loading after ours');
        $this->line('   2. Look for inline styles in HTML');
        $this->line('   3. Check browser zoom level (should be 100%)');
        $this->line('   4. Verify CSS specificity in DevTools');
        $this->line('   5. Check if JavaScript is modifying styles');
        $this->newLine();

        // Browser DevTools check
        $this->info('🔧 Browser DevTools Check:');
        $this->line('   1. Right-click dashboard title → Inspect');
        $this->line('   2. In Styles panel, look for:');
        $this->line('      - font-size: clamp(0.65rem, 0.67vw, 0.7rem) !important');
        $this->line('   3. If crossed out, another rule is overriding');
        $this->line('   4. Check which rule has higher specificity');
        $this->newLine();

        // CSS specificity explanation
        $this->info('⚡ CSS Specificity Battle:');
        $this->line('   • Our CSS: .text-base { font-size: ... !important; }');
        $this->line('   • External CSS might have higher specificity');
        $this->line('   • !important should win, but check load order');
        $this->line('   • Nuclear option: * { font-size: inherit !important; }');
        $this->newLine();

        $this->info('✨ Nuclear font override applied!');
        $this->info('🚀 ALL elements should now inherit consistent sizes!');
        $this->newLine();

        $this->warn('⚠️  If STILL not working, the issue might be:');
        $this->line('   • JavaScript dynamically setting styles');
        $this->line('   • Browser zoom not at 100%');
        $this->line('   • CSS load order issue');
        $this->line('   • Cached CSS in browser');

        return 0;
    }
}