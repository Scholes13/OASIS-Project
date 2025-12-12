<?php

namespace App\Models\Modules\Purchasing\Admin;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $taskable_type
 * @property int $taskable_id
 * @property int $business_unit_id
 * @property int $department_id
 * @property int|null $assigned_admin_id
 * @property string $status
 * @property \Illuminate\Support\Carbon $entered_at
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property float $estimated_total_price
 * @property float|null $realized_total_price
 * @property float|null $savings_amount
 * @property float|null $savings_percentage
 * @property int|null $followup_time_minutes
 * @property int|null $completion_time_minutes
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read User|null $assignedAdmin
 * @property-read BusinessUnit $businessUnit
 * @property-read Department $department
 * @property-read Model|\Eloquent $taskable
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask forBusinessUnit($businessUnitId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask inProgress()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask whereAssignedAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask whereBusinessUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask whereCompletionTimeMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask whereEnteredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask whereEstimatedTotalPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask whereFollowupTimeMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask whereRealizedTotalPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask whereSavingsAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask whereSavingsPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask whereTaskableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask whereTaskableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminTask whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AdminTask extends Model
{
    use LogsActivity;

    protected $fillable = [
        'taskable_type',
        'taskable_id',
        'business_unit_id',
        'department_id',
        'assigned_admin_id',
        'status',
        'entered_at',
        'started_at',
        'completed_at',
        'estimated_total_price',
        'realized_total_price',
        'savings_amount',
        'savings_percentage',
        'followup_time_minutes',
        'completion_time_minutes',
        'notes',
    ];

    protected $casts = [
        'entered_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_total_price' => 'decimal:2',
        'realized_total_price' => 'decimal:2',
        'savings_amount' => 'decimal:2',
        'savings_percentage' => 'decimal:2',
        'followup_time_minutes' => 'integer',
        'completion_time_minutes' => 'integer',
    ];

    /**
     * Get the parent taskable model (PR or ST)
     */
    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

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
     * Get the assigned admin
     */
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    /**
     * Scope for pending tasks
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending_followup');
    }

    /**
     * Scope for in-progress tasks
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope for completed tasks
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'done');
    }

    /**
     * Scope for tasks in a specific business unit
     */
    public function scopeForBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    /**
     * Check if task has exceeded follow-up SLA
     */
    public function hasExceededFollowupSla(): bool
    {
        if ($this->status !== 'pending_followup') {
            return false;
        }

        $settings = \App\Models\Modules\Purchasing\Admin\SlaSettings::where('business_unit_id', $this->business_unit_id)
            ->orWhereNull('business_unit_id')
            ->orderBy('business_unit_id', 'desc')
            ->first();

        if (! $settings) {
            return false;
        }

        $slaDeadline = $this->entered_at->copy()->addHours($settings->followup_sla_hours);

        return now()->isAfter($slaDeadline);
    }

    /**
     * Check if task has exceeded completion SLA
     */
    public function hasExceededCompletionSla(): bool
    {
        if ($this->status !== 'in_progress' || ! $this->started_at) {
            return false;
        }

        $settings = \App\Models\Modules\Purchasing\Admin\SlaSettings::where('business_unit_id', $this->business_unit_id)
            ->orWhereNull('business_unit_id')
            ->orderBy('business_unit_id', 'desc')
            ->first();

        if (! $settings) {
            return false;
        }

        $slaDeadline = $this->started_at->copy()->addHours($settings->completion_sla_hours);

        return now()->isAfter($slaDeadline);
    }

    /**
     * Check if task has any SLA violation
     */
    public function hasSlViolation(): bool
    {
        return $this->hasExceededFollowupSla() || $this->hasExceededCompletionSla();
    }

    /**
     * Get SLA status badge color
     */
    public function getSlaStatusColor(): string
    {
        if ($this->hasExceededFollowupSla() || $this->hasExceededCompletionSla()) {
            return 'red';
        }

        return 'gray';
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
