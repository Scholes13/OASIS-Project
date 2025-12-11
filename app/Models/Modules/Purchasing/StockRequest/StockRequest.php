<?php

namespace App\Models\Modules\Purchasing\StockRequest;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\NumberSequence;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockRequest extends Model
{
    use LogsActivity;

    protected $table = 'stock_requests';

    protected $fillable = [
        'st_number',
        'business_unit_id',
        'department_id',
        'user_id',
        'sequence_id',
        'purpose',
        'date_of_request',
        'expected_date',
        'status',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'voided_at',
        'offline_approved_at',
        'offline_approved_by',
        'offline_approval_notes',
        'approval_workflow',
        'is_sequential_approval',
        'rejection_notes',
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
        'offline_approved_at' => 'datetime',
        'approval_workflow' => 'array',
        'is_sequential_approval' => 'boolean',
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
     * Get the user who created this stock request
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
     * Get the user who last modified this stock request
     */
    public function lastModifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    /**
     * Get stock request items
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockItem::class)->orderBy('item_order');
    }

    /**
     * Get approval steps for this stock request
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(StockApproval::class)->orderBy('step_order');
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
     * Scope for draft stock requests
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for submitted stock requests
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Scope for approved stock requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected stock requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for voided stock requests
     */
    public function scopeVoided($query)
    {
        return $query->where('status', 'voided');
    }

    /**
     * Scope by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope by department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date_of_request', [$startDate, $endDate]);
    }

    /**
     * Check if stock request is editable
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    /**
     * Check if stock request can be submitted
     */
    public function canBeSubmitted(): bool
    {
        return $this->status === 'draft' && $this->items()->count() > 0;
    }

    /**
     * Check if stock request can be voided
     */
    public function canBeVoided(): bool
    {
        return in_array($this->status, ['draft', 'submitted', 'in_approval']);
    }

    /**
     * Check if stock request was approved offline
     */
    public function isOfflineApproved(): bool
    {
        return $this->offline_approved_at !== null;
    }

    /**
     * Get the user who approved this stock request offline
     */
    public function offlineApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'offline_approved_by');
    }

    /**
     * Get approval progress
     */
    public function getApprovalProgress(): array
    {
        $total = $this->approvals()->count();
        $approved = $this->approvals()->where('status', 'approved')->count();

        return [
            'total' => $total,
            'approved' => $approved,
            'percentage' => $total > 0 ? round(($approved / $total) * 100) : 0,
        ];
    }

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'st_number',
                'status',
                'purpose',
                'date_of_request',
                'expected_date',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
