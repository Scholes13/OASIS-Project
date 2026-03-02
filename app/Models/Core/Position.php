<?php

namespace App\Models\Core;

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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $activeUsers
 * @property-read int|null $active_users_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Department $department
 * @property-read string $full_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserBusinessUnit> $userBusinessUnits
 * @property-read int|null $user_business_units_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
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
     * Scope for C-Level positions
     */
    public function scopeCLevel($query)
    {
        return $query->where('level', 'c_level');
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
     * Scope for top management positions (C-Level + Executive access).
     *
     * Used by authorization Gates as single source of truth for
     * determining BOD/Director-level access across all modules.
     *
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeTopManagement($query)
    {
        return $query->where(function ($q) {
            $q->where('level', 'c_level')
                ->orWhere('access_level', 'executive');
        });
    }

    /**
     * Scope for manager-and-above positions (C-Level + HOD + General Manager).
     *
     * Used by authorization Gates for mid-level management access
     * such as department analytics and purchasing admin.
     *
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeManagerAndAbove($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('level', ['c_level', 'hod'])
                ->orWhereIn('access_level', ['executive', 'general_manager', 'department_head']);
        });
    }

    /**
     * Check if this position qualifies as top management.
     */
    public function isTopManagement(): bool
    {
        return $this->level === 'c_level' || $this->access_level === 'executive';
    }

    /**
     * Check if this position qualifies as manager-and-above.
     */
    public function isManagerAndAbove(): bool
    {
        return in_array($this->level, ['c_level', 'hod'])
            || in_array($this->access_level, ['executive', 'general_manager', 'department_head']);
    }

    /**
     * Get full position name with department
     * ✅ OPTIMIZED: Added null safety to prevent N+1 query crash
     */
    public function getFullNameAttribute(): string
    {
        // Use null coalescing to prevent N+1 query crash if department not eager-loaded
        $departmentName = $this->department?->name ?? 'Unknown Department';

        return $departmentName.' - '.$this->name;
    }

    /**
     * Check if this is a C-Level / Director position
     */
    public function isCLevel(): bool
    {
        return $this->level === 'c_level';
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
