<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\Core\User::where('email', 'anwar@werkudara.com')->first();

// Clear navigation cache for this user
$cacheKeys = [
    "nav:{$user->id}:2", // WNS business unit
    "bu_list:{$user->id}",
];

foreach ($cacheKeys as $key) {
    \Illuminate\Support\Facades\Cache::forget($key);
    echo "Cleared cache: {$key}".PHP_EOL;
}

echo PHP_EOL.'Done! Please logout and login again, or refresh the page.'.PHP_EOL;
