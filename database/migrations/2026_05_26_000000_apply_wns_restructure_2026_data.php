<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Apply WNS Restructure 2026 DATA as part of `php artisan migrate`.
 *
 * Context: PRD docs/specs/2026-05-25-wns-restructure-prd.
 *
 * This is a DATA migration (not schema). It is a thin orchestrator that
 * invokes the already-tested, idempotent seeders + console commands in
 * dependency order, inside a single transaction. No business logic is
 * duplicated here — every step delegates to the canonical implementation.
 *
 * Prerequisite schema migration:
 *   2026_05_25_100000_add_parent_department_id_to_departments_table
 * (runs earlier by timestamp; guarded below).
 *
 * Steps (all idempotent — re-running is a safe no-op):
 *   1. WGExecutiveOfficeSeeder        WG/EXEC + CEO_EXEC, MD_EXEC
 *   2. WNSExecutiveOfficeSeeder       WNS/EXEC + COS_EXEC (Chief of Staff)
 *   3. WNSSalesMarketingSeeder        WNS/SM root + BS/COM/CMC sub-divisions
 *   4. WNSSalesMarketingPositionSeeder  custom positions for the SM tree + COORD_SO
 *   5. WNSEtikUserSeeder              new user andri@ (Etik) -> WNS/SM/GM_SM
 *   6. WNSKayanaUserSeeder            new user abhi@ (Kayana) -> WNS/CMC/ANL_CMC
 *   7. wns:migrate-restructure-2026   move 23 existing users to new dept/position
 *   8. wns:remap-task-departments     reattach historical tasks to new departments
 *
 * Safety:
 *   - Guarded so it skips cleanly on un-seeded databases (e.g. CI with
 *     RefreshDatabase) where the WNS business unit / parent column do not exist.
 *   - Wrapped in a DB transaction so a mid-step failure rolls everything back.
 *   - down() is intentionally a no-op: reversing user moves + task remaps would
 *     require a prior-state snapshot that we do not capture. The schema column
 *     is dropped by its own migration's down().
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guard 1: schema prerequisite must exist (parent-child department FK).
        if (! Schema::hasColumn('departments', 'parent_department_id')) {
            return;
        }

        // Guard 2: base data must be seeded (skip on empty/test databases).
        $wnsExists = DB::table('business_units')->where('code', 'WNS')->exists();
        if (! $wnsExists) {
            return;
        }

        DB::transaction(function () {
            // 1-4: structure (departments + positions). updateOrCreate / firstOrCreate.
            $this->seed(\Database\Seeders\WG\WGExecutiveOfficeSeeder::class);
            $this->seed(\Database\Seeders\WNS\WNSExecutiveOfficeSeeder::class);
            $this->seed(\Database\Seeders\WNS\WNSSalesMarketingSeeder::class);
            $this->seed(\Database\Seeders\WNS\WNSSalesMarketingPositionSeeder::class);

            // 5-6: new users (Etik + Kayana). firstOrCreate keyed on email.
            $this->seed(\Database\Seeders\WNS\WNSEtikUserSeeder::class);
            $this->seed(\Database\Seeders\WNS\WNSKayanaUserSeeder::class);

            // 7: move existing users to new dept/position (idempotent: reports
            //    already_migrated and writes nothing when a user is already there).
            Artisan::call('wns:migrate-restructure-2026', ['--execute' => true]);
            $this->echoOutput('wns:migrate-restructure-2026');

            // 8: remap historical task department_id for moved users.
            Artisan::call('wns:remap-task-departments', ['--execute' => true]);
            $this->echoOutput('wns:remap-task-departments');
        });
    }

    public function down(): void
    {
        // Intentional no-op. This is a data migration; reversing user moves and
        // task remaps requires a prior-state snapshot we do not keep. To re-test
        // locally, restore the database backup and run `php artisan migrate` again.
    }

    /**
     * Run a seeder class through Artisan so it gets a proper command context
     * (the seeders call $this->command->info()).
     */
    private function seed(string $class): void
    {
        Artisan::call('db:seed', [
            '--class' => $class,
            '--force' => true,
        ]);
    }

    /**
     * Surface a console command's captured output during `php artisan migrate`
     * so operators can see what happened on the server.
     */
    private function echoOutput(string $label): void
    {
        $output = trim(Artisan::output());
        if ($output !== '') {
            echo PHP_EOL."  [{$label}]".PHP_EOL.$output.PHP_EOL;
        }
    }
};
