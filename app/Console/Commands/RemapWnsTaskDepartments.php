<?php

namespace App\Console\Commands;

use App\Services\Core\Restructure\TaskDepartmentRemapService;
use App\Services\Core\Restructure\WnsRestructure2026Mapping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Remap historical EmployeeTask department_id (and business_unit_id) for users
 * moved by the WNS Restructure 2026 plan.
 *
 * Usage:
 *   php artisan wns:remap-task-departments --dry-run
 *   php artisan wns:remap-task-departments --execute
 *
 * Reuses the same mapping as wns:migrate-restructure-2026.
 */
class RemapWnsTaskDepartments extends Command
{
    protected $signature = 'wns:remap-task-departments
                            {--dry-run : Print actions without writing to DB}
                            {--execute : Apply changes to DB}';

    protected $description = 'Remap historical task department_id to match each moved user\'s new department (PRD 2026-05-25 follow-up).';

    public function handle(TaskDepartmentRemapService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $execute = (bool) $this->option('execute');

        if ($dryRun === $execute) {
            $this->error('Pass exactly one of --dry-run or --execute.');

            return self::INVALID;
        }

        $mapping = WnsRestructure2026Mapping::moves();
        $this->info(sprintf('Processing %d move entries (%s)...', count($mapping), $dryRun ? 'dry-run' : 'execute'));

        $totals = ['updated' => 0, 'would_update' => 0, 'no_change' => 0, 'skipped' => 0];

        DB::beginTransaction();
        try {
            foreach ($mapping as $entry) {
                $result = $service->remapForUser(
                    email: $entry['email'],
                    businessUnitCode: $entry['bu'],
                    newDepartmentCode: $entry['dept'],
                    dryRun: $dryRun,
                );

                $this->reportResult($result, $totals);
            }

            if ($dryRun) {
                DB::rollBack();
                $this->info('Dry-run complete. No changes committed.');
            } else {
                DB::commit();
                $this->info('Task remap committed.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Aborted: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->table(
            ['updated', 'would_update', 'no_change', 'skipped'],
            [[$totals['updated'], $totals['would_update'], $totals['no_change'], $totals['skipped']]],
        );

        return self::SUCCESS;
    }

    /**
     * @param  array{status: string, message: string, updated: int}  $result
     * @param  array<string, int>  $counts
     */
    protected function reportResult(array $result, array &$counts): void
    {
        switch ($result['status']) {
            case 'updated':
                $this->info("  [updated]      {$result['message']}");
                $counts['updated'] += $result['updated'];
                break;
            case 'would_update':
                $this->line("  [would]        {$result['message']}");
                $counts['would_update'] += $result['updated'];
                break;
            case 'no_change':
                $this->comment("  [no-change]    {$result['message']}");
                $counts['no_change']++;
                break;
            default:
                $this->warn("  [{$result['status']}] {$result['message']}");
                $counts['skipped']++;
        }
    }
}
