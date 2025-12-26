<?php

namespace App\Console\Commands;

use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use Illuminate\Console\Command;

class BackfillAdminTasks extends Command
{
    protected $signature = 'admin:backfill-tasks';
    protected $description = 'Create admin tasks for approved PRs that don\'t have tasks yet';

    public function handle(): int
    {
        $this->info('=== Backfilling Admin Tasks ===');

        $approvedPRs = PurchaseRequest::where('status', 'approved')
            ->whereNotExists(function ($q) {
                $q->select(\DB::raw(1))
                    ->from('admin_tasks')
                    ->whereColumn('admin_tasks.taskable_id', 'purchase_requests.id')
                    ->where('admin_tasks.taskable_type', PurchaseRequest::class);
            })
            ->get();

        $count = $approvedPRs->count();
        $this->info("Found {$count} approved PRs without admin tasks");

        if ($count === 0) {
            $this->info('Nothing to do.');
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($count);
        $created = 0;

        foreach ($approvedPRs as $pr) {
            $lastApproval = $pr->approvals()->where('status', 'approved')->orderBy('responded_at', 'desc')->first();
            $enteredAt = $lastApproval?->responded_at ?? $pr->updated_at;

            AdminTask::create([
                'taskable_type' => PurchaseRequest::class,
                'taskable_id' => $pr->id,
                'business_unit_id' => $pr->business_unit_id,
                'department_id' => $pr->department_id,
                'assigned_admin_id' => null,
                'status' => 'pending_followup',
                'entered_at' => $enteredAt,
                'estimated_total_price' => $pr->total_amount ?? 0,
            ]);

            $created++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Created {$created} admin tasks.");
        $this->info('Total admin_tasks: ' . AdminTask::count());

        return Command::SUCCESS;
    }
}
