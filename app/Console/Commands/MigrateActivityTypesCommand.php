<?php

namespace App\Console\Commands;

use App\Services\Modules\Activity\ActivityTypeMigrationService;
use Illuminate\Console\Command;

/**
 * Artisan command to migrate Activity Types from per-department architecture
 * to global master architecture.
 *
 * This command consolidates prefixed activity types (ACC_LEAVE, BAS_LEAVE)
 * into global master records (LEAVE), updates task references, and preserves
 * department assignments.
 *
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5
 */
class MigrateActivityTypesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:activity-types
                            {--dry-run : Preview changes without committing}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Activity Types from per-department to global master architecture';

    /**
     * Execute the console command.
     */
    public function handle(ActivityTypeMigrationService $migrationService): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║         Activity Types Migration Tool                        ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be committed');
            $this->info('');
        }

        // Step 1: Show preview
        $this->info('📊 Analyzing current data...');
        $this->info('');

        $preview = $migrationService->preview();

        $this->displayPreview($preview);

        // Check if there's anything to migrate
        if (empty($preview['activity_types']) && empty($preview['sub_activities'])) {
            $this->info('');
            $this->info('✅ No duplicate activity types found. Migration not needed.');

            return Command::SUCCESS;
        }

        // Step 2: Confirm unless --force or --dry-run
        if (! $dryRun && ! $force) {
            $this->info('');
            $this->warn('⚠️  This operation will:');
            $this->line('   • Consolidate duplicate activity types into master records');
            $this->line('   • Update all task references to new master IDs');
            $this->line('   • Preserve department assignments');
            $this->line('   • Remove orphaned prefixed records');
            $this->info('');

            if (! $this->confirm('Do you want to proceed with the migration?')) {
                $this->info('Migration cancelled.');

                return Command::SUCCESS;
            }
        }

        // Step 3: Run migration
        $this->info('');
        $this->info('🚀 Starting migration...');
        $this->info('');

        $progressBar = $this->output->createProgressBar(4);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

        try {
            $progressBar->setMessage('Consolidating activity types...');
            $progressBar->start();

            // Run the migration
            $results = $migrationService->migrate($dryRun);

            $progressBar->setMessage('Consolidating sub activities...');
            $progressBar->advance();

            $progressBar->setMessage('Updating task references...');
            $progressBar->advance();

            $progressBar->setMessage('Cleaning up orphans...');
            $progressBar->advance();

            $progressBar->setMessage('Complete!');
            $progressBar->finish();

            $this->info('');
            $this->info('');

            // Step 4: Display results
            $this->displayResults($results, $dryRun);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $progressBar->finish();
            $this->info('');
            $this->info('');
            $this->error('❌ Migration failed: '.$e->getMessage());
            $this->info('');
            $this->line('Check the logs for more details.');

            return Command::FAILURE;
        }
    }

    /**
     * Display the migration preview.
     */
    protected function displayPreview(array $preview): void
    {
        // Activity Types Preview
        if (! empty($preview['activity_types'])) {
            $this->info('📋 Activity Types to Consolidate:');
            $this->info('');

            $tableData = [];
            foreach ($preview['activity_types'] as $item) {
                $tableData[] = [
                    $item['name'],
                    implode(', ', array_slice($item['current_codes'], 0, 3)).
                        (count($item['current_codes']) > 3 ? '...' : ''),
                    $item['new_code'],
                    $item['count'],
                ];
            }

            $this->table(
                ['Name', 'Current Codes', 'New Code', 'Duplicates'],
                $tableData
            );
        } else {
            $this->info('📋 Activity Types: No duplicates found');
        }

        $this->info('');

        // Sub Activities Preview
        if (! empty($preview['sub_activities'])) {
            $this->info('📋 Sub Activities to Consolidate:');
            $this->info('');

            $tableData = [];
            foreach ($preview['sub_activities'] as $item) {
                $tableData[] = [
                    $item['activity_type'],
                    $item['name'],
                    implode(', ', array_slice($item['current_codes'], 0, 2)).
                        (count($item['current_codes']) > 2 ? '...' : ''),
                    $item['count'],
                ];
            }

            $this->table(
                ['Activity Type', 'Sub Activity', 'Current Codes', 'Duplicates'],
                $tableData
            );
        } else {
            $this->info('📋 Sub Activities: No duplicates found');
        }

        $this->info('');

        // Summary
        $this->info('📊 Impact Summary:');
        $this->line('   • Activity types to consolidate: '.count($preview['activity_types']));
        $this->line('   • Sub activities to consolidate: '.count($preview['sub_activities']));
        $this->line('   • Tasks that will be updated: '.$preview['affected_tasks']);
        $this->line('   • Department assignments affected: '.$preview['affected_departments']);
    }

    /**
     * Display the migration results.
     */
    protected function displayResults(array $results, bool $dryRun): void
    {
        $status = $dryRun ? '(DRY RUN)' : '';

        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                    Migration Results '.str_pad($status, 23).'║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        // Activity Types Results
        $this->info('📦 Activity Types:');
        $this->line('   • Created (new master records): '.$results['activity_types']['created']);
        $this->line('   • Consolidated (merged into master): '.$results['activity_types']['consolidated']);
        $this->line('   • Skipped (already master): '.$results['activity_types']['skipped']);

        if (! empty($results['activity_types']['errors'])) {
            $this->warn('   • Errors: '.count($results['activity_types']['errors']));
            foreach ($results['activity_types']['errors'] as $error) {
                $this->line('     - '.($error['name'] ?? 'Unknown').': '.$error['error']);
            }
        }

        $this->info('');

        // Sub Activities Results
        $this->info('📦 Sub Activities:');
        $this->line('   • Created (new master records): '.$results['sub_activities']['created']);
        $this->line('   • Consolidated (merged into master): '.$results['sub_activities']['consolidated']);
        $this->line('   • Skipped (already master): '.$results['sub_activities']['skipped']);

        if (! empty($results['sub_activities']['errors'])) {
            $this->warn('   • Errors: '.count($results['sub_activities']['errors']));
            foreach ($results['sub_activities']['errors'] as $error) {
                $this->line('     - '.($error['name'] ?? $error['key'] ?? 'Unknown').': '.$error['error']);
            }
        }

        $this->info('');

        // Other Results
        $this->info('📊 Other Operations:');
        $this->line('   • Tasks updated: '.$results['tasks_updated']);
        $this->line('   • Department assignments preserved: '.$results['department_assignments']);
        $this->line('   • Orphaned records removed: '.$results['orphans_removed']);

        $this->info('');

        if ($dryRun) {
            $this->warn('🔍 This was a dry run. No changes were committed.');
            $this->info('   Run without --dry-run to apply changes.');
        } else {
            $this->info('✅ Migration completed successfully!');
        }

        $this->info('');
    }
}
