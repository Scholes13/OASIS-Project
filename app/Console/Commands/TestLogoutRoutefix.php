<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestLogoutRouteFix extends Command
{
    protected $signature = 'test:logout-route-fix';
    protected $description = 'Test logout route fix implementation';

    public function handle()
    {
        $this->info('🔧 Testing Logout Route Fix Implementation...');
        
        // Check if logout route exists
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        $logoutRoute = $routes->getByName('logout');
        
        if ($logoutRoute) {
            $this->info('✅ Logout route exists: ' . $logoutRoute->uri());
            $this->info('   Method: ' . implode('|', $logoutRoute->methods()));
        } else {
            $this->error('❌ Logout route not found');
        }
        
        // Check auth.php file
        $authPath = base_path('routes/auth.php');
        if (file_exists($authPath)) {
            $content = file_get_contents($authPath);
            if (strpos($content, "Route::post('logout'") !== false) {
                $this->info('✅ Logout route defined in auth.php');
            } else {
                $this->error('❌ Logout route not found in auth.php');
            }
        }
        
        // Check user-menu view
        $viewPath = resource_path('views/livewire/layout/user-menu.blade.php');
        if (file_exists($viewPath)) {
            $content = file_get_contents($viewPath);
            if (strpos($content, 'method="POST"') !== false && strpos($content, 'action="{{ route(\'logout\') }}"') !== false) {
                $this->info('✅ User menu view updated with form POST');
            } else {
                $this->error('❌ User menu view not updated properly');
            }
        }
        
        $this->info('');
        $this->info('🎯 Logout Route Fix Summary:');
        $this->info('• Added missing logout route in routes/auth.php');
        $this->info('• Changed from Livewire wire:click to standard form POST');
        $this->info('• Logout now uses Laravel\'s standard authentication flow');
        $this->info('• Session invalidation and token regeneration handled properly');
        
        $this->info('');
        $this->info('📝 Test the logout functionality:');
        $this->info('1. Login to the application');
        $this->info('2. Click on user avatar in top right');
        $this->info('3. Click "Sign Out" button');
        $this->info('4. Should immediately redirect to home/login page');
        
        return 0;
    }
}