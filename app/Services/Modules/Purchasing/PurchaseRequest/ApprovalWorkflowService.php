<?php

namespace App\Services\Modules\Purchasing\PurchaseRequest;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\PurchaseRequest\PrApproval;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Modules\Purchasing\Shared\ApprovalAuthorityResolver;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalWorkflowService
{
    protected ApprovalRuleEngine $ruleEngine;

    protected ApprovalNotificationDispatcher $notifications;

    public function __construct(
        ?ApprovalRuleEngine $ruleEngine = null,
        ?ApprovalNotificationDispatcher $notifications = null,
        ?ApprovalAuthorityResolver $authorityResolver = null,
    ) {
        // Allow direct `new ApprovalWorkflowService()` instantiation
        // (used by tests and legacy callers) while still supporting
        // container-resolved DI.  Lazy-default both collaborators so
        // we never depend on the container being booted.
        $this->ruleEngine = $ruleEngine
            ?? new ApprovalRuleEngine($authorityResolver ?? new ApprovalAuthorityResolver);
        $this->notifications = $notifications ?? new ApprovalNotificationDispatcher;
    }

    /**
     * Create approval workflow for a purchase request
     */
    public function createWorkflow(PurchaseRequest $purchaseRequest): bool
    {
        DB::transaction(function () use ($purchaseRequest) {
            // Check if PR has existing custom approval workflow (preserved from JSON)
            if ($purchaseRequest->approval_workflow && is_array($purchaseRequest->approval_workflow)) {
                // Recreate workflow from preserved JSON
                $this->recreateWorkflowFromJson($purchaseRequest);

                return;
            }

            // Otherwise, determine approvers based on business rules (automatic workflow)
            $approvers = $this->ruleEngine->resolveApproversForAmount(
                $purchaseRequest,
                (int) $purchaseRequest->total_amount,
                $purchaseRequest->businessUnit,
            );

            if ($approvers->isEmpty()) {
                throw new \Exception('No approvers found for this request');
            }

            // Create approval steps
            $this->ruleEngine->createApprovalSteps($purchaseRequest, $approvers);

            // Update PR workflow information
            $purchaseRequest->update([
                'approval_workflow' => $this->ruleEngine->buildWorkflowStructure($approvers),
                'is_sequential_approval' => true,
                'status' => 'in_approval',
            ]);
        });

        // Send notifications AFTER transaction commits

        // This prevents transaction rollback if notification fails
        // TODO: Consider queueing this for better performance
        try {
            $this->notifications->notifyNextApprover($purchaseRequest);
        } catch (\Exception $e) {
            // Log notification failure but don't fail the workflow
            Log::warning('Failed to send approval notification', [
                'pr_id' => $purchaseRequest->id,
                'pr_number' => $purchaseRequest->pr_number,
                'error' => $e->getMessage(),
            ]);
        }

        return true;
    }

