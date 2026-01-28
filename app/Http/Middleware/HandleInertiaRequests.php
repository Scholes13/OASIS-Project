<?php

namespace App\Http\Middleware;

use App\Models\Core\BusinessUnit;
use App\Services\Core\NavigationService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'layouts.inertia';

    public function __construct(
        protected NavigationService $navigationService
    ) {}

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
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

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first(),
                    'avatar_url' => $user->avatar_url ?? null,
                    'primary_department_id' => $user->primary_department_id,
                ] : null,
            ],
            'currentBusinessUnit' => fn () => $this->getCurrentBusinessUnit($currentBusinessUnitId),
            'availableBusinessUnits' => fn () => $this->getAvailableBusinessUnits($user),
            'navigation' => fn () => $this->getNavigation($user, $currentBusinessUnitId),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
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
        if (!$businessUnitId) {
            return null;
        }

        $businessUnit = BusinessUnit::find($businessUnitId);
        
        if (!$businessUnit) {
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
     * Get available business units for the user.
     * Note: Logo is returned as relative path for React components to construct URL
     */
    protected function getAvailableBusinessUnits($user): array
    {
        if (!$user) {
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
        if (!$user) {
            return ['sections' => []];
        }

        return $this->navigationService->buildMenuForUser($user, $businessUnitId);
    }
}
