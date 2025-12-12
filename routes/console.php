<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule SLA violation checks every hour
Schedule::command('sla:check-violations')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();
