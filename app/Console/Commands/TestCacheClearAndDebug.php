<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestCacheClearAndDebug extends Command
{
    protected $signature = 'test:cache-clear-debug';

    protected $description = 'Clear all caches and debug font size issues';

    public function handle()
    {
        $this->info('🧹 Clearing All Caches & Debugging Font Issues');
        $this->newLine();

        // Clear all Laravel caches
        $this->info('✅ Clearing Laravel Caches:');
        $this->call('config:clear');
        $this->call('view:clear');
        $this->call('route:clear');
        $this->call('cache:clear');
        $this->newLine();

        // Check CSS file
        $cssPath = resource_path('css/app.css');
        if (file_exists($cssPath)) {
            $cssContent = file_get_contents($cssPath);

            $this->info('✅ CSS File Check:');
            if (str_contains($cssContent, 'fluid-text-base')) {
                $this->line('   ✓ fluid-text-base class found in CSS');
            } else {
                $this->error('   ✗ fluid-text-base class NOT found in CSS');
            }

            if (str_contains($cssContent, 'clamp(0.65rem, 0.67vw, 0.7rem)')) {
                $this->line('   ✓ Reduced font sizes found in CSS');
            } else {
                $this->error('   ✗ Reduced font sizes NOT found in CSS');
            }
            $this->newLine();
        }

        // Check dashboard view
        $dashboardPath = resource_path('views/admin/dashboard.blade.php');
        if (file_exists($dashboardPath)) {
            $dashboardContent = file_get_contents($dashboardPath);

            $this->info('✅ Dashboard View Check:');
            if (str_contains($dashboardContent, 'fluid-text-base')) {
                $this->line('   ✓ fluid-text-base class found in dashboard');
            } else {
                $this->error('   ✗ fluid-text-base class NOT found in dashboard');
            }

            if (str_contains($dashboardContent, 'fluid-text-lg')) {
                $this->line('   ✓ fluid-text-lg class found in dashboard');
            } else {
                $this->error('   ✗ fluid-text-lg class NOT found in dashboard');
            }
            $this->newLine();
        }

        // Check build files
        $manifestPath = public_path('build/manifest.json');
        if (file_exists($manifestPath)) {
            $this->info('✅ Build Files Check:');
            $this->line('   ✓ Build manifest exists');

            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest['resources/css/app.css'])) {
                $this->line('   ✓ CSS file built successfully');
                $cssFile = $manifest['resources/css/app.css']['file'];
                $this->line("   ✓ CSS file: {$cssFile}");
            }
            $this->newLine();
        }

        // Browser cache instructions
        $this->info('🌐 Browser Cache Instructions:');
        $this->line('   1. Hard refresh browser: Ctrl+F5 (Windows) or Cmd+Shift+R (Mac)');
        $this->line('   2. Or open Developer Tools (F12) and right-click refresh button → "Empty Cache and Hard Reload"');
        $this->line('   3. Or try incognito/private browsing mode');
        $this->newLine();

        // Debug info
        $this->info('🔍 Debug Information:');
        $this->line('   • CSS classes should be: fluid-text-base, fluid-text-lg, fluid-text-xs');
        $this->line('   • Font sizes should be: 10.4px-11.2px, 11.2px-12px, 8px-8.8px');
        $this->line('   • If still large, check browser inspector for applied styles');
        $this->newLine();

        // Verification steps
        $this->info('✅ Verification Steps:');
        $this->line('   1. Open browser developer tools (F12)');
        $this->line('   2. Inspect dashboard title element');
        $this->line('   3. Check if "fluid-text-base" class is applied');
        $this->line('   4. Check computed font-size value');
        $this->line('   5. If still showing old size, force refresh browser');
        $this->newLine();

        $this->info('✨ Cache clearing completed!');
        $this->info('🚀 Please hard refresh your browser to see changes.');

        return 0;
    }
}
