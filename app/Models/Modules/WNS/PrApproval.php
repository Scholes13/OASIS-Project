<?php

namespace App\Models\Modules\WNS;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class PrApproval extends Model
{
    use LogsActivity;

    protected $table = 'pr_approvals';

    protected $fillable = [
        'purchase_request_id',
        'approver_id',
        'step_order',
        'approval_type',
        'status',
        'notes',
        'assigned_at',
        'responded_at',
        'due_date',
        'email_sent',
        'email_sent_at',
        'metadata',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'assigned_at' => 'datetime',
        'responded_at' => 'datetime',
        'due_date' => 'datetime',
        'email_sent' => 'boolean',
        'email_sent_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the purchase request
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * Get the approver
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Scope for pending approvals
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved approvals
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected approvals
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for specific approver
     */
    public function scopeForApprover($query, $approverId)
    {
        return $query->where('approver_id', $approverId);
    }

    /**
     * Scope for overdue approvals
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', now());
    }

    /**
     * Scope for approvals due soon (within next 24 hours)
     */
    public function scopeDueSoon($query)
    {
        return $query->where('status', 'pending')
                    ->whereNotNull('due_date')
                    ->whereBetween('due_date', [now(), now()->addDay()]);
    }

    /**
     * Check if approval is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if approval is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if approval is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if approval is overdue
     */
    public function isOverdue(): bool
    {
        return $this->isPending() && 
               $this->due_date && 
               $this->due_date->isPast();
    }

    /**
     * Check if approval is due soon
     */
    public function isDueSoon(): bool
    {
        return $this->isPending() && 
               $this->due_date && 
               $this->due_date->isBetween(now(), now()->addDay());
    }

    /**
     * Approve this step
     */
    public function approve(string $notes = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'notes' => $notes,
            'responded_at' => now(),
        ]);

        return true;
    }

    /**
     * Reject this step
     */
    public function reject(string $notes): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'notes' => $notes,
            'responded_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark email as sent
     */
    public function markEmailSent(): void
    {
        $this->update([
            'email_sent' => true,
            'email_sent_at' => now(),
        ]);
    }

    /**
     * Get days until due date
     */
    public function getDaysUntilDue(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Get hours until due date
     */
    public function getHoursUntilDue(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        return now()->diffInHours($this->due_date, false);
    }

    /**
     * Get formatted due date for display
     */
    public function getFormattedDueDateAttribute(): ?string
    {
        if (!$this->due_date) {
            return null;
        }

        return $this->due_date->format('M j, Y g:i A');
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'approved' => 'green', 
            'rejected' => 'red',
            'skipped' => 'gray',
            default => 'gray'
        };
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
