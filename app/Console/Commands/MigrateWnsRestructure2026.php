<?php

namespace App\Console\Commands;

use App\Services\Core\DepartmentRestructureService;
use App\Services\Core\Restructure\WnsRestructure2026Mapping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Apply the WNS Restructure 2026 user moves.
 *
 * Usage:
 *   php artisan wns:migrate-restructure-2026 --dry-run
 *   php artisan wns:migrate-restructure-2026 --execute
 *
 * The mapping lives in WnsRestructure2026Mapping. Move logic lives in
 * DepartmentRestructureService. This command only orchestrates and reports.
 *
 * Idempotent: re-running on already-migrated users reports 'already_migrated'
 * without DB writes.
 */
class MigrateWnsRestructure2026 extends Command
{
    protected $signature = 'wns:migrate-restructure-2026
                            {--dry-run : Print actions without writing to DB}
                            {--execute : Apply changes to DB}';

    protected $description = 'Move WNS users to the new department/position structure (PRD 2026-05-25).';

    public function handle(DepartmentRestructureService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $execute = (bool) $this->option('execute');

        if ($dryRun === $execute) {
            $this->error('Pass exactly one of --dry-run or --execute.');

            return self::INVALID;
        }

        $mapping = WnsRestructure2026Mapping::moves();
        $this->info(sprintf('Processing %d move entries (%s)...', count($mapping), $dryRun ? 'dry-run' : 'execute'));

        $counts = ['moved' => 0, 'would_move' => 0, 'already_migrated' => 0, 'skipped' => 0];

        DB::beginTransaction();
        try {
            foreach ($mapping as $entry) {
                $result = $service->moveUser(
                    email: $entry['email'],
                    businessUnitCode: $entry['bu'],
                    newDepartmentCode: $entry['dept'],
                    newPositionCode: $entry['position'],
                    dryRun: $dryRun,
                );

                $this->reportResult($entry['email'], $result, $counts);
            }

            if ($dryRun) {
                DB::rollBack();
                $this->info('Dry-run complete. No changes committed.');
            } else {
                DB::commit();
                $this->info('Restructure committed.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Aborted: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->table(
            ['moved', 'would_move', 'already_migrated', 'skipped'],
            [[$counts['moved'], $counts['would_move'], $counts['already_migrated'], $counts['skipped']]],
        );

        return self::SUCCESS;
    }

    /**
     * @param  array{status: string, message: string}  $result
     * @param  array<string, int>  $counts
     */
    protected function reportResult(string $email, array $result, array &$counts): void
    {
        switch ($result['status']) {
            case 'moved':
                $this->info("  [moved] {$result['message']}");
                $counts['moved']++;
                break;
            case 'would_move':
                $this->line("  [would] {$result['message']}");
                $counts['would_move']++;
                break;
            case 'already_migrated':
                $this->comment("  [skip] {$result['message']}");
                $counts['already_migrated']++;
                break;
            default:
                $this->warn("  [{$result['status']}] {$result['message']}");
                $counts['skipped']++;
        }
    }
}
