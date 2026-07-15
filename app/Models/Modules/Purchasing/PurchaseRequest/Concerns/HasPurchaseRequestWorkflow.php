<?php

namespace App\Models\Modules\Purchasing\PurchaseRequest\Concerns;

use App\Models\Core\ApprovalWorkflow;
use App\Models\Core\User;
use App\Models\Modules\Purchasing\PurchaseRequest\PrApproval;

trait HasPurchaseRequestWorkflow
{
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === 'draft' && $this->items()->count() > 0;
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, ['submitted', 'in_approval']);
    }

    public function canBeVoided(): bool
    {
        return ! in_array($this->status, ['voided', 'approved']);
    }

    public function submit(): bool
    {
        if (! $this->canBeSubmitted()) {
            return false;
        }

        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->createApprovalWorkflow();

        return true;
    }

    public function approve(User $approver, ?string $notes = null): bool
    {
        if (! $this->canBeApproved()) {
            return false;
        }

        $currentApproval = $this->currentApproval();
        if (! $currentApproval || $currentApproval->approver_id !== $approver->id) {
            return false;
        }

        $currentApproval->update([
            'status' => 'approved',
            'notes' => $notes,
            'responded_at' => now(),
        ]);

        if ($this->pendingApprovals()->count() === 0) {
            $this->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);
        } else {
            $this->update(['status' => 'in_approval']);
        }

        return true;
    }

    public function reject(User $approver, string $notes): bool
    {
        if (! $this->canBeApproved()) {
            return false;
        }

        $currentApproval = $this->currentApproval();
        if (! $currentApproval || $currentApproval->approver_id !== $approver->id) {
            return false;
        }

        $currentApproval->update([
            'status' => 'rejected',
            'notes' => $notes,
            'responded_at' => now(),
        ]);

        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
        ]);

        return true;
    }

    public function void(User $user, string $reason): bool
    {
        if (! $this->canBeVoided()) {
            return false;
        }

        $this->update([
            'status' => 'voided',
            'voided_at' => now(),
            'last_modified_by' => $user->id,
        ]);

        $this->addToEditHistory('voided', $reason, $user->id);

        return true;
    }

    public function calculateTotalAmount(): float
    {
        return $this->items()->sum('total_price');
    }

    public function updateTotalAmount(): void
    {
        $this->update(['total_amount' => $this->calculateTotalAmount()]);
    }

    public function addToEditHistory(string $action, string $description, int $userId): void
    {
        $history = $this->edit_history ?? [];
        $history[] = [
            'action' => $action,
            'description' => $description,
            'user_id' => $userId,
            'timestamp' => now()->toISOString(),
        ];

        $this->update(['edit_history' => $history]);
    }

    public function resetApprovals(User $user): void
    {
        if ($this->status !== 'draft') {
            // submitted_at stays stable so existing QR tokens remain reusable.
            $this->update([
                'status' => 'draft',
                'approved_at' => null,
                'rejected_at' => null,
            ]);

            $this->approvals()->delete();
            $this->addToEditHistory('reset_approvals', 'Items modified - approvals reset', $user->id);
        }
    }

    protected function createApprovalWorkflow(): void
    {
        $workflowData = [
            'total_amount' => $this->total_amount,
            'department_code' => $this->department->code,
            'business_unit_id' => $this->business_unit_id,
        ];

        $workflow = ApprovalWorkflow::getWorkflowForConditions(
            $this->business_unit_id,
            'purchase_request',
            $workflowData
        );

        if (! $workflow) {
            $workflow = ApprovalWorkflow::getDefaultWorkflow(
                $this->business_unit_id,
                'purchase_request'
            );
        }

        if (! $workflow) {
            throw new \Exception('No approval workflow found for this purchase request');
        }

        $this->update([
            'approval_workflow' => $workflow->approval_steps,
            'is_sequential_approval' => $workflow->is_sequential,
            'status' => 'in_approval',
        ]);

        foreach ($workflow->approval_steps as $step) {
            $approver = User::with(['primaryDepartment', 'primaryPosition'])->find($step['approver_id']);

            PrApproval::create([
                'purchase_request_id' => $this->id,
                'approver_id' => $step['approver_id'],
                'step_order' => $step['step'],
                'status' => 'pending',
                'assigned_at' => now(),
                'due_date' => now()->addDays(3),
                'metadata' => [
                    'approver_snapshot' => [
                        'id' => $approver?->id,
                        'name' => $approver?->name,
                        'email' => $approver?->email,
                        'department' => $approver?->primaryDepartment?->name,
                        'position' => $approver?->primaryPosition?->name,
                    ],
                ],
            ]);
        }
    }
}
