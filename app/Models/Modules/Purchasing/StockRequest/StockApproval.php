<?php

namespace App\Models\Modules\Purchasing\StockRequest;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockApproval extends Model
{
    use LogsActivity;

    protected $table = 'stock_approvals';

    protected $fillable = [
        'stock_request_id',
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
        'qr_code_path',
        'metadata',
        'task_type',
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
     * Get the stock request
     */
    public function stockRequest(): BelongsTo
    {
        return $this->belongsTo(StockRequest::class);
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
     * Scope for due soon (within 24 hours)
     */
    public function scopeDueSoon($query)
    {
        return $query->where('status', 'pending')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now(), now()->addHours(24)]);
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            'skipped' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get formatted due date
     */
    public function getFormattedDueDateAttribute(): ?string
    {
        return $this->due_date?->format('d M Y H:i');
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'notes', 'responded_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
