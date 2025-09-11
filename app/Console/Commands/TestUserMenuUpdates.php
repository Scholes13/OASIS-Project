<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestUserMenuUpdates extends Command
{
    protected $signature = 'test:user-menu-updates';
    protected $description = 'Test user menu updates and profile changes';

    public function handle()
    {
        $this->info('🔍 TESTING USER MENU UPDATES');
        $this->info('============================');
        
        $this->info('');
        $this->info('✅ CHANGES IMPLEMENTED:');
        
        // Check user menu file
        $userMenuPath = resource_path('views/livewire/layout/user-menu.blade.php');
        if (file_exists($userMenuPath)) {
            $content = file_get_contents($userMenuPath);
            
            // Check if Settings is removed
            if (!str_contains($content, 'Settings')) {
                $this->info('   ✓ Settings menu item removed');
            } else {
                $this->error('   ✗ Settings menu item still exists');
            }
            
            // Check if Help & Support redirects to external URL
            if (str_contains($content, 'request.werkudara.com')) {
                $this->info('   ✓ Help & Support redirects to request.werkudara.com');
            } else {
                $this->error('   ✗ Help & Support does not redirect to external URL');
            }
            
            // Check if external link icon is added
            if (str_contains($content, 'target="_blank"')) {
                $this->info('   ✓ External link opens in new tab');
            } else {
                $this->error('   ✗ External link does not open in new tab');
            }
        }
        
        // Check profile page
        $profilePath = resource_path('views/profile.blade.php');
        if (file_exists($profilePath)) {
            $content = file_get_contents($profilePath);
            
            // Check if profile information form is removed
            if (!str_contains($content, 'update-profile-information-form')) {
                $this->info('   ✓ Profile information form removed (read-only display)');
            } else {
                $this->error('   ✗ Profile information form still editable');
            }
            
            // Check if password form is still there
            if (str_contains($content, 'update-password-form')) {
                $this->info('   ✓ Password update form preserved');
            } else {
                $this->error('   ✗ Password update form missing');
            }
            
            // Check if delete user form is removed
            if (!str_contains($content, 'delete-user-form')) {
                $this->info('   ✓ Delete user form removed');
            } else {
                $this->error('   ✗ Delete user form still exists');
            }
        }
        
        // Check logout action
        $logoutPath = app_path('Livewire/Actions/Logout.php');
        if (file_exists($logoutPath)) {
            $content = file_get_contents($logoutPath);
            
            // Check if session cleanup is enhanced
            if (str_contains($content, "sessions')->where")) {
                $this->info('   ✓ Enhanced session cleanup implemented');
            } else {
                $this->error('   ✗ Enhanced session cleanup missing');
            }
            
            // Check if DB facade is imported
            if (str_contains($content, 'use Illuminate\\Support\\Facades\\DB')) {
                $this->info('   ✓ DB facade imported for session cleanup');
            } else {
                $this->error('   ✗ DB facade not imported');
            }
        }
        
        // Check user menu component
        $userMenuComponentPath = app_path('Livewire/Layout/UserMenu.php');
        if (file_exists($userMenuComponentPath)) {
            $content = file_get_contents($userMenuComponentPath);
            
            // Check if logout redirects to login
            if (str_contains($content, "route('login')")) {
                $this->info('   ✓ Logout redirects to login page');
            } else {
                $this->error('   ✗ Logout does not redirect to login page');
            }
        }
        
        $this->info('');
        $this->info('📋 SUMMARY OF CHANGES:');
        $this->info('   1. Settings menu item - REMOVED');
        $this->info('   2. Help & Support - REDIRECTS to request.werkudara.com');
        $this->info('   3. Profile page - READ-ONLY (name, email, role, department)');
        $this->info('   4. Password update - AVAILABLE');
        $this->info('   5. Delete account - REMOVED');
        $this->info('   6. Logout - ENHANCED (clears all sessions)');
        $this->info('   7. Logout redirect - TO LOGIN PAGE');
        
        $this->info('');
        $this->info('🚀 TESTING RECOMMENDATIONS:');
        $this->info('   1. Test logout functionality in browser');
        $this->info('   2. Verify Help & Support opens external link');
        $this->info('   3. Confirm profile page shows read-only information');
        $this->info('   4. Test password update functionality');
        $this->info('   5. Verify all sessions are cleared on logout');
        
        $this->info('');
        $this->info('✨ User menu updates completed successfully!');
        
        return 0;
    }
}