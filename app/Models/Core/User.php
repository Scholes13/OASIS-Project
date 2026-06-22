<?php

namespace App\Models\Core;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Modules\Purchasing\PurchaseRequest\PrApproval;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Core\UserAccessResolver;
use App\Services\Core\UserHierarchyResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
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

    /** Primary department. */
    public function primaryDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'primary_department_id');
    }

    /** Last active business unit. */
    public function lastActiveBusinessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class, 'last_active_business_unit_id');
    }

    /** Primary position. */
    public function primaryPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'primary_position_id');
    }

    /** Supervisor. */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /** Subordinates. */
    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    /** Active subordinates. */
    public function activeSubordinates(): HasMany
    {
        return $this->subordinates()->where('is_active', true);
    }

    /** Business unit assignments. */
    public function businessUnits(): HasMany
    {
        return $this->hasMany(UserBusinessUnit::class);
    }

    /** Active business unit assignments. */
    public function activeBusinessUnits(): HasMany
    {
        return $this->businessUnits()->where('is_active', true);
    }

    /** Primary business unit assignment. */
    public function primaryBusinessUnit()
    {
        return $this->businessUnits()->where('is_primary', true)->first();
    }

    /** Purchase requests created by this user. */
    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    /** Purchase request approvals assigned to this user. */
    public function prApprovals(): HasMany
    {
        return $this->hasMany(PrApproval::class, 'approver_id');
    }

    /** Alias for prApprovals. */
    public function approvals(): HasMany
    {
        return $this->prApprovals();
    }

    /** Pending approvals for this user. */
    public function pendingApprovals(): HasMany
    {
        return $this->prApprovals()->where('status', 'pending');
    }

    // ===== Sales CRM Relationships (v2.5) =====

    /** Sales activities created by this user. */
    public function salesActivities(): HasMany
    {
        return $this->hasMany(\App\Models\Modules\SalesCrm\Activity::class);
    }

    /** Contacts assigned to this user. */
    public function assignedContacts(): HasMany
    {
        return $this->hasMany(\App\Models\Modules\SalesCrm\Contact::class, 'assigned_to');
    }

    /** Contacts created by this user. */
    public function createdContacts(): HasMany
    {
        return $this->hasMany(\App\Models\Modules\SalesCrm\Contact::class, 'created_by');
    }

    /** Scope: active users. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Scope: users in a specific department. */
    public function scopeInDepartment($query, $departmentId)
    {
        return $query->where('primary_department_id', $departmentId);
    }

    /** Scope: users with specific global role. */
    public function scopeWithGlobalRole($query, $role)
    {
        return $query->where('global_role', $role);
    }

    /** Check if user is super admin. */
    public function isSuperAdmin(): bool
    {
        return $this->global_role === 'super_admin';
    }

    /**
     * The methods below proxy to {@see UserAccessResolver} and
     * {@see UserHierarchyResolver} so the model stays under the 400-line cap
     * while preserving every legacy `$user->xxx(...)` call site.
     */

    /** @see UserAccessResolver::getAccessLevel() */
    public function getAccessLevel(?int $businessUnitId = null): string
    {
        return app(UserAccessResolver::class)->getAccessLevel($this, $businessUnitId);
    }

    /** @see UserAccessResolver::getPositionInBusinessUnit() */
    public function getPositionInBusinessUnit(int $businessUnitId): ?Position
    {
        return app(UserAccessResolver::class)->getPositionInBusinessUnit($this, $businessUnitId);
    }

    /** @see UserAccessResolver::hasTopManagementAccess() */
    public function hasTopManagementAccess(): bool
    {
        return app(UserAccessResolver::class)->hasTopManagementAccess($this);
    }

    /** @see UserAccessResolver::hasManagerAccess() */
    public function hasManagerAccess(): bool
    {
        return app(UserAccessResolver::class)->hasManagerAccess($this);
    }

    /** @see UserAccessResolver::hasTopManagementInParentBU() */
    public function hasTopManagementInParentBU(): bool
    {
        return app(UserAccessResolver::class)->hasTopManagementInParentBU($this);
    }

    /** @see UserAccessResolver::isGeneralManager() */
    public function isGeneralManager(): bool
    {
        return app(UserAccessResolver::class)->isGeneralManager($this);
    }

    /**
     * @return array<int, int>
     *
     * @see UserAccessResolver::managedBusinessUnitIds()
     */
    public function managedBusinessUnitIds(): array
    {
        return app(UserAccessResolver::class)->managedBusinessUnitIds($this);
    }

    /**
     * @return array<int, int>
     *
     * @see UserAccessResolver::generalManagerBusinessUnitIds()
     */
    public function generalManagerBusinessUnitIds(): array
    {
        return app(UserAccessResolver::class)->generalManagerBusinessUnitIds($this);
    }

    /** @see UserHierarchyResolver::canAccessBusinessUnit() */
    public function canAccessBusinessUnit($businessUnitId): bool
    {
        return app(UserHierarchyResolver::class)->canAccessBusinessUnit($this, $businessUnitId);
    }

    /**
     * Cache key for the BU id => parent_id map used by the admin cascade.
     * Kept here for backwards compatibility with {@see \App\Observers\BusinessUnitObserver}.
     */
    public const BU_PARENT_MAP_CACHE_KEY = UserHierarchyResolver::BU_PARENT_MAP_CACHE_KEY;

    /** @see UserHierarchyResolver::isAdminInBuOrAncestor() */
    public function isAdminInBuOrAncestor(string $flag, int $buId): bool
    {
        return app(UserHierarchyResolver::class)->isAdminInBuOrAncestor($this, $flag, $buId);
    }

    /** @see UserHierarchyResolver::canAccessDepartment() */
    public function canAccessDepartment($departmentId): bool
    {
        return app(UserHierarchyResolver::class)->canAccessDepartment($this, $departmentId);
    }

    /**
     * @return array<int, int>
     *
     * @see UserHierarchyResolver::getManagedUserIds()
     */
    public function getManagedUserIds(): array
    {
        return app(UserHierarchyResolver::class)->getManagedUserIds($this);
    }

    /** @see UserHierarchyResolver::hasAccessToBusinessUnit() */
    public function hasAccessToBusinessUnit($businessUnitId): bool
    {
        return app(UserHierarchyResolver::class)->hasAccessToBusinessUnit($this, $businessUnitId);
    }

    /**
     * @return array<int, int>
     *
     * @see UserHierarchyResolver::getAccessibleBusinessUnitIds()
     */
    public function getAccessibleBusinessUnitIds(): array
    {
        return app(UserHierarchyResolver::class)->getAccessibleBusinessUnitIds($this);
    }

    /** @see UserHierarchyResolver::getAccessibleBusinessUnits() */
    public function getAccessibleBusinessUnits()
    {
        return app(UserHierarchyResolver::class)->getAccessibleBusinessUnits($this);
    }

    /** @see UserHierarchyResolver::getRoleInBusinessUnit() */
    public function getRoleInBusinessUnit($businessUnitId): ?string
    {
        return app(UserHierarchyResolver::class)->getRoleInBusinessUnit($this, $businessUnitId);
    }

    /** @see UserHierarchyResolver::getCurrentDepartmentId() */
    public function getCurrentDepartmentId(): ?int
    {
        return app(UserHierarchyResolver::class)->getCurrentDepartmentId($this);
    }

    /** @see UserHierarchyResolver::resolveDepartmentForBusinessUnit() */
    public function resolveDepartmentForBusinessUnit(int $businessUnitId): ?int
    {
        return app(UserHierarchyResolver::class)->resolveDepartmentForBusinessUnit($this, $businessUnitId);
    }

    /** @see UserHierarchyResolver::getDepartmentsInCurrentBusinessUnit() */
    public function getDepartmentsInCurrentBusinessUnit(): Collection
    {
        return app(UserHierarchyResolver::class)->getDepartmentsInCurrentBusinessUnit($this);
    }

    /** @see UserHierarchyResolver::hasMultipleDepartmentsInCurrentBusinessUnit() */
    public function hasMultipleDepartmentsInCurrentBusinessUnit(): bool
    {
        return app(UserHierarchyResolver::class)->hasMultipleDepartmentsInCurrentBusinessUnit($this);
    }

    // ===== Sales CRM Helper Methods (v2.5) =====

    /** Check if user has Sales role. */
    public function isSales(): bool
    {
        return $this->hasRole('sales');
    }

    /** Check if user can manage Sales CRM (Super Admin or Admin only). */
    public function canManageSalesCRM(): bool
    {
        return $this->hasPermissionTo('manage_sales_crm');
    }

    /** Check if user can access Sales CRM features. */
    public function canAccessSalesCRM(): bool
    {
        return $this->hasAnyPermission(['view_activities', 'view_contacts', 'manage_sales_crm']);
    }

    /** Activity log options. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
