<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed WNS / SM (root Sales & Marketing) activity types as part of
 * `php artisan migrate`.
 *
 * Context: WNS Restructure 2026 follow-up. SM is the root department over the
 * BS/COM/CMC sub-divisions. Per PO, its activity catalog is the UNION of those
 * three sub-divisions, deduplicated by purpose. This DATA migration invokes
 * the idempotent WNSSmActivityTypeSeeder.
 *
 * Prerequisites (must run earlier by timestamp):
 *   - 2026_05_26_000000_apply_wns_restructure_2026_data (creates WNS/SM dept)
 *   - 2026_05_26_000001_seed_wns_cmc_activity_types (CMC source types)
 *   - 2026_05_26_000002_seed_wns_com_activity_types (COM source types)
 *   - 2026_05_26_000003_seed_wns_bs_activity_types  (BS source types)
 * The SM seeder reads the live BS_/COM_/CMC_ types, so they must exist first.
 *
 * Idempotent: updateOrCreate / updateOrInsert. Re-running is a safe no-op.
 *
 * Safety:
 * - Skips on databases without the WNS/SM department (e.g. CI/RefreshDatabase).
 * - Wrapped in a transaction.
 * - down() is a no-op: seeded types may be referenced by tasks. Restore from
 *   backup to reverse.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('departments', 'parent_department_id')) {
            return;
        }

        $smExists = DB::table('departments')
            ->join('business_units', 'departments.business_unit_id', '=', 'business_units.id')
            ->where('business_units.code', 'WNS')
            ->where('departments.code', 'SM')
            ->exists();

        if (! $smExists) {
            return;
        }

        DB::transaction(function () {
            Artisan::call('db:seed', [
                '--class' => \Database\Seeders\WNS\WNSSmActivityTypeSeeder::class,
                '--force' => true,
            ]);

            $output = trim(Artisan::output());
            if ($output !== '') {
                echo PHP_EOL.'  [WNSSmActivityTypeSeeder]'.PHP_EOL.$output.PHP_EOL;
            }
        });
    }

    public function down(): void
    {
        // Intentional no-op. Seeded activity types may be referenced by tasks;
        // removing them would orphan history. Restore from backup to reverse.
    }
};
