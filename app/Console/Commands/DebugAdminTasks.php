<?php

namespace App\Console\Commands;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use Illuminate\Console\Command;

class DebugAdminTasks extends Command
{
    protected $signature = 'debug:admin-tasks {user_id?}';
    protected $description = 'Debug admin tasks for a specific user';

    public function handle()
    {
        $userId = $this->argument('user_id') ?? 9; // Default to Muhammad Anwar
        
        $user = User::find($userId);
        if (!$user) {
            $this->error("User not found!");
            return 1;
        }

        $this->info("=== Debugging Admin Tasks for {$user->name} ===\n");

        // Check user's business units
        $this->info("Business Units:");
        foreach ($user->activeBusinessUnits as $ubu) {
            $this->line("- {$ubu->businessUnit->name} (ID: {$ubu->business_unit_id})");
            $this->line("  Department: {$ubu->department->name} (ID: {$ubu->department_id})");
            $this->line("  Is Purchasing Admin: " . ($ubu->is_purchasing_admin ? 'YES' : 'NO'));
            $this->line("  Dept is Purchasing: " . ($ubu->department->is_purchasing_department ? 'YES' : 'NO'));
        }

        $this->newLine();

        // Check tasks for each business unit
        foreach ($user->activeBusinessUnits as $ubu) {
            $buId = $ubu->business_unit_id;
            $buName = $ubu->businessUnit->name;

            $this->info("Tasks in {$buName}:");

            // Pending tasks
            $pendingTasks = AdminTask::where('business_unit_id', $buId)
                ->where('status', 'pending_followup')
                ->where(function ($q) use ($userId) {
                    $q->whereNull('assigned_admin_id')
                        ->orWhere('assigned_admin_id', $userId);
                })
                ->get();

            $this->line("  Pending: {$pendingTasks->count()}");
            foreach ($pendingTasks as $task) {
                $taskNumber = $task->taskable->pr_number ?? $task->taskable->sr_number ?? 'N/A';
                $this->line("    - Task #{$task->id}: {$taskNumber}");
                $this->line("      Department: {$task->department->name}");
                $this->line("      Assigned: " . ($task->assigned_admin_id ? "User #{$task->assigned_admin_id}" : 'Unassigned'));
            }

            // In Progress tasks
            $inProgressTasks = AdminTask::where('business_unit_id', $buId)
                ->where('status', 'in_progress')
                ->where('assigned_admin_id', $userId)
                ->count();

            $this->line("  In Progress: {$inProgressTasks}");

            // Completed tasks
            $completedTasks = AdminTask::where('business_unit_id', $buId)
                ->where('status', 'done')
                ->where('assigned_admin_id', $userId)
                ->count();

            $this->line("  Completed: {$completedTasks}");

            $this->newLine();
        }

        return 0;
    }
}
