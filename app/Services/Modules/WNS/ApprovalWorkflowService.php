<?php

namespace App\Services\Modules\WNS;

use App\Models\Modules\WNS\PurchaseRequest;
use App\Models\Modules\WNS\PrApproval;
use App\Models\User;
use App\Models\Department;
use App\Models\BusinessUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ApprovalWorkflowService
{
    /**
     * Create approval workflow for a purchase request
     */
    public function createWorkflow(PurchaseRequest $purchaseRequest): bool
    {
        try {
            DB::beginTransaction();
            
            // Determine approvers based on business rules
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
                'status' => 'in_approval'
            ]);
            
            DB::commit();
            
            // Send notifications to first approver
            $this->notifyNextApprover($purchaseRequest);
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Determine approvers based on business rules
     */
    protected function determineApprovers(PurchaseRequest $purchaseRequest): Collection
    {
        $approvers = collect();
        $amount = $purchaseRequest->total_amount;
        $department = $purchaseRequest->department;
        
        // Rule 1: Department Head approval (if amount > 500,000)
        if ($amount > 500000) {
            $deptHead = $this->getDepartmentHead($department);
            if ($deptHead) {
                $approvers->push([
                    'user' => $deptHead,
                    'step_order' => 1,
                    'approval_type' => 'department_head',
                    'reason' => 'Department Head approval required for amount > IDR 500,000'
                ]);
            }
        }
        
        // Rule 2: Finance Manager approval (if amount > 1,000,000)
        if ($amount > 1000000) {
            $financeManager = $this->getFinanceManager($purchaseRequest->businessUnit);
            if ($financeManager) {
                $approvers->push([
                    'user' => $financeManager,
                    'step_order' => $approvers->count() + 1,
                    'approval_type' => 'finance_manager',
                    'reason' => 'Finance Manager approval required for amount > IDR 1,000,000'
                ]);
            }
        }
        
        // Rule 3: General Manager approval (if amount > 5,000,000)
        if ($amount > 5000000) {
            $generalManager = $this->getGeneralManager($purchaseRequest->businessUnit);
            if ($generalManager) {
                $approvers->push([
                    'user' => $generalManager,
                    'step_order' => $approvers->count() + 1,
                    'approval_type' => 'general_manager',
                    'reason' => 'General Manager approval required for amount > IDR 5,000,000'
                ]);
            }
        }
        
        // Rule 4: Director approval (if amount > 10,000,000)
        if ($amount > 10000000) {
            $director = $this->getDirector($purchaseRequest->businessUnit);
            if ($director) {
                $approvers->push([
                    'user' => $director,
                    'step_order' => $approvers->count() + 1,
                    'approval_type' => 'director',
                    'reason' => 'Director approval required for amount > IDR 10,000,000'
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
                'reason' => 'Special category approval required'
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
                    'reason' => 'Default department head approval'
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
        ->whereHas('departmentUsers', function ($query) use ($department) {
            $query->where('department_id', $department->id)
                  ->where('is_active', true);
        })
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
     * Get special category approver (for IT equipment, vehicles, etc.)
     */
    protected function getSpecialCategoryApprover(PurchaseRequest $purchaseRequest): ?User
    {
        $specialItems = $purchaseRequest->items()->where(function ($query) {
            $query->where('item_name', 'like', '%computer%')
                  ->orWhere('item_name', 'like', '%laptop%')
                  ->orWhere('item_name', 'like', '%server%')
                  ->orWhere('item_name', 'like', '%vehicle%')
                  ->orWhere('item_name', 'like', '%car%')
                  ->orWhere('item_name', 'like', '%software%');
        })->exists();
        
        if ($specialItems) {
            return User::whereHas('roles', function ($query) {
                $query->where('name', 'it_manager');
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
        try {
            DB::beginTransaction();
            
            // Update current approval
            $approval->update([
                'status' => $action,
                'notes' => $notes,
                'responded_at' => now(),
            ]);
            
            $purchaseRequest = $approval->purchaseRequest;
            
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
            // Here you would integrate with your notification system
            // For now, we'll just log the notification
            Log::info("Approval notification sent", [
                'pr_number' => $purchaseRequest->pr_number,
                'approver_id' => $nextApproval->approver_id,
                'approver_email' => $nextApproval->approver->email,
                'step_order' => $nextApproval->step_order,
                'due_date' => $nextApproval->due_date,
            ]);
            
            // TODO: Send email notification
            // TODO: Send in-app notification
            // TODO: Send SMS notification (if configured)
        }
    }
    
    /**
     * Notify completion of all approvals
     */
    protected function notifyCompletion(PurchaseRequest $purchaseRequest): void
    {
        Log::info("PR approval completed", [
            'pr_number' => $purchaseRequest->pr_number,
            'requestor_id' => $purchaseRequest->user_id,
            'requestor_email' => $purchaseRequest->user->email,
            'approved_at' => $purchaseRequest->approved_at,
            'total_amount' => $purchaseRequest->total_amount,
        ]);
        
        // TODO: Send completion notification to requestor
        // TODO: Send notification to procurement team
        // TODO: Send notification to finance team
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
            
        Log::info("PR rejected", [
            'pr_number' => $purchaseRequest->pr_number,
            'requestor_id' => $purchaseRequest->user_id,
            'requestor_email' => $purchaseRequest->user->email,
            'rejected_by' => $rejectedApproval?->approver->email,
            'rejection_reason' => $rejectedApproval?->notes,
            'rejected_at' => $purchaseRequest->rejected_at,
        ]);
        
        // TODO: Send rejection notification to requestor
        // TODO: Send notification to relevant stakeholders
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
     */
    public function getPendingApprovalsForUser(User $user): Collection
    {
        return PrApproval::with([
            'purchaseRequest.user',
            'purchaseRequest.department',
            'purchaseRequest.items'
        ])
        ->where('approver_id', $user->id)
        ->where('status', 'pending')
        ->whereHas('purchaseRequest', function ($query) {
            $query->where('status', 'in_approval');
        })
        ->orderBy('assigned_at', 'asc')
        ->get();
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
            
            // Reset PR status and workflow data
            $purchaseRequest->update([
                'status' => 'draft',
                'approval_workflow' => null,
                'is_sequential_approval' => false,
                'submitted_at' => null,
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