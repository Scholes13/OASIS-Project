<?php

namespace App\Services\Modules\PurchaseRequest;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\PurchaseRequest\PrApproval;
use App\Models\Modules\PurchaseRequest\PurchaseRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalWorkflowService
{
    /**
     * Create approval workflow for a purchase request
     */
    public function createWorkflow(PurchaseRequest $purchaseRequest): bool
    {
        try {
            DB::beginTransaction();

            // Check if PR has existing custom approval workflow (preserved from JSON)
            if ($purchaseRequest->approval_workflow && is_array($purchaseRequest->approval_workflow)) {
                // Recreate workflow from preserved JSON
                return $this->recreateWorkflowFromJson($purchaseRequest);
            }

            // Otherwise, determine approvers based on business rules (automatic workflow)
            $approvers = $this->determineApprovers($purchaseRequest);

            if ($approvers->isEmpty()) {
                throw new \Exception('No approvers found for this request');
            }

            // Create approval steps
            $this->createApprovalSteps($purchaseRequest, $approvers);

            // Update PR workflow information
            $purchaseRequest->update([
                'approval_workflow' => $this->buildWorkflowStructure($approvers),
                'is_sequential_approval' => true,
                'status' => 'in_approval',
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // Send notifications AFTER transaction commits
        // This prevents transaction rollback if notification fails
        // TODO: Consider queueing this for better performance
        try {
            $this->notifyNextApprover($purchaseRequest);
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
                'due_date' => isset($stepData['due_date']) ? Carbon::parse($stepData['due_date']) : $this->calculateDueDate('custom'),
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
     * Determine approvers based on business rules
     */
    protected function determineApprovers(PurchaseRequest $purchaseRequest): Collection
    {
        $approvers = collect();
        $amount = $purchaseRequest->total_amount;
        $department = $purchaseRequest->department;

        if (! $department) {
            throw new \RuntimeException(
                "Purchase request #{$purchaseRequest->id} has no associated department"
            );
        }

        // Get thresholds from config for maintainability
        $thresholds = config('approval.thresholds', [
            'department_head' => 500000,
            'finance_manager' => 1000000,
            'general_manager' => 5000000,
            'director' => 10000000,
        ]);

        // Rule 1: Department Head approval (if amount > threshold)
        if ($amount > $thresholds['department_head']) {
            $deptHead = $this->getDepartmentHead($department);
            if ($deptHead) {
                $approvers->push([
                    'user' => $deptHead,
                    'step_order' => 1,
                    'approval_type' => 'department_head',
                    'reason' => 'Department Head approval required for amount > IDR '.number_format($thresholds['department_head'], 0, ',', '.'),
                ]);
            }
        }

        // Rule 2: Finance Manager approval (if amount > threshold)
        if ($amount > $thresholds['finance_manager']) {
            $financeManager = $this->getFinanceManager($purchaseRequest->businessUnit);
            if ($financeManager) {
                $approvers->push([
                    'user' => $financeManager,
                    'step_order' => $approvers->count() + 1,
                    'approval_type' => 'finance_manager',
                    'reason' => 'Finance Manager approval required for amount > IDR '.number_format($thresholds['finance_manager'], 0, ',', '.'),
                ]);
            }
        }

        // Rule 3: General Manager approval (if amount > threshold)
        if ($amount > $thresholds['general_manager']) {
            $generalManager = $this->getGeneralManager($purchaseRequest->businessUnit);
            if ($generalManager) {
                $approvers->push([
                    'user' => $generalManager,
                    'step_order' => $approvers->count() + 1,
                    'approval_type' => 'general_manager',
                    'reason' => 'General Manager approval required for amount > IDR '.number_format($thresholds['general_manager'], 0, ',', '.'),
                ]);
            }
        }

        // Rule 4: Director approval (if amount > threshold)
        if ($amount > $thresholds['director']) {
            $director = $this->getDirector($purchaseRequest->businessUnit);
            if ($director) {
                $approvers->push([
                    'user' => $director,
                    'step_order' => $approvers->count() + 1,
                    'approval_type' => 'director',
                    'reason' => 'Director approval required for amount > IDR '.number_format($thresholds['director'], 0, ',', '.'),
                ]);
            }
        }

        // Rule 5: Special approval for specific item categories
        $specialApprover = $this->getSpecialCategoryApprover($purchaseRequest);
        if ($specialApprover) {
            $approvers->push([
                'user' => $specialApprover,
                'step_order' => $approvers->count() + 1,
                'approval_type' => 'special_category',
                'reason' => 'Special category approval required',
            ]);
        }

        // If no approvers found based on rules, assign department head as default
        if ($approvers->isEmpty()) {
            $deptHead = $this->getDepartmentHead($department);
            if ($deptHead) {
                $approvers->push([
                    'user' => $deptHead,
                    'step_order' => 1,
                    'approval_type' => 'default',
                    'reason' => 'Default department head approval',
                ]);
            }
        }

        return $approvers->sortBy('step_order')->values();
    }

    /**
     * Create approval steps for the workflow
     */
    protected function createApprovalSteps(PurchaseRequest $purchaseRequest, Collection $approvers): void
    {
        foreach ($approvers as $approverData) {
            PrApproval::create([
                'purchase_request_id' => $purchaseRequest->id,
                'approver_id' => $approverData['user']->id,
                'step_order' => $approverData['step_order'],
                'approval_type' => $approverData['approval_type'],
                'status' => 'pending',
                'assigned_at' => now(),
                'due_date' => $this->calculateDueDate($approverData['approval_type']),
                'notes' => null,
                'responded_at' => null,
            ]);
        }
    }

    /**
     * Build workflow structure for storage
     */
    protected function buildWorkflowStructure(Collection $approvers): array
    {
        return $approvers->map(function ($approverData) {
            return [
                'approver_id' => $approverData['user']->id,
                'approver_name' => $approverData['user']->name,
                'approver_email' => $approverData['user']->email,
                'step_order' => $approverData['step_order'],
                'approval_type' => $approverData['approval_type'],
                'reason' => $approverData['reason'],
                'due_date' => $this->calculateDueDate($approverData['approval_type'])->toISOString(),
            ];
        })->toArray();
    }

    /**
     * Calculate due date based on approval type
     */
    protected function calculateDueDate(string $approvalType): Carbon
    {
        $businessDays = match ($approvalType) {
            'department_head' => 2,
            'finance_manager' => 3,
            'general_manager' => 5,
            'director' => 7,
            'special_category' => 3,
            default => 2,
        };

        return $this->addBusinessDays(now(), $businessDays);
    }

    /**
     * Add business days (excluding weekends)
     */
    protected function addBusinessDays(Carbon $date, int $days): Carbon
    {
        $result = $date->copy();

        while ($days > 0) {
            $result->addDay();

            // Skip weekends
            if ($result->isWeekday()) {
                $days--;
            }
        }

        return $result;
    }

    /**
     * Get department head
     */
    protected function getDepartmentHead(Department $department): ?User
    {
        return User::whereHas('roles', function ($query) {
            $query->where('name', 'department_head');
        })
            ->where('primary_department_id', $department->id)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get finance manager
     */
    protected function getFinanceManager(BusinessUnit $businessUnit): ?User
    {
        return User::whereHas('roles', function ($query) {
            $query->where('name', 'finance_manager');
        })
            ->whereHas('businessUnits', function ($query) use ($businessUnit) {
                $query->where('business_unit_id', $businessUnit->id)
                    ->where('is_active', true);
            })
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get general manager
     */
    protected function getGeneralManager(BusinessUnit $businessUnit): ?User
    {
        return User::whereHas('roles', function ($query) {
            $query->where('name', 'general_manager');
        })
            ->whereHas('businessUnits', function ($query) use ($businessUnit) {
                $query->where('business_unit_id', $businessUnit->id)
                    ->where('is_active', true);
            })
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get director
     */
    protected function getDirector(BusinessUnit $businessUnit): ?User
    {
        return User::whereHas('roles', function ($query) {
            $query->where('name', 'director');
        })
            ->whereHas('businessUnits', function ($query) use ($businessUnit) {
                $query->where('business_unit_id', $businessUnit->id)
                    ->where('is_active', true);
            })
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get special category approver based on item categories
     * Uses config-driven approach for maintainability
     */
    protected function getSpecialCategoryApprover(PurchaseRequest $purchaseRequest): ?User
    {
        // Get special category keywords from config
        $categoryKeywords = config('approval.special_categories', [
            'it' => ['computer', 'laptop', 'server', 'software', 'hardware'],
            'vehicle' => ['vehicle', 'car', 'truck', 'motorcycle'],
        ]);

        $hasSpecialItems = false;
        $categoryType = null;

        // Check if any item matches special categories
        foreach ($categoryKeywords as $type => $keywords) {
            $hasMatch = false;

            // Use database-agnostic LIKE queries instead of MySQL REGEXP
            $matchingItems = $purchaseRequest->items()->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhereRaw('LOWER(item_name) LIKE ?', ['%'.strtolower($keyword).'%']);
                }
            })->exists();

            if ($matchingItems) {
                $hasSpecialItems = true;
                $categoryType = $type;
                break;
            }
        }

        if ($hasSpecialItems && $categoryType) {
            // Get approver role from config based on category type
            $approverRole = config("approval.special_category_approvers.{$categoryType}", 'it_manager');

            return User::whereHas('roles', function ($query) use ($approverRole) {
                $query->where('name', $approverRole);
            })
                ->where('is_active', true)
                ->first();
        }

        return null;
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

        try {
            DB::beginTransaction();

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

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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

            $this->notifyCompletion($purchaseRequest);
        } else {
            // More approvals needed
            $purchaseRequest->update(['status' => 'in_approval']);
            $this->notifyNextApprover($purchaseRequest);
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

        $this->notifyRejection($purchaseRequest);
    }

    /**
     * Send notification to next approver
     */
    protected function notifyNextApprover(PurchaseRequest $purchaseRequest): void
    {
        $nextApproval = $purchaseRequest->currentApproval();

        if ($nextApproval) {
            try {
                $emailService = app(\App\Services\Core\EmailNotificationService::class);
                $emailService->sendApprovalRequested($nextApproval);

                Log::info('Approval notification sent successfully', [
                    'pr_number' => $purchaseRequest->pr_number,
                    'approver_id' => $nextApproval->approver_id,
                    'approver_email' => $nextApproval->approver->email,
                    'step_order' => $nextApproval->step_order,
                    'due_date' => $nextApproval->due_date,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to send approval notification (fallback to database saved)', [
                    'pr_number' => $purchaseRequest->pr_number,
                    'approver_email' => $nextApproval->approver->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Notify completion of all approvals
     */
    protected function notifyCompletion(PurchaseRequest $purchaseRequest): void
    {
        try {
            $emailService = app(\App\Services\Core\EmailNotificationService::class);
            $emailService->sendApprovalCompleted($purchaseRequest);

            Log::info('PR approval completion notification sent successfully', [
                'pr_number' => $purchaseRequest->pr_number,
                'requestor_id' => $purchaseRequest->user_id,
                'requestor_email' => $purchaseRequest->user->email,
                'approved_at' => $purchaseRequest->approved_at,
                'total_amount' => $purchaseRequest->total_amount,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to send completion notification (fallback to database saved)', [
                'pr_number' => $purchaseRequest->pr_number,
                'requestor_email' => $purchaseRequest->user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify rejection
     */
    protected function notifyRejection(PurchaseRequest $purchaseRequest): void
    {
        $rejectedApproval = $purchaseRequest->approvals()
            ->where('status', 'rejected')
            ->orderBy('responded_at', 'desc')
            ->first();

        if ($rejectedApproval) {
            try {
                $emailService = app(\App\Services\Core\EmailNotificationService::class);
                $emailService->sendApprovalRejected($rejectedApproval);

                Log::info('PR rejection notification sent successfully', [
                    'pr_number' => $purchaseRequest->pr_number,
                    'requestor_id' => $purchaseRequest->user_id,
                    'requestor_email' => $purchaseRequest->user?->email,
                    'rejected_by' => $rejectedApproval->approver->email,
                    'rejection_reason' => $rejectedApproval->notes,
                    'rejected_at' => $purchaseRequest->rejected_at,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to send rejection notification (fallback to database saved)', [
                    'pr_number' => $purchaseRequest->pr_number,
                    'requestor_email' => $purchaseRequest->user?->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
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
            'current_approver' => $currentStep?->approver->name,
            'progress_percentage' => $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100, 2) : 0,
            'status' => $purchaseRequest->status,
            'is_complete' => in_array($purchaseRequest->status, ['approved', 'rejected']),
            'workflow_steps' => $approvals->map(function ($approval) {
                return [
                    'step_order' => $approval->step_order,
                    'approver_name' => $approval->approver->name,
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
        try {
            DB::beginTransaction();

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

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
