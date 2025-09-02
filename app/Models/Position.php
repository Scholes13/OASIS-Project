<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Position extends Model
{
    use LogsActivity;

    protected $fillable = [
        'department_id',
        'name',
        'code',
        'level',
        'hierarchy_level',
        'is_active',
    ];

    protected $casts = [
        'hierarchy_level' => 'integer',
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
        return $this->department->name . ' - ' . $this->name;
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
