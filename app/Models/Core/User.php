<?php

namespace App\Models\Core;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Modules\Purchasing\PurchaseRequest\PrApproval;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
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
        'last_active_business_unit_id',
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
     * Get the last active business unit
     */
    public function lastActiveBusinessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class, 'last_active_business_unit_id');
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
     * ========================================
     * Sales CRM Relationships (v2.5)
     * ========================================
     */

    /**
     * Get sales activities created by this user
     */
    public function salesActivities(): HasMany
    {
        return $this->hasMany(\App\Models\Modules\SalesCrm\Activity::class);
    }

    /**
     * Get contacts assigned to this user
     */
    public function assignedContacts(): HasMany
    {
        return $this->hasMany(\App\Models\Modules\SalesCrm\Contact::class, 'assigned_to');
    }

    /**
     * Get contacts created by this user
     */
    public function createdContacts(): HasMany
    {
        return $this->hasMany(\App\Models\Modules\SalesCrm\Contact::class, 'created_by');
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
     * Get user's access level, optionally scoped to a specific business unit.
     *
     * When $businessUnitId is provided, looks up the user's position in that
     * specific BU via user_business_units. This supports scenarios where a user
     * is HOD in one BU but C-Level/Director in another.
     *
     * @param  int|null  $businessUnitId  Scope access level to this BU context
     */
    public function getAccessLevel(?int $businessUnitId = null): string
    {
        if ($this->isSuperAdmin()) {
            return 'super_admin';
        }

        // If BU context provided, look up position in that specific BU
        $position = $businessUnitId
            ? $this->getPositionInBusinessUnit($businessUnitId)
            : $this->primaryPosition;

        if ($position && $position->access_level === 'executive') {
            return 'executive';
        }

        if ($position && $position->access_level === 'general_manager') {
            return 'general_manager';
        }

        if ($this->isGeneralManager()) {
            return 'general_manager';
        }

        // Check position level first (c_level, hod, leader, staff)
        if ($position && $position->level) {
            switch ($position->level) {
                case 'c_level':
                    return 'executive';
                case 'hod':
                    return 'department_head';
                case 'leader':
                    return 'team_leader';
                case 'staff':
                    return 'staff';
            }
        }

        // Fallback to access_level if level is not set
        if ($position && $position->access_level && $position->access_level !== 'staff') {
            return $position->access_level;
        }

        return 'staff';
    }

    /**
     * Get user's position in a specific business unit.
     *
     * Looks up user_business_units for the given BU and returns the
     * associated Position model. Falls back to primaryPosition if
     * no BU-specific assignment is found.
     */
    public function getPositionInBusinessUnit(int $businessUnitId): ?Position
    {
        $assignment = $this->activeBusinessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->with('position')
            ->first();

        if ($assignment && $assignment->position) {
            return $assignment->position;
        }

        return $this->primaryPosition;
    }

    /**
     * Check if user has top management access in any active business unit.
     *
     * Used by Gates as the canonical check for BOD/Director-level access.
     * Queries position through user_business_units pivot — supports multi-BU
     * context where a user may be staff in BU-A but C-Level in BU-B.
     */
    public function hasTopManagementAccess(): bool
    {
        return $this->activeBusinessUnits()
            ->whereHas('position', fn ($q) => $q->topManagement())
            ->exists();
    }

    /**
     * Check if user has manager-and-above access in any active business unit.
     *
     * Used by Gates for mid-level management access (department analytics,
     * purchasing admin, etc.)
     */
    public function hasManagerAccess(): bool
    {
        return $this->activeBusinessUnits()
            ->whereHas('position', fn ($q) => $q->managerAndAbove())
            ->exists();
    }

    /**
     * Check if user has top management access in a specific parent/root BU.
     *
     * Used for cross-BU administrative access (e.g., parent BU top management
     * can access child BU purchasing admin).
     */
    public function hasTopManagementInParentBU(): bool
    {
        return $this->activeBusinessUnits()
            ->whereHas('businessUnit', fn ($q) => $q->whereNull('parent_id'))
            ->whereHas('position', fn ($q) => $q->topManagement())
            ->exists();
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

        // Use getAccessibleBusinessUnitIds() which handles hierarchy
        // This allows parent BU users (e.g., WG top management) to access child BUs
        $accessibleIds = $this->getAccessibleBusinessUnitIds();

        return in_array($businessUnitId, $accessibleIds, true);
    }

    /**
     * Cache key for the BU id => parent_id map used by the admin cascade.
     */
    public const BU_PARENT_MAP_CACHE_KEY = 'core.bu_parent_map.v1';

    /**
     * Check if user has an admin flag in the given BU or any of its ancestor BUs.
     *
     * Enables top-down cascade: admin in parent BU = admin in all descendant BUs.
     * Does NOT change user position or access level.
     *
     * Performance: 2 queries max — one for user's admin BU IDs, one for the
     * target BU's ancestor chain.  The ancestor walk runs in-memory using a
     * pre-loaded parent_id map cached via {@see Cache} so concurrent BU
     * structural changes (parent reassignment) are picked up on the next
     * request after the BusinessUnit observer invalidates the key.  We
     * deliberately avoid a method-level static so long-running workers
     * (Octane/Swoole) cannot serve stale ancestor decisions across
     * unrelated requests.
     *
     * @param  string  $flag  Column name: 'is_activity_admin' or 'is_purchasing_admin'
     * @param  int  $buId  The business unit to check access for
     */
    public function isAdminInBuOrAncestor(string $flag, int $buId): bool
    {
        // Query 1: Get all BU IDs where user has this admin flag
        $adminBuIds = $this->activeBusinessUnits()
            ->where($flag, true)
            ->pluck('business_unit_id')
            ->toArray();

        if (empty($adminBuIds)) {
            return false;
        }

        // Direct match — no ancestor walk needed
        if (in_array($buId, $adminBuIds, true)) {
            return true;
        }

        // Query 2 (cached): id => parent_id map for the BU tree.
        $buParentMap = Cache::remember(
            self::BU_PARENT_MAP_CACHE_KEY,
            now()->addMinutes(15),
            fn () => BusinessUnit::pluck('parent_id', 'id')->toArray()
        );

        // Walk up ancestor chain entirely in-memory
        $visited = [$buId];
        $currentParentId = $buParentMap[$buId] ?? null;

        while ($currentParentId) {
            if (in_array($currentParentId, $visited, true)) {
                break; // cycle detection
            }
            $visited[] = $currentParentId;

            if (in_array($currentParentId, $adminBuIds, true)) {
                return true;
            }

            $currentParentId = $buParentMap[$currentParentId] ?? null;
        }

        return false;
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
            return $this->primary_department_id === $departmentId;
        }

        if ($accessLevel === 'team_leader') {
            return $this->primary_department_id === $departmentId;
        }

        return $this->primary_department_id === $departmentId;
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

        if ($accessLevel === 'executive' || $this->hasTopManagementAccess()) {
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
     * Get user role in specific business unit.
     *
     * Returns the access level based on the user's position assignment
     * in the given business unit, not their global primary position.
     */
    public function getRoleInBusinessUnit($businessUnitId): ?string
    {
        if ($this->canAccessBusinessUnit($businessUnitId)) {
            return $this->getAccessLevel((int) $businessUnitId);
        }

        return null;
    }

    /**
     * ========================================
     * Multi-Department Context Helper Methods
     * ========================================
     */

    /**
     * Get current department ID from session, with fallback to primary
     */
    public function getCurrentDepartmentId(): ?int
    {
        return session('current_department_id') ?? $this->primary_department_id;
    }

    /**
     * Resolve the best department ID for the given business unit.
     *
     * Priority: 1) current session dept if valid for BU,
     *           2) user's assignment in the BU,
     *           3) first active department in BU (last resort for super admin).
     */
    public function resolveDepartmentForBusinessUnit(int $businessUnitId): ?int
    {
        // 1. Current session department if it belongs to this BU
        $currentDeptId = session('current_department_id');
        if ($currentDeptId) {
            $valid = \App\Models\Core\Department::where('id', $currentDeptId)
                ->where('business_unit_id', $businessUnitId)
                ->where('is_active', true)
                ->exists();
            if ($valid) {
                return (int) $currentDeptId;
            }
        }

        // 2. User's own assignment in this BU
        $userAssignment = $this->activeBusinessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->whereNotNull('department_id')
            ->first();
        if ($userAssignment) {
            return (int) $userAssignment->department_id;
        }

        // 3. First active department in BU (last resort for super admin)
        $fallback = \App\Models\Core\Department::where('business_unit_id', $businessUnitId)
            ->where('is_active', true)
            ->orderBy('name')
            ->first();

        return $fallback?->id;
    }

    /**
     * Get all departments user belongs to in current business unit
     */
    public function getDepartmentsInCurrentBusinessUnit(): \Illuminate\Support\Collection
    {
        $currentBusinessUnitId = session('current_business_unit_id');

        if (! $currentBusinessUnitId) {
            return collect();
        }

        return $this->businessUnits()
            ->where('business_unit_id', $currentBusinessUnitId)
            ->where('is_active', true)
            ->with('department:id,name,code')
            ->get()
            ->pluck('department')
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * Check if user has multiple departments in current business unit
     */
    public function hasMultipleDepartmentsInCurrentBusinessUnit(): bool
    {
        return $this->getDepartmentsInCurrentBusinessUnit()->count() > 1;
    }

    /**
     * ========================================
     * Sales CRM Helper Methods (v2.5)
     * ========================================
     */

    /**
     * Check if user has Sales role
     */
    public function isSales(): bool
    {
        return $this->hasRole('sales');
    }

    /**
     * Check if user can manage Sales CRM
     * (Super Admin or Admin only)
     */
    public function canManageSalesCRM(): bool
    {
        return $this->hasPermissionTo('manage_sales_crm');
    }

    /**
     * Check if user can access Sales CRM features
     */
    public function canAccessSalesCRM(): bool
    {
        return $this->hasAnyPermission([
            'view_activities',
            'view_contacts',
            'manage_sales_crm',
        ]);
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
