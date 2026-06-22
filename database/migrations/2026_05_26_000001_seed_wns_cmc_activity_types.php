<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed WNS / CMC department activity types + sub-activities as part of
 * `php artisan migrate`.
 *
 * Context: WNS Restructure 2026 follow-up. The CMC (Corporate Marketing
 * Communication) sub-department was created by the structure seeders but had
 * no activity types assigned. This DATA migration invokes the idempotent
 * WNSCmcActivityTypeSeeder so production picks up the CMC activity catalog
 * with a single migrate command.
 *
 * Prerequisite: 2026_05_26_000000_apply_wns_restructure_2026_data (creates the
 * WNS/CMC department). Runs earlier by timestamp; guarded below.
 *
 * Idempotent: the seeder uses updateOrCreate / updateOrInsert. Re-running is a
 * safe no-op.
 *
 * Safety:
 * - Skips on databases without the WNS/CMC department (e.g. CI/RefreshDatabase).
 * - Wrapped in a transaction.
 * - down() is a no-op: removing seeded activity types could orphan historical
 *   tasks that already reference them. Restore from backup to reverse.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guard: WNS/CMC department must exist (created by the restructure migration).
        if (! Schema::hasColumn('departments', 'parent_department_id')) {
            return;
        }

        $cmcExists = DB::table('departments')
            ->join('business_units', 'departments.business_unit_id', '=', 'business_units.id')
            ->where('business_units.code', 'WNS')
            ->where('departments.code', 'CMC')
            ->exists();

        if (! $cmcExists) {
            return;
        }

        DB::transaction(function () {
            Artisan::call('db:seed', [
                '--class' => \Database\Seeders\WNS\WNSCmcActivityTypeSeeder::class,
                '--force' => true,
            ]);

            $output = trim(Artisan::output());
            if ($output !== '') {
                echo PHP_EOL.'  [WNSCmcActivityTypeSeeder]'.PHP_EOL.$output.PHP_EOL;
            }
        });
    }

    public function down(): void
    {
        // Intentional no-op. Seeded activity types may be referenced by tasks;
        // removing them would orphan history. Restore from backup to reverse.
    }
};
