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

        // Configure dynamic SMTP settings from database
        $this->configureDynamicMailer();

        // Define Gates for authorization
        $this->defineGates();
    }

    /**
     * Configure mail settings dynamically from database
     */
    protected function configureDynamicMailer(): void
    {
        try {
            // Only configure if database is accessible
            if (!\Illuminate\Support\Facades\Schema::hasTable('notification_settings')) {
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
