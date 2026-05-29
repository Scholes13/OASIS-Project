<?php

namespace App\Services\Core;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves cross-BU and cross-department access for a {@see User}, plus the
 * session-aware department helpers used by Inertia middleware and Activity
 * controllers.
 *
 * Extracted from {@see User} to keep the model under the 400-line cap.
 * Public method semantics mirror the legacy User methods exactly — User
 * proxies through this resolver via the service container so all existing
 * `$user->xxx(...)` call sites continue to work without modification.
 */
class UserHierarchyResolver
{
    /**
     * Cache key for the BU id => parent_id map used by the admin cascade.
     */
    public const BU_PARENT_MAP_CACHE_KEY = 'core.bu_parent_map.v1';

    public function __construct(
        protected UserAccessResolver $accessResolver,
    ) {}

    /**
     * Check if user can access business unit (with hierarchy walk).
     */
    public function canAccessBusinessUnit(User $user, int|string $businessUnitId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $accessibleIds = $this->getAccessibleBusinessUnitIds($user);

        return in_array($businessUnitId, $accessibleIds, true)
            || in_array((int) $businessUnitId, $accessibleIds, true);
    }

    /**
     * Direct membership / GM check (no parent walk).
     */
    public function hasAccessToBusinessUnit(User $user, int|string $businessUnitId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (in_array($businessUnitId, $this->accessResolver->generalManagerBusinessUnitIds($user), true)) {
            return true;
        }

        return $user->activeBusinessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if user has an admin flag in the given BU or any of its ancestor BUs.
     *
     * @param  string  $flag  Column name: 'is_activity_admin' or 'is_purchasing_admin'
     */
    public function isAdminInBuOrAncestor(User $user, string $flag, int $buId): bool
    {
        $adminBuIds = $user->activeBusinessUnits()
            ->where($flag, true)
            ->pluck('business_unit_id')
            ->toArray();

        if (empty($adminBuIds)) {
            return false;
        }

        if (in_array($buId, $adminBuIds, true)) {
            return true;
        }

        $buParentMap = Cache::remember(
            self::BU_PARENT_MAP_CACHE_KEY,
            now()->addMinutes(15),
            fn () => BusinessUnit::pluck('parent_id', 'id')->toArray()
        );

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
     * Check if user can access department.
     */
    public function canAccessDepartment(User $user, int|string $departmentId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $accessLevel = $this->accessResolver->getAccessLevel($user);

        if ($accessLevel === 'executive') {
            $businessUnitIds = $this->getAccessibleBusinessUnitIds($user);

            return Department::where('id', $departmentId)
                ->whereIn('business_unit_id', $businessUnitIds)
                ->exists();
        }

        if ($accessLevel === 'general_manager') {
            $businessUnitIds = $this->accessResolver->generalManagerBusinessUnitIds($user);

            if (! empty($businessUnitIds)) {
                return Department::where('id', $departmentId)
                    ->whereIn('business_unit_id', $businessUnitIds)
                    ->exists();
            }
        }

        return $user->primary_department_id === (int) $departmentId
            || $user->primary_department_id === $departmentId;
    }

    /**
     * Get all accessible business unit IDs for this user.
     *
     * @return array<int, int>
     */
    public function getAccessibleBusinessUnitIds(User $user): array
    {
        if ($user->isSuperAdmin()) {
            $primaryBU = $user->primaryBusinessUnit();
            if ($primaryBU && $primaryBU->businessUnit) {
                $accessibleIds = $primaryBU->businessUnit->getAccessibleBusinessUnits();

                return array_values(array_unique($accessibleIds));
            }

            return BusinessUnit::active()->pluck('id')->toArray();
        }

        $accessLevel = $this->accessResolver->getAccessLevel($user);

        if ($accessLevel === 'executive' || $this->accessResolver->hasTopManagementAccess($user)) {
            $ids = [];

            foreach ($user->activeBusinessUnits()->with('businessUnit')->get() as $assignment) {
                if ($assignment->businessUnit) {
                    $ids = array_merge($ids, $assignment->businessUnit->getAccessibleBusinessUnits());
                }
            }

            if ($this->accessResolver->isGeneralManager($user)) {
                $ids = array_merge($ids, $this->accessResolver->managedBusinessUnitIds($user));
            }

            return array_values(array_unique($ids));
        }

        if ($accessLevel === 'general_manager') {
            $ids = $this->accessResolver->generalManagerBusinessUnitIds($user);
            if (! empty($ids)) {
                return $ids;
            }
        }

        return $user->activeBusinessUnits()->pluck('business_unit_id')->toArray();
    }

    /**
     * Get accessible business units with their relationships.
     */
    public function getAccessibleBusinessUnits(User $user)
    {
        $accessibleIds = $this->getAccessibleBusinessUnitIds($user);

        return BusinessUnit::whereIn('id', $accessibleIds)
            ->with(['parent', 'children', 'departments'])
            ->active()
            ->get();
    }

    /**
     * Get user role in specific business unit.
     */
    public function getRoleInBusinessUnit(User $user, int|string $businessUnitId): ?string
    {
        if ($this->canAccessBusinessUnit($user, $businessUnitId)) {
            return $this->accessResolver->getAccessLevel($user, (int) $businessUnitId);
        }

        return null;
    }

    /**
     * Get users that this user can manage/view.
     *
     * @return array<int, int>
     */
    public function getManagedUserIds(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return User::pluck('id')->toArray();
        }

        $accessLevel = $this->accessResolver->getAccessLevel($user);
        $managedIds = [$user->id];

        switch ($accessLevel) {
            case 'executive':
                $businessUnitIds = $this->getAccessibleBusinessUnitIds($user);

                if (! empty($businessUnitIds)) {
                    $departmentIds = Department::whereIn('business_unit_id', $businessUnitIds)
                        ->pluck('id');
                    $managedIds = User::whereIn('primary_department_id', $departmentIds)
                        ->pluck('id')->toArray();
                    $managedIds[] = $user->id;
                }
                break;

            case 'general_manager':
                $businessUnitIds = $this->accessResolver->generalManagerBusinessUnitIds($user);

                if (! empty($businessUnitIds)) {
                    $departmentIds = Department::whereIn('business_unit_id', $businessUnitIds)
                        ->pluck('id');
                    $managedIds = User::whereIn('primary_department_id', $departmentIds)
                        ->pluck('id')->toArray();
                    $managedIds[] = $user->id;
                }
                break;

            case 'department_head':
                $managedIds = User::where('primary_department_id', $user->primary_department_id)
                    ->pluck('id')->toArray();
                break;

            case 'team_leader':
                $managedIds = User::where('supervisor_id', $user->id)
                    ->pluck('id')->toArray();
                $managedIds[] = $user->id;
                break;

            case 'staff':
            default:
                $managedIds = [$user->id];
                break;
        }

        return array_values(array_unique($managedIds));
    }

    /**
     * Get current department ID from session, with fallback to primary.
     */
    public function getCurrentDepartmentId(User $user): ?int
    {
        return session('current_department_id') ?? $user->primary_department_id;
    }

    /**
     * Resolve the best department ID for the given business unit.
     *
     * Priority: 1) current session dept if valid for BU,
     *           2) user's assignment in the BU,
     *           3) first active department in BU (last resort for super admin).
     */
    public function resolveDepartmentForBusinessUnit(User $user, int $businessUnitId): ?int
    {
        $currentDeptId = session('current_department_id');
        if ($currentDeptId) {
            $valid = Department::where('id', $currentDeptId)
                ->where('business_unit_id', $businessUnitId)
                ->where('is_active', true)
                ->exists();
            if ($valid) {
                return (int) $currentDeptId;
            }
        }

        $userAssignment = $user->activeBusinessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->whereNotNull('department_id')
            ->first();
        if ($userAssignment) {
            return (int) $userAssignment->department_id;
        }

        $fallback = Department::where('business_unit_id', $businessUnitId)
            ->where('is_active', true)
            ->orderBy('name')
            ->first();

        return $fallback?->id;
    }

    /**
     * Get all departments user belongs to in current business unit.
     */
    public function getDepartmentsInCurrentBusinessUnit(User $user): Collection
    {
        $currentBusinessUnitId = session('current_business_unit_id');

        if (! $currentBusinessUnitId) {
            return collect();
        }

        return $user->businessUnits()
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
     * Check if user has multiple departments in current business unit.
     */
    public function hasMultipleDepartmentsInCurrentBusinessUnit(User $user): bool
    {
        return $this->getDepartmentsInCurrentBusinessUnit($user)->count() > 1;
    }
}
