<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DebugSubmitIssue extends Command
{
    protected $signature = 'debug:submit-issue';
    protected $description = 'Debug the submit issue with comprehensive checks';

    public function handle()
    {
        $this->info('🔍 Debugging Submit Issue');
        $this->info('========================');
        
        $this->info('✅ Added comprehensive debugging to the form:');
        $this->info('');
        
        $this->info('1. 🧪 Test Button Added');
        $this->info('   - Click the "Test" button first to verify Livewire is working');
        $this->info('   - Should show "Livewire connection is working!" message');
        
        $this->info('');
        $this->info('2. 📝 Session Data Check');
        $this->info('   - Added check for business_unit_id and department_id in session');
        $this->info('   - Will show specific error if session data is missing');
        
        $this->info('');
        $this->info('3. 📊 Enhanced Logging');
        $this->info('   - Logs form data, session data, and custom approvers');
        $this->info('   - Shows "Submit method called" flash message when method starts');
        
        $this->info('');
        $this->info('🔧 Debugging Steps:');
        $this->info('1. Go to /purchase-requests/create');
        $this->info('2. Click "Test" button - should show success message');
        $this->info('3. Fill out the form completely');
        $this->info('4. Click "Submit for Approval"');
        $this->info('5. Check for flash messages and browser console errors');
        
        $this->info('');
        $this->info('📋 Common Issues to Check:');
        $this->info('- Browser console errors (F12 → Console)');
        $this->info('- Network tab for failed requests (F12 → Network)');
        $this->info('- Flash messages at top of page');
        $this->info('- Laravel logs: php artisan pail or storage/logs/laravel.log');
        
        $this->info('');
        $this->info('🚨 If Test button doesn\'t work:');
        $this->info('- Livewire is not properly loaded');
        $this->info('- Check browser console for JavaScript errors');
        $this->info('- Verify Alpine.js and Livewire scripts are loaded');
        
        return 0;
    }
}