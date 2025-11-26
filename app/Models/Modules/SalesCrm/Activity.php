<?php

namespace App\Models\Modules\SalesCrm;

use App\Models\Core\BusinessUnit;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Activity extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'business_unit_id',
        'user_id',
        'contact_id',
        'activity_date',
        'activity_type',
        'title',
        'department',
        'pic_name',
        'pic_phone',
        'office_address',
        'description',
        'location',
        'start_time',
        'end_time',
        'duration_minutes',
        'result',
        'notes',
        'status',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'activity_type', 'activity_date', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the user who created this activity (sales person)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the business unit this activity belongs to
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    /**
     * Get the contact linked to this activity (optional)
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the company visit history record (if company visited)
     */
    public function companyVisitHistory(): HasOne
    {
        return $this->hasOne(CompanyVisitHistory::class);
    }

    /**
     * Scope: Get activities for current business unit
     */
    public function scopeForBusinessUnit($query, int $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    /**
     * Scope: Get activities for specific user (sales person)
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Get completed activities
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Get activities for specific contact
     */
    public function scopeForContact($query, int $contactId)
    {
        return $query->where('contact_id', $contactId);
    }

    /**
     * Scope: Get activities by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope: Get activities within date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('activity_date', [$startDate, $endDate]);
    }

    /**
     * Calculate duration in minutes from start and end time
     */
    public function calculateDuration(): int
    {
        if (! $this->start_time || ! $this->end_time) {
            return 0;
        }

        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);

        return $end->diffInMinutes($start);
    }

    /**
     * Check if activity is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if activity is planned
     */
    public function isPlanned(): bool
    {
        return $this->status === 'planned';
    }

    /**
     * Check if activity has contact
     */
    public function hasContact(): bool
    {
        return ! is_null($this->contact_id);
    }

    /**
     * Get activity type label
     */
    public function getActivityTypeLabel(): string
    {
        return match ($this->activity_type) {
            'call' => 'Phone Call',
            'visit' => 'Site Visit',
            'meeting' => 'Meeting',
            'blitz' => 'Blitz',
            'follow_up' => 'Follow Up',
            'other' => 'Other',
            default => ucfirst($this->activity_type),
        };
    }

    /**
     * Get result badge color
     */
    public function getResultBadgeColor(): string
    {
        return match ($this->result) {
            'success' => 'green',
            'follow_up_needed' => 'yellow',
            'no_answer' => 'gray',
            'rejected' => 'red',
            'other' => 'blue',
            default => 'gray',
        };
    }
}
