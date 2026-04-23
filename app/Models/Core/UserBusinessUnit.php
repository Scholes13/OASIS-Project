<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $user_id
 * @property int $business_unit_id
 * @property int $department_id
 * @property int $position_id
 * @property bool $is_primary
 * @property float $allocation_percentage
 * @property bool $is_active
 * @property array<array-key, mixed>|null $permissions
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read BusinessUnit $businessUnit
 * @property-read Department|null $department
 * @property-read Position|null $position
 * @property-read User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit forBusinessUnit($businessUnitId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit primary()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit whereBusinessUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit whereIsPrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit wherePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit wherePositionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit whereUserId($value)
 *
 * @mixin \Eloquent
 */
class UserBusinessUnit extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'business_unit_id',
        'department_id',
        'position_id',
        'is_primary',
        'allocation_percentage',
        'is_active',
        'is_purchasing_admin',
        'is_activity_admin',
        'is_activity_report_access',
        'permissions',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'allocation_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'is_purchasing_admin' => 'boolean',
        'is_activity_admin' => 'boolean',
        'is_activity_report_access' => 'boolean',
        'permissions' => 'array',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * Get the position
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Scope for active assignments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for primary assignments
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope for specific business unit
     */
    public function scopeForBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    /**
     * Check if user has specific permission
     * ✅ FIX: Added strict comparison to prevent type juggling
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];

        return in_array($permission, $permissions, true);
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
