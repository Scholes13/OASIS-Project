<?php

namespace App\Models\Modules\Activity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubActivity extends Model
{
    protected $table = 'employee_sub_activities';

    protected $fillable = [
        'activity_type_id',
        'code',
        'name',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the parent activity type
     */
    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id');
    }

    /**
     * Get employee tasks using this sub-activity
     */
    public function employeeTasks(): HasMany
    {
        return $this->hasMany(EmployeeTask::class, 'sub_activity_id');
    }

    /**
     * Scope: Get only active sub-activities
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by activity type
     */
    public function scopeForActivityType($query, int $activityTypeId)
    {
        return $query->where('activity_type_id', $activityTypeId);
    }

    /**
     * Scope: Order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
