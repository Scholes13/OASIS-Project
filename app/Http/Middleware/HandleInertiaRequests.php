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
     * Cache TTL for navigation (15 minutes)
     */
    protected const NAVIGATION_CACHE_TTL = 900;

    /**
     * Cache TTL for business units list (30 minutes)
     */
    protected const BUSINESS_UNITS_CACHE_TTL = 1800;

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
                    'avatar_url' => $user->avatar_url ?? null,
                    'primary_department_id' => $user->primary_department_id,
                    'current_department_id' => $user->getCurrentDepartmentId(),
                ] : null,
            ],
            'currentBusinessUnit' => fn () => $this->getCurrentBusinessUnit($currentBusinessUnitId),
            'currentDepartment' => fn () => $this->getCurrentDepartment($currentDepartmentId),
            // Available business units with caching (loaded on every request, cached for 30 min)
            'availableBusinessUnits' => fn () => $this->getCachedBusinessUnits($user),
            // Available departments for current business unit
            'availableDepartments' => fn () => $this->getAvailableDepartments($user),
            // Navigation with caching - always needed for sidebar
            'navigation' => fn () => $this->getCachedNavigation($user, $currentBusinessUnitId),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                'just_logged_in' => fn () => $request->session()->get('just_logged_in'),
                'created_task_id' => fn () => $request->session()->get('created_task_id'),
            ],
            'appName' => config('app.name'),
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
            'logo' => $businessUnit->logo, // Relative path - React prepends /storage/
        ];
    }

    /**
     * Get the current department data from session.
     */
    protected function getCurrentDepartment(?int $departmentId): ?array
    {
        if (! $departmentId) {
            return null;
        }

        $department = Department::find($departmentId);

        if (! $department) {
            return null;
        }

        return [
            'id' => $department->id,
            'code' => $department->code,
            'name' => $department->name,
        ];
    }

    /**
     * Get available departments for the user in current business unit.
     * Only returns departments if user has multiple departments.
     */
    protected function getAvailableDepartments($user): array
    {
        if (! $user) {
            return [];
        }

        // Only return departments if user has multiple in current BU
        if (! $user->hasMultipleDepartmentsInCurrentBusinessUnit()) {
            return [];
        }

        return $user->getDepartmentsInCurrentBusinessUnit()
            ->map(fn ($department) => [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
            ])
            ->toArray();
    }

    /**
     * Get cached navigation for the user.
     * Cache key includes user ID and business unit ID.
     * Cache is invalidated when user permissions change (via UserBusinessUnitObserver).
     */
    protected function getCachedNavigation($user, ?int $businessUnitId): array
    {
        if (! $user || ! $businessUnitId) {
            return ['sections' => []];
        }

        $cacheKey = "nav:{$user->id}:{$businessUnitId}";

        return Cache::remember($cacheKey, self::NAVIGATION_CACHE_TTL, function () use ($user, $businessUnitId) {
            return $this->navigationService->buildMenuForUser($user, $businessUnitId);
        });
    }

    /**
     * Get cached available business units for the user.
     * Cache is invalidated when user BU assignments change (via UserBusinessUnitObserver).
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
     * Note: Logo is returned as relative path for React components to construct URL
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
            ->map(fn (BusinessUnit $bu) => [
                'id' => $bu->id,
                'code' => $bu->code,
                'name' => $bu->name,
                'logo' => $bu->logo, // Relative path - React prepends /storage/
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
}
