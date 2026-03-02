<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\Core\User::where('email', 'anwar@werkudara.com')->first();

echo '=== User Info ==='.PHP_EOL;
echo 'ID: '.$user->id.PHP_EOL;
echo 'Name: '.$user->name.PHP_EOL;
echo 'Email: '.$user->email.PHP_EOL;
echo 'Is Super Admin: '.($user->is_super_admin ? 'true' : 'false').PHP_EOL;
echo 'primary_department_id: '.($user->primary_department_id ?? 'null').PHP_EOL;

echo PHP_EOL.'=== Primary Department ==='.PHP_EOL;
if ($user->primaryDepartment) {
    echo 'ID: '.$user->primaryDepartment->id.PHP_EOL;
    echo 'Name: '.$user->primaryDepartment->name.PHP_EOL;
    echo 'is_purchasing_department: '.($user->primaryDepartment->is_purchasing_department ? 'true' : 'false').PHP_EOL;
    echo 'business_unit_id: '.$user->primaryDepartment->business_unit_id.PHP_EOL;
} else {
    echo 'No department assigned'.PHP_EOL;
}

echo PHP_EOL.'=== User Business Units (from user_business_units table) ==='.PHP_EOL;
$ubUs = \App\Models\Core\UserBusinessUnit::where('user_id', $user->id)->with(['businessUnit', 'department'])->get();
foreach ($ubUs as $ubu) {
    echo '- BU: '.($ubu->businessUnit ? $ubu->businessUnit->name." (ID: {$ubu->business_unit_id})" : "null (ID: {$ubu->business_unit_id})").PHP_EOL;
    echo '  Department: '.($ubu->department ? $ubu->department->name." (ID: {$ubu->department_id})" : "null (ID: {$ubu->department_id})").PHP_EOL;
    echo '  is_purchasing_admin: '.($ubu->is_purchasing_admin ? 'true' : 'false').PHP_EOL;
    echo '  is_active: '.($ubu->is_active ? 'true' : 'false').PHP_EOL;
    if ($ubu->department) {
        echo '  department.is_purchasing_department: '.($ubu->department->is_purchasing_department ? 'true' : 'false').PHP_EOL;
    }
}

echo PHP_EOL.'=== Gate Check ==='.PHP_EOL;
// Simulate session
session(['current_business_unit_id' => 2]); // WNS
\Illuminate\Support\Facades\Auth::login($user);
echo 'Can access-purchasing-admin (BU ID 2/WNS): '.($user->can('access-purchasing-admin') ? 'true' : 'false').PHP_EOL;
