<?php

namespace App\Models\Core;

use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\SubActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $business_unit_id
 * @property string $code
 * @property string $name
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Position> $activePositions
 * @property-read int|null $active_positions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $activeUsers
 * @property-read int|null $active_users_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read BusinessUnit $businessUnit
 * @property-read string $full_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, NumberSequence> $numberSequences
 * @property-read int|null $number_sequences_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Position> $positions
 * @property-read int|null $positions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserBusinessUnit> $userBusinessUnits
 * @property-read int|null $user_business_units_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read int|null $users_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department active()
 * @method static \Database\Factories\DepartmentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department forBusinessUnit($businessUnitId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereBusinessUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Department extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'business_unit_id',
        'code',
        'name',
        'is_active',
        'is_purchasing_department',
        'default_purchasing_admin_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_purchasing_department' => 'boolean',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\DepartmentFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $department) {
            $department->ensureDefaultPositions();
        });
    }

    /**
     * Get the business unit that owns this department
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    /**
     * Get the department head
     */
    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_id');
    }

    /**
     * Get the purchasing admin for this department
     */
    public function purchasingAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_purchasing_admin_id');
    }

    /**
     * Get positions in this department
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    /**
     * Get active positions in this department
     */
    public function activePositions(): HasMany
    {
        return $this->positions()->where('is_active', true);
    }

    /**
     * Get users in this department
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'primary_department_id');
    }

    /**
     * Get active users in this department
     */
    public function activeUsers(): HasMany
    {
        return $this->users()->where('is_active', true);
    }

    /**
     * Get user business unit assignments for this department
     */
    public function userBusinessUnits(): HasMany
    {
        return $this->hasMany(UserBusinessUnit::class);
    }

    /**
     * Get number sequences for this department
     */
    public function numberSequences(): HasMany
    {
        return $this->hasMany(NumberSequence::class);
    }

    /**
     * Get the default purchasing admin for this department
     */
    public function defaultPurchasingAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_purchasing_admin_id');
    }

    /**
     * Get purchasing admins in this department
     */
    public function purchasingAdmins()
    {
        return $this->userBusinessUnits()
            ->where('is_purchasing_admin', true)
            ->where('is_active', true)
            ->with('user');
    }

    /**
     * Get admin tasks for this department
     */
    public function adminTasks(): HasMany
    {
        return $this->hasMany(\App\Models\Modules\Purchasing\Admin\AdminTask::class);
    }

    /**
     * Get activity types assigned to this department
     */
    public function activityTypes(): BelongsToMany
    {
        return $this->belongsToMany(ActivityType::class, 'department_activity_types')
            ->withPivot(['is_default', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Get active activity types for this department, ordered
     */
    public function activeActivityTypes(): BelongsToMany
    {
        return $this->activityTypes()->where('is_active', true);
    }

    /**
     * Get sub-activities assigned to this department
     */
    public function subActivities(): BelongsToMany
    {
        return $this->belongsToMany(SubActivity::class, 'department_sub_activities')
            ->withPivot(['is_default', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Get active sub-activities for this department, ordered
     */
    public function activeSubActivities(): BelongsToMany
    {
        return $this->subActivities()->where('is_active', true);
    }

    /**
     * Ensure default hierarchy positions exist for the department.
     */
    public function ensureDefaultPositions(): void
    {
        $positions = [
            [
                'code' => 'EXEC_'.strtoupper($this->code),
                'name' => 'Executive of '.$this->name,
                'level' => 'hod',
                'access_level' => 'executive',
                'hierarchy_level' => 0,
            ],
            [
                'code' => 'HOD_'.strtoupper($this->code),
                'name' => 'Head of '.$this->name,
                'level' => 'hod',
                'access_level' => 'department_head',
                'hierarchy_level' => 1,
            ],
            [
                'code' => 'LEAD_'.strtoupper($this->code),
                'name' => 'Leader of '.$this->name,
                'level' => 'leader',
                'access_level' => 'team_leader',
                'hierarchy_level' => 2,
            ],
            [
                'code' => 'STAFF_'.strtoupper($this->code),
                'name' => 'Staff of '.$this->name,
                'level' => 'staff',
                'access_level' => 'staff',
                'hierarchy_level' => 3,
            ],
        ];

        foreach ($positions as $position) {
            Position::firstOrCreate(
                [
                    'department_id' => $this->id,
                    'code' => $position['code'],
                ],
                [
                    'name' => $position['name'],
                    'level' => $position['level'],
                    'access_level' => $position['access_level'],
                    'hierarchy_level' => $position['hierarchy_level'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Scope for active departments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for departments in a specific business unit
     */
    public function scopeForBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    /**
     * Get full department name with business unit
     */
    public function getFullNameAttribute(): string
    {
        // Add null safety check for businessUnit relationship
        if (! $this->businessUnit) {
            return $this->name;
        }

        return $this->businessUnit->name.' - '.$this->name;
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