    /**
     * Create approval workflow from user-submitted request data
     * Used when creating/editing PR with custom approval workflow
     *
     * @param  PurchaseRequest  $purchaseRequest  The PR to create workflow for
     * @param  array  $approvalWorkflow  Array of approval steps from form
     * @param  string|null  $notes  Optional notes for the workflow
     */
    public function createWorkflowFromRequest(PurchaseRequest $purchaseRequest, array $approvalWorkflow, ?string $notes = null): bool
    {
        DB::transaction(function () use ($purchaseRequest, $approvalWorkflow, $notes) {
            // Build workflow structure for storage
            $workflowData = [];
            foreach ($approvalWorkflow as $index => $step) {
                $approver = User::find($step['approver_id']);
                if (! $approver) {
                    throw new \Exception("Approver with ID {$step['approver_id']} not found");
                }

                // Block self-approval
                if ((int) $step['approver_id'] === (int) $purchaseRequest->user_id) {
                    throw new \Exception('Request creator cannot be assigned as an approver.');
                }

                $stepOrder = $index + 1;
                $taskType = $step['task_type'] ?? 'approval';

                $workflowData[] = [
                    'approver_id' => $approver->id,
                    'approver_name' => $approver->name,
                    'approver_email' => $approver->email,
                    'step_order' => $stepOrder,
                    'approval_type' => $taskType,
                    'reason' => $notes ?? 'Custom approval workflow',
                    'due_date' => $this->ruleEngine->calculateDueDate($taskType)->toISOString(),
                ];

                // Create approval record
                PrApproval::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'approver_id' => $approver->id,
                    'step_order' => $stepOrder,
                    'approval_type' => $taskType,
                    'status' => 'pending',
                    'assigned_at' => now(),
                    'due_date' => $this->ruleEngine->calculateDueDate($taskType),
                    'notes' => null,
                    'responded_at' => null,
                ]);
            }

            // Update PR workflow information
            $purchaseRequest->update([
                'approval_workflow' => $workflowData,
                'is_sequential_approval' => true,
                'status' => 'in_approval',
            ]);
        });

        // Send notification to first approver AFTER transaction commits
        try {
            $this->notifications->notifyNextApprover($purchaseRequest);
        } catch (\Exception $e) {
            Log::warning('Failed to send approval notification', [
                'pr_id' => $purchaseRequest->id,
                'pr_number' => $purchaseRequest->pr_number,
                'error' => $e->getMessage(),
            ]);
        }

        return true;
    }

    /**
     * Recreate workflow from preserved approval_workflow JSON
     * Used when resubmitting rejected PR to restore original custom workflow
     */
    protected function recreateWorkflowFromJson(PurchaseRequest $purchaseRequest): bool
    {
        $workflowData = $purchaseRequest->approval_workflow;

        if (empty($workflowData)) {
            throw new \Exception('No workflow data found to recreate');
        }

        // Recreate approval steps from JSON
        foreach ($workflowData as $stepData) {
            PrApproval::create([
                'purchase_request_id' => $purchaseRequest->id,
                'approver_id' => $stepData['approver_id'],
                'step_order' => $stepData['step_order'],
                'approval_type' => $stepData['approval_type'] ?? 'custom',
                'status' => 'pending',
                'assigned_at' => now(),
                'due_date' => isset($stepData['due_date']) ? Carbon::parse($stepData['due_date']) : $this->ruleEngine->calculateDueDate('custom'),
                'notes' => $stepData['reason'] ?? null,
                'responded_at' => null,
            ]);
        }

        // Update PR status
        $purchaseRequest->update([
            'status' => 'in_approval',
        ]);

        // Note: DB::commit() handled by parent createWorkflow() method
        // Notification will be sent by parent after transaction commits

        return true;
    }

    /**
     * Process approval step
     */
    public function processApproval(PrApproval $approval, string $action, ?string $notes = null): bool
    {
        // Validate action parameter
        $validActions = ['approved', 'rejected'];
        if (! in_array($action, $validActions, true)) {
            throw new \InvalidArgumentException(
                "Invalid approval action: {$action}. Must be one of: ".implode(', ', $validActions)
            );
        }

        // Check if the assigned approver is still active
        $approver = User::find($approval->approver_id);
        if (! $approver || ! ($approver->is_active ?? true)) {
            throw new \Exception('The assigned approver is no longer active. Please contact an administrator to reassign the approval.');
        }

        $purchaseRequest = DB::transaction(function () use ($approval, $action, $notes) {
            // Update current approval
            $approval->update([
                'status' => $action,
                'notes' => $notes,
                'responded_at' => now(),
            ]);

            $purchaseRequest = $approval->purchaseRequest;

            if (! $purchaseRequest) {
                throw new \RuntimeException('Purchase request not found for approval ID: '.$approval->id);
            }

            if ($action === 'approved') {
                $this->handleApprovalStep($purchaseRequest);
            } else {
                $this->handleRejectionStep($purchaseRequest);
            }

            return $purchaseRequest;
        });

        // Dispatch event for auto-logging AFTER transaction commits
        // This allows the activity tracking module to automatically log the approval action
        try {
            \App\Events\Purchasing\PrApprovalCompleted::dispatch($approval);
        } catch (\Exception $e) {
            // Log but don't fail the approval process if event dispatch fails
            Log::warning('Failed to dispatch PrApprovalCompleted event', [
                'approval_id' => $approval->id,
                'pr_number' => $purchaseRequest->pr_number,
                'error' => $e->getMessage(),
            ]);
        }

        return true;
    }

    /**
     * Handle approval step completion
     */
    protected function handleApprovalStep(PurchaseRequest $purchaseRequest): void
    {
        $pendingApprovals = $purchaseRequest->pendingApprovals()->count();

        if ($pendingApprovals === 0) {
            // All approvals completed
            $purchaseRequest->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            $this->notifications->notifyCompletion($purchaseRequest);
        } else {
            // More approvals needed
            $purchaseRequest->update(['status' => 'in_approval']);
            $this->notifications->notifyNextApprover($purchaseRequest);
        }
    }

    /**
     * Handle rejection step
     */
    protected function handleRejectionStep(PurchaseRequest $purchaseRequest): void
    {
        $purchaseRequest->update([
            'status' => 'rejected',
            'rejected_at' => now(),
        ]);

        $this->notifications->notifyRejection($purchaseRequest);
    }

    /**
     * Get workflow status for a purchase request
     */
    public function getWorkflowStatus(PurchaseRequest $purchaseRequest): array
    {
        $approvals = $purchaseRequest->approvals()->orderBy('step_order')->get();

        $totalSteps = $approvals->count();
        $completedSteps = $approvals->whereIn('status', ['approved', 'rejected'])->count();
        $currentStep = $purchaseRequest->currentApproval();

        return [
            'total_steps' => $totalSteps,
            'completed_steps' => $completedSteps,
            'current_step' => $currentStep?->step_order,
            'current_approver' => $currentStep?->approver?->name,
            'progress_percentage' => $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100, 2) : 0,
            'status' => $purchaseRequest->status,
            'is_complete' => in_array($purchaseRequest->status, ['approved', 'rejected']),
            'workflow_steps' => $approvals->map(function ($approval) {
                return [
                    'step_order' => $approval->step_order,
                    'approver_name' => $approval->approver?->name,
                    'approval_type' => $approval->approval_type ?? 'general',
                    'status' => $approval->status,
                    'assigned_at' => $approval->assigned_at,
                    'due_date' => $approval->due_date,
                    'responded_at' => $approval->responded_at,
                    'notes' => $approval->notes,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get pending approvals for a user
     * Returns query builder to allow pagination in controller
     * Shows all approvals in PRs where user is involved
     */
    public function getPendingApprovalsForUser(User $user)
    {
        // Get PR IDs where user has any approval
        $prIds = PrApproval::where('approver_id', $user->id)
            ->whereHas('purchaseRequest', function ($query) {
                $query->where('status', 'in_approval');
            })
            ->pluck('purchase_request_id')
            ->unique();

        // Get all approvals for those PRs
        return PrApproval::with([
            'purchaseRequest.user',
            'purchaseRequest.department',
            'purchaseRequest.items',
            'approver',
        ])
            ->whereIn('purchase_request_id', $prIds)
            ->orderBy('purchase_request_id', 'desc')
            ->orderBy('step_order', 'asc');
    }

    /**
     * Get approval history for a user (completed approvals)
     * Returns query builder to allow pagination in controller
     */
    public function getApprovalHistoryForUser(User $user)
    {
        return PrApproval::with([
            'purchaseRequest.user',
            'purchaseRequest.department',
            'purchaseRequest.businessUnit',
            'purchaseRequest.items',
            'approver',
        ])
            ->where('approver_id', $user->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->whereNotNull('responded_at')
            ->orderBy('responded_at', 'desc');
    }

    /**
     * Get approval statistics for a user
     */
    public function getApprovalStatistics(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = PrApproval::where('approver_id', $user->id);

        if ($startDate) {
            $query->where('responded_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('responded_at', '<=', $endDate);
        }

        $approvals = $query->get();

        return [
            'total_assigned' => $approvals->count(),
            'total_approved' => $approvals->where('status', 'approved')->count(),
            'total_rejected' => $approvals->where('status', 'rejected')->count(),
            'total_pending' => $approvals->where('status', 'pending')->count(),
            'average_response_time_hours' => $this->calculateAverageResponseTime($approvals),
            'approval_rate' => $approvals->count() > 0 ?
                round(($approvals->where('status', 'approved')->count() / $approvals->count()) * 100, 2) : 0,
        ];
    }

    /**
     * Calculate average response time in hours
     */
    protected function calculateAverageResponseTime(Collection $approvals): float
    {
        $respondedApprovals = $approvals->whereNotNull('responded_at');

        if ($respondedApprovals->isEmpty()) {
            return 0;
        }

        $totalHours = $respondedApprovals->sum(function ($approval) {
            // Add null safety check for assigned_at
            if (! $approval->assigned_at || ! $approval->responded_at) {
                return 0;
            }

            return $approval->assigned_at->diffInHours($approval->responded_at);
        });

        return round($totalHours / $respondedApprovals->count(), 2);
    }

    /**
     * Reset workflow (for when PR is edited after submission)
     */
    public function resetWorkflow(PurchaseRequest $purchaseRequest): bool
    {
        DB::transaction(function () use ($purchaseRequest) {
            // Delete existing approvals
            $purchaseRequest->approvals()->delete();

            // Reset PR status but KEEP approval_workflow JSON (for recreation)
            // CRITICAL: submitted_at is PRESERVED for QR token reusability
            $purchaseRequest->update([
                'status' => 'draft',
                // approval_workflow is PRESERVED (not set to null)
                // is_sequential_approval is PRESERVED
                // submitted_at is PRESERVED (for QR token consistency)
                'approved_at' => null,
                'rejected_at' => null,
            ]);
        });

        return true;
    }
}
