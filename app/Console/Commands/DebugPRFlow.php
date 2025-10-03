<?php

namespace App\Console\Commands;

use App\Models\Modules\Wns\PurchaseRequest;
use App\Models\NumberSequence;
use App\Models\User;
use Illuminate\Console\Command;

class DebugPRFlow extends Command
{
    protected $signature = 'debug:pr-flow';

    protected $description = 'Debug PR creation flow';

    public function handle()
    {
        $this->info('=== PR Flow Debug ===');

        // Check number sequences
        $sequences = NumberSequence::latest()->take(5)->get();
        $this->info('Latest 5 Number Sequences:');
        foreach ($sequences as $seq) {
            $formatted = $seq->formatted_number ?? 'NULL';
            $createdBy = $seq->created_by ?? 'NULL';
            $this->line("- ID: {$seq->id} | Formatted: {$formatted} | BU: {$seq->business_unit_id} | User: {$createdBy} | Created: {$seq->created_at}");

            // Show all attributes for debugging
            $this->line('  Attributes: '.json_encode($seq->getAttributes()));
        }

        // Check purchase requests
        $prs = PurchaseRequest::latest()->take(5)->get();
        $this->info("\nLatest 5 Purchase Requests:");
        if ($prs->count() > 0) {
            foreach ($prs as $pr) {
                $this->line("- {$pr->pr_number} | User: {$pr->user_id} | Status: {$pr->status} | Created: {$pr->created_at}");
            }
        } else {
            $this->warn('No Purchase Requests found!');
        }

        // Check users
        $users = User::where('is_active', true)->get();
        $this->info("\nActive Users:");
        foreach ($users as $user) {
            $accessLevel = $user->getAccessLevel();
            $this->line("- {$user->name} (ID: {$user->id}) | Role: {$user->global_role} | Access: {$accessLevel}");
        }

        return 0;
    }
}
