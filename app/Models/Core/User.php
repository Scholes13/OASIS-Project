<?php

namespace App\Models\Core;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Modules\PurchaseRequest\PrApproval;
use App\Models\Modules\PurchaseRequest\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $phone_number
 * @property int|null $primary_department_id
 * @property int|null $primary_position_id
 * @property int|null $supervisor_id
 * @property string $global_role
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserBusinessUnit> $activeBusinessUnits
 * @property-read int|null $active_business_units_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $activeSubordinates
 * @property-read int|null $active_subordinates_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserBusinessUnit> $businessUnits
 * @property-read int|null $business_units_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PrApproval> $pendingApprovals
 * @property-read int|null $pending_approvals_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PrApproval> $prApprovals
 * @property-read int|null $pr_approvals_count
 * @property-read Department|null $primaryDepartment
 * @property-read Position|null $primaryPosition
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PurchaseRequest> $purchaseRequests
 * @property-read int|null $purchase_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $subordinates
 * @property-read int|null $subordinates_count
 * @property-read User|null $supervisor
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User active()
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User inDepartment($departmentId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereGlobalRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePrimaryDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePrimaryPositionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSupervisorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withGlobalRole($role)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'primary_department_id',
        'primary_position_id',
        'supervisor_id',
        'global_role',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\UserFactory
    {
        return \Database\Factories\UserFactory::new();
    }

    /**
     * Get the primary department
     */
    public function primaryDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'primary_department_id');
    }

    /**
     * Get the primary position
     */
    public function primaryPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'primary_position_id');
    }

    /**
     * Get the supervisor
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get subordinates
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    /**
     * Get active subordinates
     */
    public function activeSubordinates(): HasMany
    {
        return $this->subordinates()->where('is_active', true);
    }

    /**
     * Get business unit assignments
     */
    public function businessUnits(): HasMany
    {
        return $this->hasMany(UserBusinessUnit::class);
    }

    /**
     * Get active business unit assignments
     */
    public function activeBusinessUnits(): HasMany
    {
        return $this->businessUnits()->where('is_active', true);
    }

    /**
     * Get primary business unit
     */
    public function primaryBusinessUnit()
    {
        return $this->businessUnits()->where('is_primary', true)->first();
    }

    /**
     * Get purchase requests created by this user
     */
    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    /**
     * Get purchase request approvals assigned to this user
     */
    public function prApprovals(): HasMany
    {
        return $this->hasMany(PrApproval::class, 'approver_id');
    }

    /**
     * Alias for prApprovals for consistency
     */
    public function approvals(): HasMany
    {
        return $this->prApprovals();
    }

    /**
     * Get pending approvals for this user
     */
    public function pendingApprovals(): HasMany
    {
        return $this->prApprovals()->where('status', 'pending');
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for users in a specific department
     */
    public function scopeInDepartment($query, $departmentId)
    {
        return $query->where('primary_department_id', $departmentId);
    }

    /**
     * Scope for users with specific global role
     */
    public function scopeWithGlobalRole($query, $role)
    {
        return $query->where('global_role', $role);
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->global_role === 'super_admin';
    }

    /**
     * Get user's access level.
     */
    public function getAccessLevel(): string
    {
        if ($this->isSuperAdmin()) {
            return 'super_admin';
        }

        $position = $this->primaryPosition;

        if ($position && $position->access_level === 'executive') {
            return 'executive';
        }

        if ($position && $position->access_level === 'general_manager') {
            return 'general_manager';
        }

        if ($this->isGeneralManager()) {
            return 'general_manager';
        }

        if ($position && $position->access_level) {
            return $position->access_level;
        }

        if ($position) {
            switch ($position->level) {
                case 'hod':
                    return 'department_head';
                case 'leader':
                    return 'team_leader';
                default:
                    return 'staff';
            }
        }

        return 'staff';
    }

    /**
     * Determine if the user acts as a general manager for any business unit.
     */
    public function isGeneralManager(): bool
    {
        return BusinessUnit::where('manager_id', $this->id)->exists();
    }

    /**
     * Business unit IDs where the user is assigned as general manager.
     */
    public function managedBusinessUnitIds(): array
    {
        return BusinessUnit::where('manager_id', $this->id)->pluck('id')->toArray();
    }

    /**
     * Business unit IDs the user can access as general manager (manager assignment + active links).
     */
    public function generalManagerBusinessUnitIds(): array
    {
        return array_values(array_unique(array_merge(
            $this->managedBusinessUnitIds(),
            $this->activeBusinessUnits()->pluck('business_unit_id')->toArray()
        )));
    }

    /**
     * Check if user can access business unit
     */
    public function canAccessBusinessUnit($businessUnitId): bool
    {
        // Super admin can access all business units
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check if user is assigned to this business unit
        if (in_array($businessUnitId, $this->generalManagerBusinessUnitIds(), true)) {
            return true;
        }

        return $this->activeBusinessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->exists();
    }

    /**
     * Check if user can access department
     */
    public function canAccessDepartment($departmentId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $accessLevel = $this->getAccessLevel();

        if ($accessLevel === 'executive') {
            $businessUnitIds = $this->getAccessibleBusinessUnitIds();

            return \App\Models\Core\Department::where('id', $departmentId)
                ->whereIn('business_unit_id', $businessUnitIds)
                ->exists();
        }

        if ($accessLevel === 'general_manager') {
            $businessUnitIds = $this->generalManagerBusinessUnitIds();

            if (! empty($businessUnitIds)) {
                return \App\Models\Core\Department::where('id', $departmentId)
                    ->whereIn('business_unit_id', $businessUnitIds)
                    ->exists();
            }
        }

        if ($accessLevel === 'department_head') {
            return $this->primary_department_id == $departmentId;
        }

        if ($accessLevel === 'team_leader') {
            return $this->primary_department_id == $departmentId;
        }

        return $this->primary_department_id == $departmentId;
    }

    /**
     * Get users that this user can manage/view
     */
    public function getManagedUserIds(): array
    {
        if ($this->isSuperAdmin()) {
            return User::pluck('id')->toArray();
        }

        $accessLevel = $this->getAccessLevel();
        $managedIds = [$this->id]; // Always include self

        switch ($accessLevel) {
            case 'executive':
                $businessUnitIds = $this->getAccessibleBusinessUnitIds();

                if (! empty($businessUnitIds)) {
                    $departmentIds = \App\Models\Core\Department::whereIn('business_unit_id', $businessUnitIds)
                        ->pluck('id');
                    $managedIds = User::whereIn('primary_department_id', $departmentIds)
                        ->pluck('id')->toArray();
                    $managedIds[] = $this->id;
                }
                break;

            case 'general_manager':
                $businessUnitIds = $this->generalManagerBusinessUnitIds();

                if (! empty($businessUnitIds)) {
                    $departmentIds = \App\Models\Core\Department::whereIn('business_unit_id', $businessUnitIds)
                        ->pluck('id');
                    $managedIds = User::whereIn('primary_department_id', $departmentIds)
                        ->pluck('id')->toArray();
                    $managedIds[] = $this->id;
                }
                break;

            case 'department_head':
                $managedIds = User::where('primary_department_id', $this->primary_department_id)
                    ->pluck('id')->toArray();
                break;

            case 'team_leader':
                $managedIds = User::where('supervisor_id', $this->id)
                    ->pluck('id')->toArray();
                $managedIds[] = $this->id;
                break;

            case 'staff':
            default:
                $managedIds = [$this->id];
                break;
        }

        return array_values(array_unique($managedIds));
    }

    /**
     * Check if user has access to business unit
     */
    public function hasAccessToBusinessUnit($businessUnitId): bool
    {
        // Super admin has access to all business units
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (in_array($businessUnitId, $this->generalManagerBusinessUnitIds(), true)) {
            return true;
        }

        return $this->activeBusinessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get all accessible business unit IDs for this user
     */
    public function getAccessibleBusinessUnitIds(): array
    {
        // Super admin can access all business units
        if ($this->isSuperAdmin()) {
            $primaryBU = $this->primaryBusinessUnit();
            if ($primaryBU && $primaryBU->businessUnit) {
                $accessibleIds = $primaryBU->businessUnit->getAccessibleBusinessUnits();

                return array_values(array_unique($accessibleIds));
            }

            // Fallback: access to all business units
            return BusinessUnit::active()->pluck('id')->toArray();
        }

        $accessLevel = $this->getAccessLevel();

        if ($accessLevel === 'executive') {
            $ids = [];

            foreach ($this->activeBusinessUnits()->with('businessUnit')->get() as $assignment) {
                if ($assignment->businessUnit) {
                    $ids = array_merge($ids, $assignment->businessUnit->getAccessibleBusinessUnits());
                }
            }

            if ($this->isGeneralManager()) {
                $ids = array_merge($ids, $this->managedBusinessUnitIds());
            }

            return array_values(array_unique($ids));
        }

        if ($accessLevel === 'general_manager') {
            $ids = $this->generalManagerBusinessUnitIds();
            if (! empty($ids)) {
                return $ids;
            }
        }

        // Regular users can only access their assigned business units
        return $this->activeBusinessUnits()->pluck('business_unit_id')->toArray();
    }

    /**
     * Get accessible business units with their relationships
     */
    public function getAccessibleBusinessUnits()
    {
        $accessibleIds = $this->getAccessibleBusinessUnitIds();

        return BusinessUnit::whereIn('id', $accessibleIds)
            ->with(['parent', 'children', 'departments'])
            ->active()
            ->get();
    }

    /**
     * Get user role in specific business unit
     */
    public function getRoleInBusinessUnit($businessUnitId): ?string
    {
        // Since role field was removed, return access level based on position
        if ($this->canAccessBusinessUnit($businessUnitId)) {
            return $this->getAccessLevel();
        }

        return null;
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
