<?php

namespace App\Models\Core;

use App\Models\Modules\Activity\ActivityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot model untuk relasi Department - ActivityType
 *
 * @property int $id
 * @property int $department_id
 * @property int $activity_type_id
 * @property bool $is_default
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Department $department
 * @property-read ActivityType $activityType
 */
class DepartmentActivityType extends Model
{
    protected $table = 'department_activity_types';

    protected $fillable = [
        'department_id',
        'activity_type_id',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }
}
