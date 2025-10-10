<?php

namespace App\Services\Modules\PurchaseRequest;

use App\Models\Core\Department;
use App\Models\Modules\PurchaseRequest\PrItem;
use App\Models\Modules\PurchaseRequest\PurchaseRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PurchaseRequestService
{
    protected UniversalPRNumberingService $numberingService;

    protected ApprovalWorkflowService $workflowService;

    public function __construct(
        UniversalPRNumberingService $numberingService,
        ApprovalWorkflowService $workflowService
    ) {
        $this->numberingService = $numberingService;
        $this->workflowService = $workflowService;
    }

    /**
     * Get Purchase Requests based on user hierarchy and filters
     */
    public function getPurchaseRequestsQuery(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $user = Auth::user();
        $accessLevel = $user->getAccessLevel();

        // Get business unit context
        $currentBusinessUnitId = session('current_business_unit_id');
        $currentBusinessUnit = \App\Models\Core\BusinessUnit::find($currentBusinessUnitId);        // Base query with business unit hierarchy consideration
        $query = PurchaseRequest::with(['department', 'user', 'items', 'approvals']);

        // Apply business unit filtering based on hierarchy
        if ($currentBusinessUnit) {
            if (in_array($accessLevel, ['super_admin', 'executive'])) {
                $accessibleBusinessUnits = $currentBusinessUnit->getAccessibleBusinessUnits();
                $query->whereIn('business_unit_id', $accessibleBusinessUnits);
            } elseif ($accessLevel === 'general_manager') {
                $managedBusinessUnits = $user->generalManagerBusinessUnitIds();
                if (! empty($managedBusinessUnits)) {
                    $query->whereIn('business_unit_id', $managedBusinessUnits);
                } else {
                    $query->where('business_unit_id', $currentBusinessUnitId);
                }
            } else {
                $query->where('business_unit_id', $currentBusinessUnitId);
            }
        } else {
            // Fallback: only current business unit
            $query->where('business_unit_id', $currentBusinessUnitId);
        }

        // Apply hierarchy-based filtering
        switch ($accessLevel) {
            case 'super_admin':
            case 'executive':
            case 'general_manager':
                // Can see all PRs in accessible business units (already filtered above)
                break;

            case 'department_head':
                // Department head can see all PRs in their department
                $query->where('department_id', $user->primary_department_id);
                break;

            case 'team_leader':
                // Team leader can see their own + subordinates' PRs
                $subordinateIds = $user->activeSubordinates()->pluck('id')->toArray();
                $subordinateIds[] = $user->id; // Include own PRs
                $query->whereIn('user_id', $subordinateIds);
                break;

            case 'staff':
            default:
                // Staff can only see their own PRs
                $query->byUser($user->id);
                break;
        }

        // Apply additional filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('date_of_request', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('date_of_request', '<=', $filters['date_to']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query;
    }

    /**
     * Create a new Purchase Request
     */
    public function createPurchaseRequest(array $data): PurchaseRequest
    {
        DB::beginTransaction();

        try {
            // Generate PR number
            $prNumber = $this->numberingService->generatePRNumber(
                Auth::user(),
                session('current_business_unit_id'),
                null,
                Carbon::parse($data['date_of_request'])
            );

            // Create purchase request
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => $prNumber['formatted_number'],
                'business_unit_id' => session('current_business_unit_id'),
                'department_id' => session('current_department_id'),
                'user_id' => Auth::id(),
                'sequence_id' => $prNumber['sequence_id'],
                'keperluan' => $data['keperluan'],
                'used_for' => $data['used_for'],
                'date_of_request' => $data['date_of_request'],
                'designated_date' => $data['designated_date'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'currency' => $data['items'][0]['currency'] ?? 'IDR',
                'last_modified_by' => Auth::id(),
            ]);

            // Create PR items
            foreach ($data['items'] as $index => $itemData) {
                PrItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'item_order' => $index + 1,
                    'item_name' => $itemData['item_name'],
                    'brand_name' => $itemData['brand_name'] ?? null,
                    'item_description' => $itemData['item_description'] ?? null,
                    'supplier_name' => $itemData['supplier_name'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'unit_price' => $itemData['unit_price'],
                    'currency' => $itemData['currency'],
                    'expense_department_id' => $itemData['expense_department_id'],
                ]);
            }

            // Update total amount
            $purchaseRequest->updateTotalAmount();

            // Set submitted_at if status is submitted
            if (($data['status'] ?? 'draft') === 'submitted') {
                $purchaseRequest->update(['submitted_at' => now()]);
            }

            DB::commit();

            return $purchaseRequest;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing Purchase Request
     */
    public function updatePurchaseRequest(PurchaseRequest $purchaseRequest, array $data): PurchaseRequest
    {
        if (! $purchaseRequest->canBeEdited()) {
            throw new \Exception('This purchase request cannot be edited.');
        }

        DB::beginTransaction();

        try {
            // Update purchase request
            $purchaseRequest->update([
                'keperluan' => $data['keperluan'],
                'used_for' => $data['used_for'],
                'date_of_request' => $data['date_of_request'],
                'currency' => $data['items'][0]['currency'] ?? $purchaseRequest->currency,
                'last_modified_by' => Auth::id(),
            ]);

            // Delete existing items
            $purchaseRequest->items()->delete();

            // Create new items
            foreach ($data['items'] as $index => $itemData) {
                PrItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'item_order' => $index + 1,
                    'item_name' => $itemData['item_name'],
                    'brand_name' => $itemData['brand_name'] ?? null,
                    'item_description' => $itemData['item_description'] ?? null,
                    'supplier_name' => $itemData['supplier_name'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'unit_price' => $itemData['unit_price'],
                    'currency' => $itemData['currency'],
                    'expense_department_id' => $itemData['expense_department_id'],
                ]);
            }

            // Update total amount
            $purchaseRequest->updateTotalAmount();

            // Reset approvals if not in draft
            $purchaseRequest->resetApprovals(Auth::user());

            DB::commit();

            return $purchaseRequest;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Submit an EXISTING DRAFT Purchase Request for approval
     *
     * ⚠️ CURRENTLY UNUSED - Kept for future "Save as Draft" feature
     *
     * Purpose: Transitions EXISTING draft PR from 'draft' → 'submitted' status
     * Different from: Livewire Create::submitPurchaseRequest() (creates NEW PRs)
     *
     * Current Flow: App creates PRs directly as 'submitted' (no draft step)
     * Future Use: If "Save as Draft" feature added, this submits those drafts
     *
     * @param  PurchaseRequest  $purchaseRequest  MUST be in 'draft' status
     * @return PurchaseRequest Updated PR in 'submitted' status with workflow
     *
     * @throws \Exception If PR not in submittable state
     */
    public function submitPurchaseRequest(PurchaseRequest $purchaseRequest): PurchaseRequest
    {
        if (! $purchaseRequest->canBeSubmitted()) {
            throw new \Exception('This purchase request cannot be submitted.');
        }

        DB::beginTransaction();

        try {
            // Update status to submitted
            $purchaseRequest->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            // Create approval workflow
            $this->workflowService->createWorkflow($purchaseRequest);

            DB::commit();

            return $purchaseRequest;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Resubmit a rejected Purchase Request (reset workflow)
     */
    public function resubmitPurchaseRequest(PurchaseRequest $purchaseRequest): PurchaseRequest
    {
        if ($purchaseRequest->status !== 'rejected') {
            throw new \Exception('Only rejected purchase requests can be resubmitted.');
        }

        DB::beginTransaction();

        try {
            // Preserve original submitted_at timestamp (for QR token reusability)
            $originalSubmittedAt = $purchaseRequest->submitted_at;

            // Reset the workflow (delete old approvals)
            $this->workflowService->resetWorkflow($purchaseRequest);

            // Update PR status and timestamps
            // CRITICAL: Keep original submitted_at to ensure QR tokens remain valid
            $purchaseRequest->update([
                'status' => 'submitted',
                'submitted_at' => $originalSubmittedAt ?? now(), // Reuse original timestamp
                'rejected_at' => null,
            ]);

            // Create new approval workflow (will use preserved JSON if available)
            $this->workflowService->createWorkflow($purchaseRequest);

            DB::commit();

            return $purchaseRequest;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Void a Purchase Request
     */
    public function voidPurchaseRequest(PurchaseRequest $purchaseRequest, string $reason): PurchaseRequest
    {
        if (! $purchaseRequest->canBeVoided()) {
            throw new \Exception('This purchase request cannot be voided.');
        }

        $purchaseRequest->void(Auth::user(), $reason);

        return $purchaseRequest;
    }

    /**
     * Get departments for business unit
     */
    public function getDepartments(): \Illuminate\Database\Eloquent\Collection
    {
        return Department::where('business_unit_id', session('current_business_unit_id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get validation rules for Purchase Request
     */
    public function getValidationRules(): array
    {
        return [
            'keperluan' => 'required|string|max:500',
            'used_for' => 'required|string|max:1000',
            'date_of_request' => 'required|date',
            'designated_date' => 'nullable|date|after_or_equal:date_of_request',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.brand_name' => 'nullable|string|max:255',
            'items.*.item_description' => 'nullable|string|max:1000',
            'items.*.supplier_name' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.currency' => 'required|string|in:IDR,USD,EUR',
            'items.*.expense_department_id' => 'required|exists:departments,id',
        ];
    }

    /**
     * Get workflow status for API response
     */
    public function getWorkflowStatus(PurchaseRequest $purchaseRequest): array
    {
        $approvals = $purchaseRequest->approvals()
            ->with('approver:id,name,email')
            ->orderBy('step_order')
            ->get();

        $currentStep = $purchaseRequest->currentApproval();
        $nextApprovers = $purchaseRequest->getNextApprovers();

        return [
            'status' => $purchaseRequest->status,
            'current_step' => $currentStep ? $currentStep->step_order : null,
            'current_approver' => $currentStep ? $currentStep->approver->name : null,
            'total_steps' => $approvals->count(),
            'completed_steps' => $approvals->where('status', 'approved')->count(),
            'workflow_progress' => $approvals->count() > 0
                ? ($approvals->where('status', 'approved')->count() / $approvals->count()) * 100
                : 0,
            'approvals' => $approvals->map(function ($approval) {
                return [
                    'id' => $approval->id,
                    'step_order' => $approval->step_order,
                    'approver_name' => $approval->approver->name,
                    'approver_email' => $approval->approver->email,
                    'approval_type' => $approval->approval_type,
                    'status' => $approval->status,
                    'assigned_at' => $approval->assigned_at,
                    'responded_at' => $approval->responded_at,
                    'notes' => $approval->notes,
                ];
            }),
            'next_approvers' => $nextApprovers->map(function ($approver) {
                return [
                    'id' => $approver->id,
                    'name' => $approver->name,
                    'email' => $approver->email,
                ];
            }),
        ];
    }

    /**
     * Clear dashboard cache for affected users when PR is created/updated
     * ✅ Call this after any PR mutation (create, update, delete, approve, reject)
     *
     * @param  PurchaseRequest  $pr  The purchase request that was modified
     */
    public function clearDashboardCache(PurchaseRequest $pr): void
    {
        // Clear cache for PR creator
        $this->clearUserDashboardCache($pr->user_id);

        // Clear cache for all approvers involved
        $approvers = $pr->approvals()->pluck('approver_id')->unique();
        foreach ($approvers as $approverId) {
            $this->clearUserDashboardCache($approverId);
        }

        // Clear cache for last modifier if exists
        if ($pr->last_modified_by) {
            $this->clearUserDashboardCache($pr->last_modified_by);
        }
    }

    /**
     * Clear dashboard cache for a specific user
     *
     * @param  int  $userId  The user ID whose cache should be cleared
     */
    protected function clearUserDashboardCache(int $userId): void
    {
        // Get user to access their business units
        $user = \App\Models\Core\User::find($userId);
        if (! $user) {
            return;
        }

        // Clear all date filter variations
        $dateFilters = ['today', 'this_week', 'this_month', 'this_year', 'last_7_days', 'last_30_days', 'custom'];

        // Get all possible business unit combinations for this user
        $businessUnits = $user->businessUnits()->with('businessUnit.children')->get();
        $buIds = [];
        foreach ($businessUnits as $userBU) {
            if ($userBU->businessUnit) {
                $buIds[] = $userBU->businessUnit->id;
            }
        }

        if (empty($buIds)) {
            return;
        }

        $buHash = md5(implode(',', $buIds));

        // Clear stats cache for all date filters
        foreach ($dateFilters as $filter) {
            $dates = $this->getFilterDates($filter);
            $key = sprintf(
                'dashboard.stats.u%s.bu%s.f%s.d%s-%s',
                $userId,
                $buHash,
                $filter,
                $dates['start'],
                $dates['end']
            );
            Cache::forget($key);
        }

        // Clear activities cache
        Cache::forget(sprintf('dashboard.activities.u%s.bu%s', $userId, $buHash));

        // Clear chart cache
        foreach ($dateFilters as $filter) {
            $dates = $this->getFilterDates($filter);
            $key = sprintf(
                'dashboard.chart.bu%s.f%s.d%s-%s',
                $buHash,
                $filter,
                $dates['start'],
                $dates['end']
            );
            Cache::forget($key);
        }

        // Clear business units cache
        Cache::forget(sprintf('dashboard.business_units.u%s', $userId));

        \Log::info("✅ Dashboard cache cleared for user {$userId}");
    }

    /**
     * Get start and end dates for a given filter
     * Helper method for cache key generation
     */
    protected function getFilterDates(string $filter): array
    {
        return match ($filter) {
            'today' => [
                'start' => now()->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ],
            'this_week' => [
                'start' => now()->startOfWeek()->format('Y-m-d'),
                'end' => now()->endOfWeek()->format('Y-m-d'),
            ],
            'this_month' => [
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end' => now()->endOfMonth()->format('Y-m-d'),
            ],
            'this_year' => [
                'start' => now()->startOfYear()->format('Y-m-d'),
                'end' => now()->endOfYear()->format('Y-m-d'),
            ],
            'last_7_days' => [
                'start' => now()->subDays(7)->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ],
            'last_30_days' => [
                'start' => now()->subDays(30)->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ],
            default => [
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end' => now()->endOfMonth()->format('Y-m-d'),
            ],
        };
    }
}
