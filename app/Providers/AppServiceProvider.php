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

    /**
     * Register model observers
     */
    protected function registerObservers(): void
    {
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
                fn() => \Illuminate\Support\Facades\Schema::hasTable('notification_settings')
            );

            if (!$hasTable) {
                return;
            }

            $settings = \App\Models\Core\NotificationSetting::getInstance();

            // Only configure if email is enabled
            if (!$settings->email_enabled) {
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
                'reason' => $e->getMessage()
            ]);
        }
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

            // Check if user has top management positions by access_level or name
            // access_level: 1 = CEO/Director, 2 = General Manager, 3 = Manager
            // Also check by position name for specific roles
            $topManagementNames = ['Top Management', 'Chief Executive Officer', 'Finance Manager'];

            // Get user's active business unit assignments with top management access
            $hasTopManagementRole = $user->activeBusinessUnits()
                ->whereHas('position', function ($query) use ($topManagementNames) {
                    $query->where(function ($q) use ($topManagementNames) {
                        // Check by access_level (1 = CEO/Director, 2 = GM)
                        $q->whereIn('access_level', [1, 2])
                            // Or check by specific position names
                            ->orWhereIn('name', $topManagementNames)
                            // Or check by code pattern for managers
                            ->orWhere('code', 'LIKE', 'MGR_FIN%')
                            ->orWhere('code', 'TOP_MANAGEMENT')
                            ->orWhere('code', 'CEO_LEAD');
                    });
                })
                ->exists();

            return $hasTopManagementRole;
        });

        // View Department Analytics Gate - For department heads and above
        Gate::define('view-department-analytics', function ($user) {
            // Super Admin always has access
            if ($user->isSuperAdmin()) {
                return true;
            }

            // Any user with a department assignment can view their department analytics
            // The component itself will filter to show only their department's data
            return $user->primary_department_id !== null;
        });

        // Access Purchasing Admin Gate - For purchasing admins, super admin, and parent BU top management
        Gate::define('access-purchasing-admin', function ($user) {
            $currentBuId = session('current_business_unit_id');

            // Super Admin has full access
            if ($user->isSuperAdmin()) {
                return true;
            }

            // Check if user is top management in parent BU (Werkudara Group)
            $isTopManagementParent = $user->activeBusinessUnits()
                ->whereHas('businessUnit', function ($query) {
                    $query->whereNull('parent_id'); // Parent BU has no parent
                })
                ->whereHas('position', function ($query) {
                    // Top management access levels: 1 (CEO/Director), 2 (General Manager)
                    $query->whereIn('access_level', [1, 2])
                        ->orWhereIn('level', ['hod']); // Also include HOD level for department heads
                })
                ->exists();

            if ($isTopManagementParent) {
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
    }
}
