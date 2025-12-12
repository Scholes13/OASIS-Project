<?php

namespace App\Console\Commands;

use App\Models\Core\Department;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Notifications\Purchasing\Admin\SlaExceeded;
use App\Services\Modules\Purchasing\Admin\SlaMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSlaViolations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sla:check-violations';

    /**
     * The console command description.
     */
    protected $description = 'Check for SLA violations and send email alerts';

    /**
     * Execute the console command.
     */
    public function handle(SlaMonitoringService $slaService): int
    {
        $this->info('Checking for SLA violations...');

        // Track violations found
        $followupViolations = 0;
        $completionViolations = 0;

        // Check follow-up SLA violations
        $followupTasks = $slaService->getTasksExceedingFollowupSla();

        foreach ($followupTasks as $task) {
            if (! $slaService->areEmailAlertsEnabled($task->business_unit_id)) {
                continue;
            }

            $this->sendSlaAlert($task, 'followup');
            $followupViolations++;
        }

        // Check completion SLA violations
        $completionTasks = $slaService->getTasksExceedingCompletionSla();

        foreach ($completionTasks as $task) {
            if (! $slaService->areEmailAlertsEnabled($task->business_unit_id)) {
                continue;
            }

            $this->sendSlaAlert($task, 'completion');
            $completionViolations++;
        }

        $this->info("Follow-up SLA violations: {$followupViolations}");
        $this->info("Completion SLA violations: {$completionViolations}");
        $this->info('SLA check completed.');

        Log::info('SLA violations check completed', [
            'followup_violations' => $followupViolations,
            'completion_violations' => $completionViolations,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Send SLA alert notification
     */
    protected function sendSlaAlert(AdminTask $task, string $violationType): void
    {
        try {
            // Send to assigned admin if exists
            if ($task->assignedAdmin) {
                $task->assignedAdmin->notify(new SlaExceeded($task, $violationType));
            }

            // Send to department manager(s)
            $this->notifyDepartmentManagers($task, $violationType);

            $this->line("  → Alert sent for task #{$task->id} ({$violationType} SLA exceeded)");
        } catch (\Exception $e) {
            $this->error("  → Failed to send alert for task #{$task->id}: {$e->getMessage()}");
            Log::error('Failed to send SLA alert', [
                'task_id' => $task->id,
                'violation_type' => $violationType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify department managers about SLA violation
     */
    protected function notifyDepartmentManagers(AdminTask $task, string $violationType): void
    {
        // Get department managers (users with management positions in the department)
        $managers = $task->department->userBusinessUnits()
            ->whereHas('position', function ($query) {
                $query->whereIn('access_level', [1, 2, 3]); // GM, Director, CEO, Finance Manager, Dept Head
            })
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id');

        foreach ($managers as $manager) {
            $manager->notify(new SlaExceeded($task, $violationType));
        }
    }
}
