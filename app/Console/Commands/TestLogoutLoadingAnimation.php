<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestLogoutLoadingAnimation extends Command
{
    protected $signature = 'test:logout-loading-animation';
    protected $description = 'Test logout loading animation implementation';

    public function handle()
    {
        $this->info('🎬 Testing Logout Loading Animation Implementation...');
        
        // Check user-menu view for loading states
        $viewPath = resource_path('views/livewire/layout/user-menu.blade.php');
        if (file_exists($viewPath)) {
            $content = file_get_contents($viewPath);
            
            $checks = [
                'loggingOut: false' => 'Alpine.js loading state variable',
                'x-show="loggingOut"' => 'Loading spinner visibility',
                'x-show="!loggingOut"' => 'Normal icon visibility',
                'Signing Out...' => 'Loading text',
                'animate-spin' => 'Spinner animation',
                'disabled:opacity-50' => 'Disabled button styling',
                'x-bind:disabled="loggingOut"' => 'Button disable binding'
            ];
            
            foreach ($checks as $pattern => $description) {
                if (strpos($content, $pattern) !== false) {
                    $this->info("✅ {$description}");
                } else {
                    $this->error("❌ Missing: {$description}");
                }
            }
        }
        
        // Check layout for global loading overlay
        $layoutPath = resource_path('views/layouts/app.blade.php');
        if (file_exists($layoutPath)) {
            $content = file_get_contents($layoutPath);
            
            $globalChecks = [
                'globalLoading: false' => 'Global loading state variable',
                'Global Loading Overlay' => 'Loading overlay comment',
                'z-[9999]' => 'High z-index for overlay',
                'backdrop-blur-sm' => 'Backdrop blur effect',
                'Signing out...' => 'Global loading text'
            ];
            
            foreach ($globalChecks as $pattern => $description) {
                if (strpos($content, $pattern) !== false) {
                    $this->info("✅ {$description}");
                } else {
                    $this->error("❌ Missing: {$description}");
                }
            }
        }
        
        $this->info('');
        $this->info('🎯 Loading Animation Features:');
        $this->info('• Button loading state with spinner animation');
        $this->info('• Text changes from "Sign Out" to "Signing Out..."');
        $this->info('• Button becomes disabled during logout process');
        $this->info('• Global loading overlay with backdrop blur');
        $this->info('• Smooth transitions and animations');
        
        $this->info('');
        $this->info('📝 Test the loading animation:');
        $this->info('1. Login to the application');
        $this->info('2. Click on user avatar in top right');
        $this->info('3. Click "Sign Out" button');
        $this->info('4. Should see:');
        $this->info('   - Button spinner animation');
        $this->info('   - Text change to "Signing Out..."');
        $this->info('   - Global loading overlay');
        $this->info('   - Smooth redirect to login page');
        
        return 0;
    }
}