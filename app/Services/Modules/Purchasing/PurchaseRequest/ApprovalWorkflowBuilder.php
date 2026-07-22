<?php

namespace App\Services\Modules\Purchasing\PurchaseRequest;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\PurchaseRequest\PrApproval;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalWorkflowBuilder
{
    public function __construct(
        private ApprovalRuleEngine $ruleEngine,
        private ApprovalNotificationDispatcher $notifications,
    ) {}

    public function isEligibleApprover(PurchaseRequest $purchaseRequest, int $approverId): bool
    {
        if ($approverId === (int) $purchaseRequest->user_id) {
            return false;
        }

        $businessUnitIds = [];
        $businessUnit = $purchaseRequest->businessUnit;
        while ($businessUnit && ! in_array($businessUnit->id, $businessUnitIds, true)) {
            $businessUnitIds[] = $businessUnit->id;
            $businessUnit = $businessUnit->parent;
        }

        return User::query()
            ->whereKey($approverId)
            ->where('is_active', true)
            ->where('global_role', '!=', 'super_admin')
            ->whereHas('activeBusinessUnits', fn ($query) => $query->whereIn('business_unit_id', $businessUnitIds))
            ->exists();
    }

    public function create(PurchaseRequest $purchaseRequest): bool
    {
        DB::transaction(function () use ($purchaseRequest) {
            if ($purchaseRequest->approval_workflow && is_array($purchaseRequest->approval_workflow)) {
                $this->recreateFromJson($purchaseRequest);

                return;
            }

            $approvers = $this->ruleEngine->resolveApproversForAmount(
                $purchaseRequest,
                (int) $purchaseRequest->total_amount,
                $purchaseRequest->businessUnit,
            );

            if ($approvers->isEmpty()) {
                throw new \Exception('No approvers found for this request');
            }

            foreach ($approvers as $approverData) {
                if (! $this->isEligibleApprover($purchaseRequest, (int) $approverData['user']->id)) {
                    throw new \DomainException('An automatically selected approver is not eligible for this purchase request.');
                }
            }

            $this->ruleEngine->createApprovalSteps($purchaseRequest, $approvers);
            $purchaseRequest->update([
                'approval_workflow' => $this->ruleEngine->buildWorkflowStructure($approvers),
                'is_sequential_approval' => true,
                'status' => 'in_approval',
            ]);
        });

        $this->notifyFirstApprover($purchaseRequest);

        return true;
    }

    public function createFromRequest(
        PurchaseRequest $purchaseRequest,
        array $approvalWorkflow,
        ?string $notes = null,
    ): bool {
        DB::transaction(function () use ($purchaseRequest, $approvalWorkflow, $notes) {
            $workflowData = [];
            foreach ($approvalWorkflow as $index => $step) {
                $approver = User::with(['primaryDepartment', 'primaryPosition'])->find($step['approver_id']);
                if (! $approver) {
                    throw new \Exception("Approver with ID {$step['approver_id']} not found");
                }

                if ((int) $step['approver_id'] === (int) $purchaseRequest->user_id) {
                    throw new \Exception('Request creator cannot be assigned as an approver.');
                }

                if (! $this->isEligibleApprover($purchaseRequest, (int) $step['approver_id'])) {
                    throw new \DomainException('Selected approver is not eligible for this purchase request.');
                }

                $stepOrder = $index + 1;
                $taskType = $step['task_type'] ?? 'approval';
                $workflowData[] = [
                    'approver_id' => $approver->id,
                    'approver_name' => $approver->name,
                    'approver_email' => $approver->email,
                    'approver_department' => $approver->primaryDepartment?->name,
                    'approver_position' => $approver->primaryPosition?->name,
                    'step_order' => $stepOrder,
                    'approval_type' => $taskType,
                    'reason' => $notes ?? 'Custom approval workflow',
                    'due_date' => $this->ruleEngine->calculateDueDate($taskType)->toISOString(),
                ];

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
                    'metadata' => ['approver_snapshot' => $this->approverSnapshot($approver)],
                ]);
            }

            $purchaseRequest->update([
                'approval_workflow' => $workflowData,
                'is_sequential_approval' => true,
                'status' => 'in_approval',
            ]);
        });

        $this->notifyFirstApprover($purchaseRequest);

        return true;
    }

    private function recreateFromJson(PurchaseRequest $purchaseRequest): bool
    {
        $workflowData = $purchaseRequest->approval_workflow;
        if (empty($workflowData)) {
            throw new \Exception('No workflow data found to recreate');
        }

        foreach ($workflowData as $stepData) {
            if (! $this->isEligibleApprover($purchaseRequest, (int) $stepData['approver_id'])) {
                throw new \DomainException('A stored approver is no longer eligible for this purchase request.');
            }

            PrApproval::create([
                'purchase_request_id' => $purchaseRequest->id,
                'approver_id' => $stepData['approver_id'],
                'step_order' => $stepData['step_order'],
                'approval_type' => $stepData['approval_type'] ?? 'custom',
                'status' => 'pending',
                'assigned_at' => now(),
                'due_date' => isset($stepData['due_date'])
                    ? Carbon::parse($stepData['due_date'])
                    : $this->ruleEngine->calculateDueDate('custom'),
                'notes' => $stepData['reason'] ?? null,
                'responded_at' => null,
                'metadata' => [
                    'approver_snapshot' => [
                        'id' => $stepData['approver_id'] ?? null,
                        'name' => $stepData['approver_name'] ?? null,
                        'email' => $stepData['approver_email'] ?? null,
                        'department' => $stepData['approver_department'] ?? null,
                        'position' => $stepData['approver_position'] ?? null,
                    ],
                ],
            ]);
        }

        $purchaseRequest->update(['status' => 'in_approval']);

        return true;
    }

    private function notifyFirstApprover(PurchaseRequest $purchaseRequest): void
    {
        try {
            $this->notifications->notifyNextApprover($purchaseRequest);
        } catch (\Exception $e) {
            Log::warning('Failed to send approval notification', [
                'pr_id' => $purchaseRequest->id,
                'pr_number' => $purchaseRequest->pr_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function approverSnapshot(User $approver): array
    {
        return [
            'id' => $approver->id,
            'name' => $approver->name,
            'email' => $approver->email,
            'department' => $approver->primaryDepartment?->name,
            'position' => $approver->primaryPosition?->name,
        ];
    }
}
