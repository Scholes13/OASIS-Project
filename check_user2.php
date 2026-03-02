<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check all departments named Strategic Sourcing
echo '=== All Strategic Sourcing Departments ==='.PHP_EOL;
$depts = \App\Models\Core\Department::where('name', 'like', '%Strategic Sourcing%')->with('businessUnit')->get();
foreach ($depts as $dept) {
    echo "ID: {$dept->id}, Name: {$dept->name}, BU: ".($dept->businessUnit ? $dept->businessUnit->name." ({$dept->businessUnit->code})" : 'null').', is_purchasing: '.($dept->is_purchasing ? 'true' : 'false').PHP_EOL;
}

// Check business units
echo PHP_EOL.'=== Business Units ==='.PHP_EOL;
$bus = \App\Models\Core\BusinessUnit::whereIn('id', [2, 147])->get();
foreach ($bus as $bu) {
    echo "ID: {$bu->id}, Name: {$bu->name}, Code: {$bu->code}".PHP_EOL;
}

// Check WNS business unit
echo PHP_EOL.'=== WNS Business Unit ==='.PHP_EOL;
$wns = \App\Models\Core\BusinessUnit::where('code', 'WNS')->first();
if ($wns) {
    echo "ID: {$wns->id}, Name: {$wns->name}, Code: {$wns->code}".PHP_EOL;
}
