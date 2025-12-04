<?php

namespace App\Console\Commands;

use App\Models\Core\BusinessUnit;
use App\Models\User;
use Illuminate\Console\Command;

class CheckSuperAdminDetails extends Command
{
    protected $signature = 'check:super-admin {user_id=3}';

    protected $description = 'Check super admin details and business unit assignments';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);

        if (! $user) {
            $this->error("User {$userId} not found");

            return;
        }

        $this->info('=== SUPER ADMIN DETAILS ===');
        $this->info("User: {$user->name} (ID: {$user->id})");
        $this->info("Email: {$user->email}");
        $this->info("Global Role: {$user->global_role}");

        $this->info("\n=== PRIMARY DEPARTMENT ===");
        if ($user->primaryDepartment) {
            $dept = $user->primaryDepartment;
            $bu = $dept->businessUnit;
            $this->info("Department: {$dept->name} ({$dept->code})");
            $this->info("Business Unit: {$bu->name} ({$bu->code})");
            $this->info("BU ID: {$bu->id}");
        } else {
            $this->error('No primary department assigned');
        }

        $this->info("\n=== SESSION CONTEXT ===");
        $this->info('Session current_business_unit_id: '.session('current_business_unit_id', 'null'));
        $this->info('Session current_business_unit_code: '.session('current_business_unit_code', 'null'));
        $this->info('Session current_business_unit_name: '.session('current_business_unit_name', 'null'));

        $this->info("\n=== BUSINESS UNIT ASSIGNMENTS ===");
        $assignments = $user->businessUnits()->with('businessUnit')->get();
        if ($assignments->isEmpty()) {
            $this->error('No business unit assignments found');
        } else {
            foreach ($assignments as $assignment) {
                $bu = $assignment->businessUnit;
                $this->info("- {$bu->name} ({$bu->code}) - Role: {$assignment->role} - Active: ".($assignment->is_active ? 'Yes' : 'No'));
            }
        }

        $this->info("\n=== MIDDLEWARE LOGIC CHECK ===");
        // Check what the middleware would set
        $wns = BusinessUnit::where('code', 'WNS')->first();
        if ($wns) {
            $this->info("WNS Business Unit found: {$wns->name} (ID: {$wns->id})");
            $this->info('Middleware would set WNS as default for super admin');
        } else {
            $this->error('WNS Business Unit not found - middleware would fallback to WG');
        }

        $wg = BusinessUnit::where('code', 'WG')->first();
        if ($wg) {
            $this->info("WG Business Unit found: {$wg->name} (ID: {$wg->id})");
        }

        $this->info("\n=== RECOMMENDATION ===");
        if ($user->primaryDepartment && $user->primaryDepartment->businessUnit->code === 'WG') {
            $this->info('✓ Super Admin correctly belongs to WG (Werkudara Group)');
            $this->info('✓ For PR generation, system should use WG context, not force WNS');
        }
    }
}
