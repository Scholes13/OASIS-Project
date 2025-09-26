<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $department_id
 * @property string $name
 * @property string $code
 * @property string $level
 * @property string $access_level
 * @property int $hierarchy_level
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $activeUsers
 * @property-read int|null $active_users_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Department $department
 * @property-read string $full_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserBusinessUnit> $userBusinessUnits
 * @property-read int|null $user_business_units_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position byLevel($level)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position forDepartment($departmentId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position hod()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position leader()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position staff()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereHierarchyLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Position extends Model
{
    use LogsActivity;

    protected $fillable = [
        'department_id',
        'name',
        'code',
        'level',
        'hierarchy_level',
        'access_level',
        'is_active',
    ];

    protected $casts = [
        'hierarchy_level' => 'integer',
        'access_level' => 'string',
        'is_active' => 'boolean',
    ];

    /**
     * Get the department that owns this position
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get users in this position
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'primary_position_id');
    }

    /**
     * Get active users in this position
     */
    public function activeUsers(): HasMany
    {
        return $this->users()->where('is_active', true);
    }

    /**
     * Get user business unit assignments for this position
     */
    public function userBusinessUnits(): HasMany
    {
        return $this->hasMany(UserBusinessUnit::class);
    }

    /**
     * Scope for active positions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for positions in a specific department
     */
    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope for positions by level
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope for HOD positions
     */
    public function scopeHod($query)
    {
        return $query->where('level', 'hod');
    }

    /**
     * Scope for Leader positions
     */
    public function scopeLeader($query)
    {
        return $query->where('level', 'leader');
    }

    /**
     * Scope for Staff positions
     */
    public function scopeStaff($query)
    {
        return $query->where('level', 'staff');
    }

    /**
     * Get full position name with department
     */
    public function getFullNameAttribute(): string
    {
        return $this->department->name.' - '.$this->name;
    }

    /**
     * Check if this is a HOD position
     */
    public function isHod(): bool
    {
        return $this->level === 'hod';
    }

    /**
     * Check if this is a Leader position
     */
    public function isLeader(): bool
    {
        return $this->level === 'leader';
    }

    /**
     * Check if this is a Staff position
     */
    public function isStaff(): bool
    {
        return $this->level === 'staff';
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
