<?php

namespace App\Console\Commands;

use App\Models\Modules\Wns\PrApproval;
use App\Models\User;
use App\Services\Modules\Wns\ApprovalWorkflowService;
use Illuminate\Console\Command;

class DebugApprovalHistory extends Command
{
    protected $signature = 'debug:approval-history {user_id?}';

    protected $description = 'Debug approval history functionality';

    public function handle()
    {
        $this->info('🔍 DEBUGGING APPROVAL HISTORY FUNCTIONALITY');
        $this->line('═══════════════════════════════════════════════════════');

        $userId = $this->argument('user_id');

        if ($userId) {
            $user = User::find($userId);
            if (! $user) {
                $this->error("User with ID {$userId} not found");

                return 1;
            }
        } else {
            // Get first user with approvals
            $user = User::whereHas('approvals')->first();
            if (! $user) {
                $this->error('No users with approvals found');

                return 1;
            }
        }

        $this->info("📋 Analyzing approvals for user: {$user->name} (ID: {$user->id})");
        $this->newLine();

        // Get all approvals for this user
        $allApprovals = PrApproval::where('approver_id', $user->id)->get();
        $pendingApprovals = $allApprovals->where('status', 'pending');
        $completedApprovals = $allApprovals->whereIn('status', ['approved', 'rejected']);

        $this->info('📊 Approval Statistics:');
        $this->line("   Total Approvals: {$allApprovals->count()}");
        $this->line("   Pending: {$pendingApprovals->count()}");
        $this->line("   Completed: {$completedApprovals->count()}");
        $this->line("   Approved: {$allApprovals->where('status', 'approved')->count()}");
        $this->line("   Rejected: {$allApprovals->where('status', 'rejected')->count()}");

        $this->newLine();

        // Test ApprovalWorkflowService methods
        $workflowService = new ApprovalWorkflowService;

        $this->info('🔧 Testing ApprovalWorkflowService methods:');

        // Test getPendingApprovalsForUser
        $pendingFromService = $workflowService->getPendingApprovalsForUser($user);
        $this->line("   getPendingApprovalsForUser(): {$pendingFromService->count()} items");

        // Test getApprovalHistoryForUser
        $historyFromService = $workflowService->getApprovalHistoryForUser($user);
        $this->line("   getApprovalHistoryForUser(): {$historyFromService->count()} items");

        // Test getApprovalStatistics
        $statsFromService = $workflowService->getApprovalStatistics($user);
        $this->line('   getApprovalStatistics():');
        $this->line("     - Total Assigned: {$statsFromService['total_assigned']}");
        $this->line("     - Total Approved: {$statsFromService['total_approved']}");
        $this->line("     - Total Rejected: {$statsFromService['total_rejected']}");
        $this->line("     - Approval Rate: {$statsFromService['approval_rate']}%");

        $this->newLine();

        // Show recent approval history details
        if ($historyFromService->count() > 0) {
            $this->info('📋 Recent Approval History (last 5):');
            foreach ($historyFromService->take(5) as $approval) {
                $pr = $approval->purchaseRequest;
                $this->line("   • {$pr->pr_number} - {$approval->status} - {$approval->responded_at->format('Y-m-d H:i')}");
                $this->line("     Requested by: {$pr->user->name}");
                $this->line("     Amount: {$pr->currency} ".number_format($pr->total_amount, 0));
                if ($approval->notes) {
                    $this->line('     Notes: '.\Str::limit($approval->notes, 50));
                }
                $this->line('');
            }
        }

        // Show pending approvals details
        if ($pendingFromService->count() > 0) {
            $this->info('⏳ Current Pending Approvals:');
            foreach ($pendingFromService as $approval) {
                $pr = $approval->purchaseRequest;
                $this->line("   • {$pr->pr_number} - Due: {$approval->due_date->format('Y-m-d')}");
                $this->line("     Requested by: {$pr->user->name}");
                $this->line("     Amount: {$pr->currency} ".number_format($pr->total_amount, 0));
                $this->line("     Assigned: {$approval->assigned_at->diffForHumans()}");
                $this->line('');
            }
        }

        $this->newLine();

        // Check for any issues
        $this->info('🔍 Issue Detection:');

        $orphanedApprovals = PrApproval::where('approver_id', $user->id)
            ->whereDoesntHave('purchaseRequest')
            ->count();

        if ($orphanedApprovals > 0) {
            $this->warn("   ⚠️  Found {$orphanedApprovals} orphaned approvals (no purchase request)");
        } else {
            $this->line('   ✅ No orphaned approvals found');
        }

        $nullResponseDates = PrApproval::where('approver_id', $user->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->whereNull('responded_at')
            ->count();

        if ($nullResponseDates > 0) {
            $this->warn("   ⚠️  Found {$nullResponseDates} completed approvals with null responded_at");
        } else {
            $this->line('   ✅ All completed approvals have response dates');
        }

        $this->newLine();

        $this->info('✅ Debug completed successfully!');
        $this->line('');
        $this->line('💡 To test the approval history page:');
        $this->line("   1. Login as user: {$user->email}");
        $this->line('   2. Navigate to: /approvals?tab=history');
        $this->line('   3. Check that approval history is displayed');

        return 0;
    }
}
