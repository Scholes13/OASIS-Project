<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed WNS / COM department activity types + sub-activities as part of
 * `php artisan migrate`.
 *
 * Context: WNS Restructure 2026 follow-up. The COM (Commercial Division)
 * sub-department was created by the structure seeders but had no activity
 * types assigned. This DATA migration invokes the idempotent
 * WNSComActivityTypeSeeder so production picks up the COM activity catalog
 * with a single migrate command.
 *
 * Prerequisite: 2026_05_26_000000_apply_wns_restructure_2026_data (creates the
 * WNS/COM department). Runs earlier by timestamp; guarded below.
 *
 * Idempotent: the seeder uses updateOrCreate / updateOrInsert. Re-running is a
 * safe no-op.
 *
 * Safety:
 * - Skips on databases without the WNS/COM department (e.g. CI/RefreshDatabase).
 * - Wrapped in a transaction.
 * - down() is a no-op: removing seeded activity types could orphan historical
 *   tasks that already reference them. Restore from backup to reverse.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('departments', 'parent_department_id')) {
            return;
        }

        $comExists = DB::table('departments')
            ->join('business_units', 'departments.business_unit_id', '=', 'business_units.id')
            ->where('business_units.code', 'WNS')
            ->where('departments.code', 'COM')
            ->exists();

        if (! $comExists) {
            return;
        }

        DB::transaction(function () {
            Artisan::call('db:seed', [
                '--class' => \Database\Seeders\WNS\WNSComActivityTypeSeeder::class,
                '--force' => true,
            ]);

            $output = trim(Artisan::output());
            if ($output !== '') {
                echo PHP_EOL.'  [WNSComActivityTypeSeeder]'.PHP_EOL.$output.PHP_EOL;
            }
        });
    }

    public function down(): void
    {
        // Intentional no-op. Seeded activity types may be referenced by tasks;
        // removing them would orphan history. Restore from backup to reverse.
    }
};
