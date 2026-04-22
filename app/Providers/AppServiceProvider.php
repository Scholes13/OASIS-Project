<?php

namespace App\Providers;

use App\Support\ViteHotFileGuard;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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
        $this->cleanupStaleViteHotFile();

        // Register PDF Layout Component
        Blade::component('pdf-layout', \App\View\Components\PdfLayout::class);

        // Load module migrations
        $this->loadMigrationsFrom([
            database_path('migrations'),
            database_path('migrations/modules/activity'),
            // database_path('migrations/modules/sales-crm'), // Temporarily disabled due to duplicate migrations
            database_path('migrations/modules/stock-request'),
        ]);

        // Configure dynamic SMTP settings from database
        $this->configureDynamicMailer();

        // Define Gates for authorization
        $this->defineGates();

        // Register model observers
        $this->registerObservers();
    }

    protected function cleanupStaleViteHotFile(): void
    {
        $hotFilePath = public_path('hot');
        $environment = (string) config('app.env');

        if (! app(ViteHotFileGuard::class)->cleanup($environment, $hotFilePath)) {
            return;
        }

        Log::warning('Removed stale Vite hot file outside local runtime.', [
            'environment' => $environment,
            'hot_file' => $hotFilePath,
        ]);
    }

    /**
     * Register model observers
     */
    protected function registerObservers(): void
    {
        \App\Models\Core\UserBusinessUnit::observe(
            \App\Observers\UserBusinessUnitObserver::class
        );

        \App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest::observe(
            \App\Observers\PurchaseRequestObserver::class
        );

        \App\Models\Modules\Purchasing\StockRequest\StockRequest::observe(
            \App\Observers\StockRequestObserver::class
        );
    }

    /**
     * Configure mail settings dynamically from database
     * Cached to prevent schema check on every request
     */
    protected function configureDynamicMailer(): void
    {
        try {
            // Only configure if database is accessible (cached for 1 hour)
            $hasTable = \Illuminate\Support\Facades\Cache::remember(
                'schema_has_notification_settings_table',
                3600, // 1 hour cache
                fn () => \Illuminate\Support\Facades\Schema::hasTable('notification_settings')
            );

            if (! $hasTable) {
                return;
            }

            $settings = \App\Models\Core\NotificationSetting::getInstance();

            // Only configure if email is enabled
            if (! $settings->email_enabled) {
                return;
            }

            // Configure mail dynamically
            config([
                'mail.default' => 'dynamic_smtp',
                'mail.mailers.dynamic_smtp' => [
                    'transport' => 'smtp',
                    'host' => $settings->smtp_host,
                    'port' => $settings->smtp_port,
                    'encryption' => $settings->smtp_encryption,
                    'username' => $settings->smtp_username,
                    'password' => $settings->smtp_password, // Auto-decrypted by accessor
                    'timeout' => 30,
                ],
                'mail.from' => [
                    'address' => $settings->mail_from_address,
                    'name' => $settings->mail_from_name,
                ],
            ]);

        } catch (\Exception $e) {
            // Silently fail during migrations or when table doesn't exist
            \Illuminate\Support\Facades\Log::debug('Dynamic mailer configuration skipped', [
                'reason' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Define authorization gates.
     *
     * Enterprise SaaS pattern: Gates delegate to Position model scopes
     * as the single source of truth for hierarchy-based authorization.
     * No hardcoded position names, codes, or integer access_level values.
     */
    protected function defineGates(): void
    {
        // View Reports Gate - Top management (C-Level / Executive)
        Gate::define('view-reports', function ($user) {
            if ($user->isSuperAdmin()) {
                return true;
            }

            return $user->hasTopManagementAccess();
        });

        // View Department Analytics Gate - For department heads and above
        Gate::define('view-department-analytics', function ($user) {
            if ($user->isSuperAdmin()) {
                return true;
            }

            return $user->primary_department_id !== null;
        });

        // Access Purchasing Admin Gate - For purchasing admins, super admin, and parent BU top management
        Gate::define('access-purchasing-admin', function ($user) {
            $currentBuId = session('current_business_unit_id');

            if ($user->isSuperAdmin()) {
                return true;
            }

            // Top management in parent BU can access all child BU purchasing
            if ($user->hasTopManagementInParentBU()) {
                return true;
            }

            // Manager-and-above in parent BU also gets access
            $hasManagerInParentBU = $user->activeBusinessUnits()
                ->whereHas('businessUnit', fn ($q) => $q->whereNull('parent_id'))
                ->whereHas('position', fn ($q) => $q->managerAndAbove())
                ->exists();

            if ($hasManagerInParentBU) {
                return true;
            }

            // Check if user is a purchasing admin in current BU
            if ($currentBuId) {
                return $user->activeBusinessUnits()
                    ->where('business_unit_id', $currentBuId)
                    ->where('is_purchasing_admin', true)
                    ->whereHas('department', function ($query) {
                        $query->where('is_purchasing_department', true);
                    })
                    ->exists();
            }

            return false;
        });

        // Access Cashflow Projection Gate - For department heads and finance/CFC users
        Gate::define('access-cashflow-projection', function ($user) {
            $currentBuId = session('current_business_unit_id');

            return app(\App\Services\Modules\CashflowProjection\CashflowProjectionAccessService::class)
                ->canAccess($user, $currentBuId);
        });

        // Manage Cashflow Projection Gate - For finance/CFC users only
        Gate::define('manage-cashflow-projection', function ($user) {
            $currentBuId = session('current_business_unit_id');

            return app(\App\Services\Modules\CashflowProjection\CashflowProjectionAccessService::class)
                ->canManage($user, $currentBuId);
        });
    }
}
