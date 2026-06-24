<?php

namespace App\Http\Middleware;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Services\Core\NavigationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'layouts.inertia';

    /**
     * Cache TTL for business units list (30 minutes)
     */
    protected const BUSINESS_UNITS_CACHE_TTL = 1800;

    /**
     * Cache TTL for the per-user unread notification count (60 seconds).
     *
     * The Inertia bell renders this on every authenticated request, so a
     * short cache absorbs request bursts without making the badge feel
     * stale.  Notification dispatch and mark-read paths invalidate the
     * key explicitly to keep the badge interactive.
     */
    protected const UNREAD_NOTIFICATIONS_CACHE_TTL = 60;

    /**
     * Build the cache key for a user's unread notification count.
     */
    public static function unreadNotificationsCacheKey(int $userId): string
    {
        return "notifications.unread_count.{$userId}";
    }

    public function __construct(
        protected NavigationService $navigationService
    ) {}

    /**
     * Determine the current asset version.
     * Uses manifest.json hash for cache busting after deployments.
     */
    public function version(Request $request): ?string
    {
        $manifestPath = public_path('build/manifest.json');

        if (file_exists($manifestPath)) {
            return md5_file($manifestPath);
        }

        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $currentBusinessUnitId = session('current_business_unit_id');
        $currentDepartmentId = session('current_department_id');

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first(),
                    'global_role' => $user->global_role,
                    'avatar_url' => $user->avatar_url ?? null,
                    'primary_department_id' => $user->primary_department_id,
                    'current_department_id' => $user->getCurrentDepartmentId(),
                    'is_purchasing_readonly' => $this->isPurchasingReadonly($user, $currentBusinessUnitId),
                ] : null,
            ],
            'currentBusinessUnit' => fn () => $this->getCurrentBusinessUnit($currentBusinessUnitId),
            'currentDepartment' => fn () => $this->getCurrentDepartment($currentDepartmentId),
            'availableBusinessUnits' => fn () => $this->getCachedBusinessUnits($user),
            'availableDepartments' => fn () => $this->getAvailableDepartments($user),
            'navigation' => $this->getCachedNavigation($user, $currentBusinessUnitId),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                'cashflow_import' => fn () => $request->session()->get('cashflow_import'),
                'just_logged_in' => fn () => $request->session()->get('just_logged_in'),
                'created_task_id' => fn () => $request->session()->get('created_task_id'),
            ],
            'notifications' => [
                'unread_count' => fn () => $user
                    ? Cache::remember(
                        self::unreadNotificationsCacheKey((int) $user->id),
                        self::UNREAD_NOTIFICATIONS_CACHE_TTL,
                        fn () => $user->unreadNotifications()->count(),
                    )
                    : 0,
            ],
            'appName' => config('app.name'),
            'appEnvironment' => config('app.env'),
            'isStaging' => app()->environment('staging'),
            'serverDate' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Get the current business unit data including logo.
     * Note: Logo is returned as relative path for React components to construct URL
     */
    protected function getCurrentBusinessUnit(?int $businessUnitId): ?array
    {
        if (! $businessUnitId) {
            return null;
        }

        $businessUnit = BusinessUnit::find($businessUnitId);

        if (! $businessUnit) {
            return null;
        }

        return [
            'id' => $businessUnit->id,
            'code' => $businessUnit->code,
            'name' => $businessUnit->name,
            'logo' => $businessUnit->logo,
        ];
    }

    /**
     * Get the current department data from session.
     *
     * Includes parent metadata when the active department is a sub-department
     * (Division), so the frontend can render breadcrumbs like "S&M / BS".
     */
    protected function getCurrentDepartment(?int $departmentId): ?array
    {
        if (! $departmentId) {
            return null;
        }

        $department = Department::with('parent:id,code,name')->find($departmentId);

        if (! $department) {
            return null;
        }

        return [
            'id' => $department->id,
            'code' => $department->code,
            'name' => $department->name,
            'parent_department_id' => $department->parent_department_id,
            'parent' => $department->parent ? [
                'id' => $department->parent->id,
                'code' => $department->parent->code,
                'name' => $department->parent->name,
            ] : null,
        ];
    }

    /**
     * Get available departments for the user in current business unit,
     * grouped as a parent-children tree.
     *
     * Returns root departments (parent_department_id is null) at the top
     * level, each with a `children` array of its sub-departments that the
     * user has access to. Flat departments simply have an empty `children`.
     *
     * Returns [] when the user only has access to a single department,
     * matching the existing switcher visibility rule.
     */
    protected function getAvailableDepartments($user): array
    {
        if (! $user) {
            return [];
        }

        if (! $user->hasMultipleDepartmentsInCurrentBusinessUnit()) {
            return [];
        }

        $depts = $user->getDepartmentsInCurrentBusinessUnit();

        $byParent = $depts->groupBy('parent_department_id');

        // Roots are either depts with parent_department_id=null (true roots)
        // or depts whose parent is not in the user's accessible list (treat
        // them as roots too so they don't disappear).
        $accessibleIds = $depts->pluck('id')->all();

        $roots = $depts->filter(function ($d) use ($accessibleIds) {
            return $d->parent_department_id === null
                || ! in_array($d->parent_department_id, $accessibleIds, true);
        });

        return $roots->map(function ($root) use ($byParent) {
            $children = $byParent->get($root->id, collect());

            return [
                'id' => $root->id,
                'code' => $root->code,
                'name' => $root->name,
                'parent_department_id' => $root->parent_department_id,
                'children' => $children->map(fn ($child) => [
                    'id' => $child->id,
                    'code' => $child->code,
                    'name' => $child->name,
                    'parent_department_id' => $child->parent_department_id,
                ])->values()->toArray(),
            ];
        })->values()->toArray();
    }

    /**
     * Get cached navigation for the user.
     */
    /**
     * Build navigation fresh on every request.
     *
     * Navigation depends on the user's current BU context, roles, and
     * pivot flags which can change at any time (admin assignment, BU
     * switch, role grant).  Caching caused stale sidebar menus that
     * showed items the user could not access or hid items they could.
     * The underlying query is lightweight (a few pivot flag checks),
     * so the trade-off favours correctness over a marginal cache hit.
     */
    protected function getCachedNavigation($user, ?int $businessUnitId): array
    {
        if (! $user) {
            return ['sections' => []];
        }

        return $this->navigationService->buildMenuForUser($user, $businessUnitId);
    }

    /**
     * Get cached available business units for the user.
     */
    protected function getCachedBusinessUnits($user): array
    {
        if (! $user) {
            return [];
        }

        $cacheKey = "bu_list:{$user->id}";

        return Cache::remember($cacheKey, self::BUSINESS_UNITS_CACHE_TTL, function () use ($user) {
            return $this->getAvailableBusinessUnits($user);
        });
    }

    /**
     * Get available business units for the user.
     */
    protected function getAvailableBusinessUnits($user): array
    {
        if (! $user) {
            return [];
        }

        $accessibleIds = $user->getAccessibleBusinessUnitIds();

        return BusinessUnit::whereIn('id', $accessibleIds)
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn (BusinessUnit $businessUnit) => [
                'id' => $businessUnit->id,
                'code' => $businessUnit->code,
                'name' => $businessUnit->name,
                'logo' => $businessUnit->logo,
            ])
            ->toArray();
    }

    /**
     * Get the navigation menu structure for the user.
     */
    protected function getNavigation($user, ?int $businessUnitId): array
    {
        if (! $user) {
            return ['sections' => []];
        }

        return $this->navigationService->buildMenuForUser($user, $businessUnitId);
    }

    protected function isPurchasingReadonly($user, $businessUnitId): bool
    {
        if (! $user || ! $businessUnitId) {
            return false;
        }

        return \App\Models\Core\UserBusinessUnit::query()
            ->where('user_id', $user->id)
            ->where('business_unit_id', $businessUnitId)
            ->where('is_active', true)
            ->where('is_purchasing_admin', true)
            ->where('is_purchasing_readonly', true)
            ->exists();
    }
}
