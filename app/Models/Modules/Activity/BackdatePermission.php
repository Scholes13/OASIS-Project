<?php

namespace App\Models\Modules\Activity;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BackdatePermission extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'department_id',
        'business_unit_id',
        'requested_date',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'granted_until',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'granted_until' => 'datetime',
    ];

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'requested_date', 'reason', 'rejection_reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'approved')
            ->where('granted_until', '>=', now());
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->status === 'approved' 
            && $this->granted_until !== null 
            && $this->granted_until->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->status === 'approved' 
            && $this->granted_until !== null 
            && $this->granted_until->isPast();
    }

    public function canBackdateTo(\DateTime|string $date): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $checkDate = is_string($date) ? \Carbon\Carbon::parse($date) : \Carbon\Carbon::instance($date);
        
        // Can backdate if the date is between requested_date and today
        return $checkDate->greaterThanOrEqualTo($this->requested_date)
            && $checkDate->lessThanOrEqualTo(now());
    }
}
