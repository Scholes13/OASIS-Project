<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CompareAdminUserDashboard extends Command
{
    protected $signature = 'debug:compare-dashboards';
    protected $description = 'Compare admin vs user dashboard to find overlay source';

    public function handle()
    {
        $this->info('🔍 COMPARING ADMIN VS USER DASHBOARD...');
        $this->newLine();

        $this->warn('📋 KEY OBSERVATION:');
        $this->line('   • Admin dashboard: HAS overlay issue');
        $this->line('   • User dashboard: NO overlay issue');
        $this->line('   • Same layout (app.blade.php)');
        $this->line('   • Same CSS files');
        $this->line('   • Different dashboard views');
        $this->newLine();

        $this->info('🎯 DIFFERENCES TO INVESTIGATE:');
        $this->line('   1. Admin dashboard uses resources/views/admin/dashboard.blade.php');
        $this->line('   2. User dashboard uses resources/views/dashboard.blade.php');
        $this->line('   3. Admin has complex stats grid with absolute overlays');
        $this->line('   4. User has simpler card layout');
        $this->line('   5. Admin may have different Livewire components');
        $this->newLine();

        $this->info('🔧 INVESTIGATION PLAN:');
        $this->line('   • Compare both dashboard view files');
        $this->line('   • Check for admin-specific CSS classes');
        $this->line('   • Look for admin-specific Livewire components');
        $this->line('   • Check for different z-index usage');
        $this->line('   • Compare HTML structure differences');

        return 0;
    }
}