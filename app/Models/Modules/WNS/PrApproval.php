<?php

namespace App\Models\Modules\WNS;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $purchase_request_id
 * @property int $approver_id
 * @property int $step_order
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $assigned_at
 * @property \Illuminate\Support\Carbon|null $responded_at
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property bool $email_sent
 * @property \Illuminate\Support\Carbon|null $email_sent_at
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $approval_type
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read User $approver
 * @property-read string|null $formatted_due_date
 * @property-read string $status_color
 * @property-read \App\Models\Modules\WNS\PurchaseRequest $purchaseRequest
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval dueSoon()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval forApprover($approverId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval overdue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval rejected()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereApprovalType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereApproverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereAssignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereEmailSent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereEmailSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval wherePurchaseRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereRespondedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereStepOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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
    public function approve(?string $notes = null): bool
    {
        if (! $this->isPending()) {
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
        if (! $this->isPending()) {
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
        if (! $this->due_date) {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Get hours until due date
     */
    public function getHoursUntilDue(): ?int
    {
        if (! $this->due_date) {
            return null;
        }

        return now()->diffInHours($this->due_date, false);
    }

    /**
     * Get formatted due date for display
     */
    public function getFormattedDueDateAttribute(): ?string
    {
        if (! $this->due_date) {
            return null;
        }

        return $this->due_date->format('M j, Y g:i A');
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
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
