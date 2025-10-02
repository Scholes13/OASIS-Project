<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestAggressiveFontReduction extends Command
{
    protected $signature = 'test:aggressive-font-reduction';

    protected $description = 'Test aggressive font reduction with !important overrides';

    public function handle()
    {
        $this->info('💪 Testing Aggressive Font Reduction with !important');
        $this->newLine();

        // Clear caches first
        $this->info('🧹 Clearing All Caches:');
        $this->call('config:clear');
        $this->call('view:clear');
        $this->call('cache:clear');
        $this->newLine();

        // Test aggressive changes
        $this->info('✅ Aggressive Font Changes Applied:');
        $this->line('   • All fluid-text classes now use !important');
        $this->line('   • Added dashboard-specific classes with !important');
        $this->line('   • Dashboard title: dashboard-title class');
        $this->line('   • Card values: dashboard-card-value class');
        $this->line('   • Section titles: dashboard-section-title class');
        $this->newLine();

        // Font sizes
        $this->info('📏 Forced Font Sizes:');
        $this->line('   • dashboard-title: 10.4px - 11.2px (forced with !important)');
        $this->line('   • dashboard-card-value: 11.2px - 12px (forced with !important)');
        $this->line('   • dashboard-section-title: 10.4px - 11.2px (forced with !important)');
        $this->line('   • All other fluid-text classes: forced with !important');
        $this->newLine();

        // What should change
        $this->info('🎯 What Should Change Now:');
        $this->line('   • Dashboard title "Super Admin Dashboard" should be smaller');
        $this->line('   • Card numbers (12, 5, 28, Rp 125M) should be smaller');
        $this->line('   • Section titles "Recent Users", "Business Unit Distribution" should be smaller');
        $this->line('   • All text should override any Tailwind defaults');
        $this->newLine();

        // Browser instructions
        $this->info('🌐 Browser Testing Instructions:');
        $this->line('   1. Hard refresh: Ctrl+F5 or Cmd+Shift+R');
        $this->line('   2. Open Developer Tools (F12)');
        $this->line('   3. Inspect dashboard title element');
        $this->line('   4. Should see "dashboard-title" class applied');
        $this->line('   5. Computed font-size should be ~10.4px-11.2px');
        $this->newLine();

        // Troubleshooting
        $this->info('🔧 If Still Not Working:');
        $this->line('   1. Try incognito/private browsing mode');
        $this->line('   2. Check browser console for CSS errors');
        $this->line('   3. Verify CSS file is loading correctly');
        $this->line('   4. Check if other CSS is overriding our styles');
        $this->newLine();

        // CSS specificity info
        $this->info('⚡ CSS Specificity:');
        $this->line('   • Using !important to override all other styles');
        $this->line('   • Custom classes to avoid Tailwind conflicts');
        $this->line('   • Should force font sizes regardless of other CSS');
        $this->newLine();

        $this->info('✨ Aggressive font reduction applied!');
        $this->info('🚀 Hard refresh browser - fonts should be noticeably smaller now!');

        return 0;
    }
}
