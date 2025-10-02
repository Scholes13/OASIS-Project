<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestSubmitIssue extends Command
{
    protected $signature = 'test:submit-issue';

    protected $description = 'Test the submit issue and check logs';

    public function handle()
    {
        $this->info('Testing Submit Issue');
        $this->info('==================');

        $this->info('Added debug logging to the submitPurchaseRequest method.');
        $this->info('');

        $this->info('When you try to submit the form, check the Laravel logs for:');
        $this->info('1. "Submit PR attempt" - Shows the form data being submitted');
        $this->info('2. "Custom approval validation" - Shows the custom approvers data');
        $this->info('3. "Submit PR failed" - Shows any errors that occur');

        $this->info('');
        $this->info('To check logs, run:');
        $this->info('php artisan pail');
        $this->info('or check storage/logs/laravel.log');

        $this->info('');
        $this->info('Common issues that could cause this:');
        $this->info('- Validation errors not being displayed');
        $this->info('- Custom approvers array format issue');
        $this->info('- Session data missing (business_unit_id, department_id)');
        $this->info('- Database constraint violations');
        $this->info('- Missing required services or models');

        $this->info('');
        $this->info('Try submitting the form again and check the logs for the exact error.');

        return 0;
    }
}
