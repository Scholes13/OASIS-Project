<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register PDF Layout Component
        Blade::component('pdf-layout', \App\View\Components\PdfLayout::class);

        // Define Gates for authorization
        $this->defineGates();
    }

    /**
     * Define authorization gates
     */
    protected function defineGates(): void
    {
        // View Reports Gate - Only for top management (General Manager, Director, Super Admin)
        Gate::define('view-reports', function ($user) {
            // Super Admin always has access
            if ($user->isSuperAdmin()) {
                return true;
            }

            // Check if user has top management positions
            $topManagementRoles = ['general_manager', 'director', 'ceo', 'finance_manager'];

            // Get user's active business unit assignments
            $hasTopManagementRole = $user->activeBusinessUnits()
                ->whereHas('position', function ($query) use ($topManagementRoles) {
                    $query->whereIn('slug', $topManagementRoles);
                })
                ->exists();

            return $hasTopManagementRole;
        });
    }
}
