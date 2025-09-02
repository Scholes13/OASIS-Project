<?php

namespace App\Models\Modules\WNS;

use App\Models\BusinessUnit;
use App\Models\Department;
use App\Models\User;
use App\Models\NumberSequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class PurchaseRequest extends Model
{
    use LogsActivity;

    protected $table = 'purchase_requests';

    protected $fillable = [
        'pr_number',
        'business_unit_id',
        'department_id',
        'user_id',
        'sequence_id',
        'used_for',
        'keperluan', // Specific needs or requirements field
        'date_of_request', // Auto dari PR number creation
        'expected_date', // User input - kapan barang dibutuhkan
        'status',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'voided_at',
        'approval_workflow',
        'is_sequential_approval',
        'total_amount',
        'currency',
        'edit_history',
        'last_modified_by',
    ];

    protected $casts = [
        'date_of_request' => 'date',
        'expected_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'voided_at' => 'datetime',
        'approval_workflow' => 'array',
        'is_sequential_approval' => 'boolean',
        'total_amount' => 'decimal:2',
        'edit_history' => 'array',
    ];

    /**
     * Get the business unit
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    /**
     * Get the department
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user who created this PR
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the number sequence used
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(NumberSequence::class, 'sequence_id');
    }

    /**
     * Get the user who last modified this PR
     */
    public function lastModifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    /**
     * Get PR items
     */
    public function items(): HasMany
    {
        return $this->hasMany(PrItem::class)->orderBy('item_order');
    }

    /**
     * Get PR approvals
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(PrApproval::class)->orderBy('step_order');
    }

    /**
     * Get pending approvals
     */
    public function pendingApprovals(): HasMany
    {
        return $this->approvals()->where('status', 'pending');
    }

    /**
     * Get current approval step
     */
    public function currentApproval()
    {
        return $this->approvals()
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->first();
    }

    /**
     * Scope for specific status
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for draft PRs
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for submitted PRs
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Scope for approved PRs
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected PRs
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for voided PRs
     */
    public function scopeVoided($query)
    {
        return $query->where('status', 'voided');
    }

    /**
     * Scope for PRs by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for PRs by department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope for PRs in date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date_of_request', [$startDate, $endDate]);
    }

    /**
     * Check if PR can be edited
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    /**
     * Check if PR can be submitted
     */
    public function canBeSubmitted(): bool
    {
        return $this->status === 'draft' && $this->items()->count() > 0;
    }

    /**
     * Check if PR can be approved
     */
    public function canBeApproved(): bool
    {
        return in_array($this->status, ['submitted', 'in_approval']);
    }

    /**
     * Check if PR can be voided
     */
    public function canBeVoided(): bool
    {
        return !in_array($this->status, ['voided', 'approved']);
    }

    /**
     * Submit the PR for approval
     */
    public function submit(): bool
    {
        if (!$this->canBeSubmitted()) {
            return false;
        }

        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // Create approval workflow
        $this->createApprovalWorkflow();

        return true;
    }

    /**
     * Approve the PR
     */
    public function approve(User $approver, string $notes = null): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $currentApproval = $this->currentApproval();
        if (!$currentApproval || $currentApproval->approver_id !== $approver->id) {
            return false;
        }

        $currentApproval->update([
            'status' => 'approved',
            'notes' => $notes,
            'responded_at' => now(),
        ]);

        // Check if all approvals are completed
        $pendingCount = $this->pendingApprovals()->count();
        
        if ($pendingCount === 0) {
            $this->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);
        } else {
            $this->update(['status' => 'in_approval']);
        }

        return true;
    }

    /**
     * Reject the PR
     */
    public function reject(User $approver, string $notes): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $currentApproval = $this->currentApproval();
        if (!$currentApproval || $currentApproval->approver_id !== $approver->id) {
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

    /**
     * Void the PR
     */
    public function void(User $user, string $reason): bool
    {
        if (!$this->canBeVoided()) {
            return false;
        }

        $this->update([
            'status' => 'voided',
            'voided_at' => now(),
            'last_modified_by' => $user->id,
        ]);

        // Add void reason to edit history
        $this->addToEditHistory('voided', $reason, $user->id);

        // Trigger resequencing if needed
        $this->triggerResequencing();

        return true;
    }

    /**
     * Calculate total amount from items
     */
    public function calculateTotalAmount(): float
    {
        return $this->items()->sum('total_price');
    }

    /**
     * Update total amount
     */
    public function updateTotalAmount(): void
    {
        $this->update(['total_amount' => $this->calculateTotalAmount()]);
    }

    /**
     * Add entry to edit history
     */
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

    /**
     * Reset approvals when items are edited
     */
    public function resetApprovals(User $user): void
    {
        if ($this->status !== 'draft') {
            // Reset to draft and clear approval data
            $this->update([
                'status' => 'draft',
                'submitted_at' => null,
                'approved_at' => null,
                'rejected_at' => null,
            ]);

            // Remove all approvals
            $this->approvals()->delete();

            // Add to edit history
            $this->addToEditHistory('reset_approvals', 'Items modified - approvals reset', $user->id);
        }
    }

    /**
     * Create approval workflow
     */
    protected function createApprovalWorkflow(): void
    {
        // Prepare data for workflow evaluation
        $workflowData = [
            'total_amount' => $this->total_amount,
            'department_code' => $this->department->code,
            'business_unit_id' => $this->business_unit_id,
        ];
        
        // Find matching workflow based on conditions
        $workflow = \App\Models\ApprovalWorkflow::getWorkflowForConditions(
            $this->business_unit_id,
            'purchase_request',
            $workflowData
        );
        
        // Fallback to default workflow if no match
        if (!$workflow) {
            $workflow = \App\Models\ApprovalWorkflow::getDefaultWorkflow(
                $this->business_unit_id,
                'purchase_request'
            );
        }
        
        // Store workflow and create approval steps
        if ($workflow) {
            $this->update([
                'approval_workflow' => $workflow->approval_steps,
                'is_sequential_approval' => $workflow->is_sequential,
                'status' => 'in_approval'
            ]);
            
            // Create PrApproval records for each step
            foreach ($workflow->approval_steps as $step) {
                PrApproval::create([
                    'purchase_request_id' => $this->id,
                    'approver_id' => $step['approver_id'],
                    'step_order' => $step['step'],
                    'status' => 'pending',
                    'assigned_at' => now(),
                    'due_date' => now()->addDays(3), // Default 3-day deadline
                ]);
            }
        } else {
            throw new \Exception('No approval workflow found for this purchase request');
        }
    }

    /**
     * Trigger resequencing for voided PR
     */
    protected function triggerResequencing(): void
    {
        // This would dispatch a job to handle resequencing
        // For now, we'll add the number to void list
        if ($this->sequence) {
            // Extract number from PR number (e.g., from "PR.GA/2025/07/080" get 80)
            $parts = explode('/', $this->pr_number);
            if (count($parts) === 4) {
                $number = (int) $parts[3];
                $this->sequence->addVoidNumber($number);
            }
        }
    }

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
