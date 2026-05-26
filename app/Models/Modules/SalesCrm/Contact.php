<?php

namespace App\Models\Modules\SalesCrm;

use App\Models\Core\BusinessUnit;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @deprecated SalesCrm module is archived. Retained on disk for reference
 *             only. See docs/exec_plans.md for archive log.
 */
class Contact extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'business_unit_id',
        'created_by',
        'assigned_to',
        'code',
        'name',
        'email',
        'phone',
        'mobile',
        'birth_date',
        'company',
        'department',
        'position',
        'social_media',
        'status',
        'category',
        'address',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'social_media' => 'array',
    ];

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'company', 'status', 'category'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the business unit this contact belongs to
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    /**
     * Get the user who created this contact
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user this contact is assigned to (current owner)
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get all activities related to this contact
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Get the source information for this contact
     */
    public function source(): HasOne
    {
        return $this->hasOne(ContactSource::class);
    }

    /**
     * Get the last activity for this contact
     */
    public function lastActivity(): HasOne
    {
        return $this->hasOne(Activity::class)->latest('activity_date');
    }

    /**
     * Get company visit history for this contact's company
     * NOTE: Removed department constraint from relationship definition
     * Use query constraints when needed: $contact->companyVisitHistory()->where('department', $contact->department)->first()
     */
    public function companyVisitHistory(): HasOne
    {
        return $this->hasOne(CompanyVisitHistory::class, 'company_name', 'company');
    }

    /**
     * Scope: Get contacts for current business unit
     */
    public function scopeForBusinessUnit($query, int $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    /**
     * Scope: Get contacts assigned to specific user
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope: Get active contacts
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Search contacts by name, company, or phone
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('company', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    /**
     * Scope: Filter by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Check if contact is from activity
     */
    public function isFromActivity(): bool
    {
        return $this->source?->source_type === 'activity';
    }

    /**
     * Check if contact is manual entry
     */
    public function isManualEntry(): bool
    {
        return $this->source?->source_type === 'manual';
    }

    /**
     * Get source badge label
     */
    public function getSourceBadge(): string
    {
        return match ($this->source?->source_type) {
            'activity' => ucfirst($this->source->activity_type ?? 'Activity'),
            'manual' => 'Manual Input',
            'import' => 'Imported',
            'referral' => 'Referral',
            'website' => 'Website',
            'event' => 'Event',
            default => 'Unknown',
        };
    }

    /**
     * Get category badge color
     */
    public function getCategoryBadgeColor(): string
    {
        return match ($this->category) {
            'lead' => 'blue',
            'prospect' => 'yellow',
            'customer' => 'green',
            'partner' => 'purple',
            default => 'gray',
        };
    }

    /**
     * Get full name with company
     */
    public function getFullNameAttribute(): string
    {
        return $this->company
            ? "{$this->name} ({$this->company})"
            : $this->name;
    }

    /**
     * Get social media link
     */
    public function getSocialMediaLink(string $platform): ?string
    {
        return $this->social_media[$platform] ?? null;
    }

    /**
     * Check if contact has activities
     */
    public function hasActivities(): bool
    {
        return $this->activities()->exists();
    }

    /**
     * Get total activities count
     */
    public function getTotalActivitiesAttribute(): int
    {
        return $this->activities()->count();
    }
}
