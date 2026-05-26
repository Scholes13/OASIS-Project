<?php

namespace App\Services\Modules\Purchasing\PurchaseRequest;

use App\Models\Core\BusinessUnit;
use App\Models\Core\User;
use App\Models\Modules\Purchasing\PurchaseRequest\PrApproval;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Modules\Purchasing\Shared\ApprovalAuthorityResolver;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Threshold-based approval rule engine for Purchase Requests.
 *
 * Resolves the list of required approvers and supports utilities for
 * approval-step persistence and workflow shape. Lifted from
 * ApprovalWorkflowService::determineApprovers and surrounding helpers
 * to preserve current behavior.
 */
class ApprovalRuleEngine
{
    public function __construct(
        private ApprovalAuthorityResolver $authorityResolver,
    ) {}

    /**
     * Resolve the ordered approver chain for a PR amount.
     *
     * Public surface required by the Phase 2 split: callers may pass an
     * explicit amount + business unit (useful when the rule engine is
     * exercised against a draft amount or in tests).  The PR's department
     * is read from the model.
     *
     * @return Collection<int, array{user: User, step_order: int, approval_type: string, reason: string}>
     */
    public function resolveApproversForAmount(
        PurchaseRequest $purchaseRequest,
        int $amount,
        BusinessUnit $businessUnit,
    ): Collection {
        $approvers = collect();
        $department = $purchaseRequest->department;

        if (! $department) {
            throw new \RuntimeException(
                "Purchase request #{$purchaseRequest->id} has no associated department"
            );
        }

        $thresholds = config('approval.thresholds', [
            'department_head' => 500000,
            'finance_manager' => 1000000,
            'general_manager' => 5000000,
            'director' => 10000000,
        ]);

        // Rule 1: Department Head approval (if amount > threshold)
        if ($amount > $thresholds['department_head']) {
            $deptHead = $this->authorityResolver->findDepartmentHead(
                (int) $purchaseRequest->business_unit_id,
                (int) $department->id,
            );
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
            $financeManager = $this->authorityResolver->findFinanceManager(
                (int) $businessUnit->id,
            );
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
            $generalManager = $this->authorityResolver->findGeneralManager(
                (int) $businessUnit->id,
            );
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
            $director = $this->authorityResolver->findDirector(
                (int) $businessUnit->id,
            );
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
            $deptHead = $this->authorityResolver->findDepartmentHead(
                (int) $purchaseRequest->business_unit_id,
                (int) $department->id,
            );
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
     * Persist approval step records for the given approvers.
     */
    public function createApprovalSteps(PurchaseRequest $purchaseRequest, Collection $approvers): void
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
     * Build workflow structure for storage on the PR record.
     */
    public function buildWorkflowStructure(Collection $approvers): array
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
     * Calculate due date based on approval type.
     */
    public function calculateDueDate(string $approvalType): Carbon
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
     * Add business days (excluding weekends).
     */
    public function addBusinessDays(Carbon $date, int $days): Carbon
    {
        $result = $date->copy();

        while ($days > 0) {
            $result->addDay();
            if ($result->isWeekday()) {
                $days--;
            }
        }

        return $result;
    }

    /**
     * Get special category approver based on item names.
     */
    private function getSpecialCategoryApprover(PurchaseRequest $purchaseRequest): ?User
    {
        // Get special category keywords from config
        $categoryKeywords = config('approval.special_categories', [
            'it' => ['computer', 'laptop', 'server', 'software', 'hardware'],
            'vehicle' => ['vehicle', 'car', 'truck', 'motorcycle'],
        ]);

        $hasSpecialItems = false;
        $categoryType = null;

        foreach ($categoryKeywords as $type => $keywords) {
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
}
