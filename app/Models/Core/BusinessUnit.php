<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property array<array-key, mixed>|null $numbering_config
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $parent_id
 * @property string|null $description
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property int|null $manager_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Department> $activeDepartments
 * @property-read int|null $active_departments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, NumberingModule> $activeNumberingModules
 * @property-read int|null $active_numbering_modules_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BusinessUnit> $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Department> $departments
 * @property-read int|null $departments_count
 * @property-read User|null $manager
 * @property-read \Illuminate\Database\Eloquent\Collection<int, NumberSequence> $numberSequences
 * @property-read int|null $number_sequences_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, NumberingModule> $numberingModules
 * @property-read int|null $numbering_modules_count
 * @property-read BusinessUnit|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Modules\PurchaseRequest\PurchaseRequest> $purchaseRequests
 * @property-read int|null $purchase_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserBusinessUnit> $userBusinessUnits
 * @property-read int|null $user_business_units_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read int|null $users_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit active()
 * @method static \Database\Factories\BusinessUnitFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereNumberingConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class BusinessUnit extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'logo',
        'description',
        'address',
        'phone',
        'email',
        'parent_id',
        'manager_id',
        'numbering_config',
        'is_active',
    ];

    protected $casts = [
        'numbering_config' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get departments for this business unit
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    /**
     * Get active departments for this business unit
     */
    public function activeDepartments(): HasMany
    {
        return $this->departments()->where('is_active', true);
    }

    /**
     * Get numbering modules for this business unit
     */
    public function numberingModules(): HasMany
    {
        return $this->hasMany(NumberingModule::class);
    }

    /**
     * Get active numbering modules for this business unit
     */
    public function activeNumberingModules(): HasMany
    {
        return $this->numberingModules()->where('is_active', true);
    }

    /**
     * Get user business unit assignments
     */
    public function userBusinessUnits(): HasMany
    {
        return $this->hasMany(UserBusinessUnit::class);
    }

    /**
     * Get users assigned to this business unit
     */
    public function users()
    {
        return $this->hasManyThrough(
            User::class,
            UserBusinessUnit::class,
            'business_unit_id', // Foreign key on UserBusinessUnit table
            'id', // Foreign key on User table
            'id', // Local key on BusinessUnit table
            'user_id' // Local key on UserBusinessUnit table
        );
    }

    /**
     * Get purchase requests for this business unit
     */
    public function purchaseRequests()
    {
        return $this->hasMany(\App\Models\Modules\PurchaseRequest\PurchaseRequest::class, 'business_unit_id');
    }

    /**
     * Get parent business unit
     */
    public function parent()
    {
        return $this->belongsTo(BusinessUnit::class, 'parent_id');
    }

    /**
     * Get child business units
     */
    public function children(): HasMany
    {
        return $this->hasMany(BusinessUnit::class, 'parent_id');
    }

    /**
     * Get manager of this business unit
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get all descendant business units (recursive)
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Check if this business unit is a parent of given business unit
     * Includes cycle detection to prevent infinite recursion
     */
    public function isParentOf(BusinessUnit $businessUnit, array $visited = []): bool
    {
        // Cycle detection: prevent infinite recursion
        if (in_array($this->id, $visited, true)) {
            return false;
        }

        $visited[] = $this->id;

        // Direct child check
        if ($this->children()->where('id', $businessUnit->id)->exists()) {
            return true;
        }

        // Recursive check with visited tracking
        return $this->children()->get()->contains(function ($child) use ($businessUnit, $visited) {
            return $child->isParentOf($businessUnit, $visited);
        });
    }

    /**
     * Get all business units that this user can access (including children)
     * Includes cycle detection to prevent infinite loops
     */
    public function getAccessibleBusinessUnits(array $visited = []): array
    {
        // Cycle detection: prevent infinite loops
        if (in_array($this->id, $visited, true)) {
            return [];
        }

        $visited[] = $this->id;
        $accessible = [$this->id];

        foreach ($this->descendants as $descendant) {
            // Skip if already visited (circular reference)
            if (in_array($descendant->id, $visited, true)) {
                continue;
            }

            $accessible[] = $descendant->id;
            $accessible = array_merge($accessible, $descendant->getAccessibleBusinessUnits($visited));
        }

        return array_unique($accessible);
    }

    /**
     * Get number sequences for this business unit
     */
    public function numberSequences(): HasMany
    {
        return $this->hasMany(NumberSequence::class);
    }

    /**
     * Scope for active business units
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
