<?php

namespace App\Models\Modules\Activity;

use App\Models\Core\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityType extends Model
{
    protected $table = 'employee_activity_types';

    protected $fillable = [
        'code',
        'name',
        'color',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get sub-activities for this activity type
     */
    public function subActivities(): HasMany
    {
        return $this->hasMany(SubActivity::class, 'activity_type_id');
    }

    /**
     * Get employee tasks for this activity type
     */
    public function employeeTasks(): HasMany
    {
        return $this->hasMany(EmployeeTask::class, 'activity_type_id');
    }

    /**
     * Get auto-log rules for this activity type
     */
    public function autoLogRules(): HasMany
    {
        return $this->hasMany(AutoLogRule::class, 'activity_type_id');
    }

    /**
     * Get departments that have this activity type assigned
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_activity_types')
            ->withPivot(['is_default', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * Scope: Get only active activity types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get active sub-activities for dropdown
     */
    public function getActiveSubActivities()
    {
        return $this->subActivities()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
