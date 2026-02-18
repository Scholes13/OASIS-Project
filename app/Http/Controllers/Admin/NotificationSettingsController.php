<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\NotificationSetting;
use App\Services\Core\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin.access'); // Only super admin can access
    }

    /**
     * Display notification settings
     */
    public function index()
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403, 'Only Super Administrators can access notification settings.');
        }

        $settings = NotificationSetting::getInstance();

        return Inertia::render('Admin/NotificationSettings/Index', [
            'settings' => [
                'smtp_host' => $settings->smtp_host,
                'smtp_port' => $settings->smtp_port,
                'smtp_username' => $settings->smtp_username,
                'smtp_password' => $settings->smtp_password ? '********' : null, // Mask password
                'smtp_encryption' => $settings->smtp_encryption,
                'mail_from_address' => $settings->mail_from_address,
                'mail_from_name' => $settings->mail_from_name,
                'email_enabled' => $settings->email_enabled,
                'fallback_to_database' => $settings->fallback_to_database,
                'link_expiry_days' => $settings->link_expiry_days,
                'retry_failed_emails' => $settings->retry_failed_emails,
                'total_sent' => $settings->total_sent,
                'total_failed' => $settings->total_failed,
                'last_email_sent_at' => $settings->last_email_sent_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Update notification settings
     */
    public function update(Request $request)
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403, 'Only Super Administrators can modify notification settings.');
        }

        $validated = $request->validate([
            'smtp_host' => 'required|string|max:255',
            'smtp_port' => 'required|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_encryption' => 'required|in:tls,ssl,none',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name' => 'required|string|max:255',
            'email_enabled' => 'boolean',
            'fallback_to_database' => 'boolean',
            'link_expiry_days' => 'required|integer|min:1|max:30',
            'retry_failed_emails' => 'boolean',
        ]);

        $settings = NotificationSetting::getInstance();

        // Only update password if provided
        if (empty($validated['smtp_password'])) {
            unset($validated['smtp_password']);
        }

        $settings->update($validated);

        // Clear cache
        EmailNotificationService::refreshSettings();

        return back()->with('success', 'Notification settings updated successfully.');
    }

    /**
     * Send test email
     */
    public function sendTest(Request $request)
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'test_email' => 'required|email',
        ]);

        $emailService = new EmailNotificationService();
        $result = $emailService->sendTestEmail($validated['test_email'], Auth::user()->name);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        } else {
            return back()->with('error', $result['message']);
        }
    }

    /**
     * View email statistics
     */
    public function statistics()
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403);
        }

        $settings = NotificationSetting::getInstance();

        $stats = [
            'total_sent' => $settings->total_sent,
            'total_failed' => $settings->total_failed,
            'success_rate' => $settings->total_sent + $settings->total_failed > 0
                ? round(($settings->total_sent / ($settings->total_sent + $settings->total_failed)) * 100, 2)
                : 0,
            'last_email_sent' => $settings->last_email_sent_at,
        ];

        return Inertia::render('Admin/NotificationSettings/Statistics', [
            'stats' => [
                'total_sent' => $stats['total_sent'],
                'total_failed' => $stats['total_failed'],
                'success_rate' => $stats['success_rate'],
                'last_email_sent' => $stats['last_email_sent']?->toISOString(),
            ],
            'settings' => [
                'email_enabled' => $settings->email_enabled,
                'fallback_to_database' => $settings->fallback_to_database,
                'retry_failed_emails' => $settings->retry_failed_emails,
                'link_expiry_days' => $settings->link_expiry_days,
                'smtp_host' => $settings->smtp_host,
                'smtp_port' => $settings->smtp_port,
                'smtp_encryption' => $settings->smtp_encryption,
                'mail_from_address' => $settings->mail_from_address,
                'mail_from_name' => $settings->mail_from_name,
            ],
        ]);
    }
}
