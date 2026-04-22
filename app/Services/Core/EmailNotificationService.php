<?php

namespace App\Services\Core;

use App\Models\Core\NotificationSetting;
use App\Models\Modules\Purchasing\PurchaseRequest\PrApproval;
use App\Models\Modules\Purchasing\StockRequest\StockApproval;
use App\Notifications\Purchasing\PurchaseRequest\ApprovalCompleted;
use App\Notifications\Purchasing\PurchaseRequest\ApprovalRejected;
use App\Notifications\Purchasing\PurchaseRequest\ApprovalRequested;
use App\Notifications\Purchasing\StockRequest\ApprovalApproved as StApprovalApproved;
use App\Notifications\Purchasing\StockRequest\ApprovalRejected as StApprovalRejected;
use App\Notifications\Purchasing\StockRequest\ApprovalRequested as StApprovalRequested;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    protected ?NotificationSetting $settings = null;

    public function __construct()
    {
        $this->loadSettings();
    }

    /**
     * Load notification settings from cache or database
     */
    protected function loadSettings(): void
    {
        $this->settings = Cache::remember('notification_settings', 3600, function () {
            return NotificationSetting::getInstance();
        });
    }

    /**
     * Refresh settings cache
     */
    public static function refreshSettings(): void
    {
        Cache::forget('notification_settings');
    }

    /**
     * Check if email notifications are enabled
     */
    public function isEmailEnabled(): bool
    {
        return $this->settings && $this->settings->email_enabled;
    }

    /**
     * Configure mail driver with database settings
     */
    protected function configureMail(): void
    {
        if (! $this->settings) {
            return;
        }

        Config::set('mail.mailers.smtp', [
            'transport' => 'smtp',
            'host' => $this->settings->smtp_host,
            'port' => $this->settings->smtp_port,
            'encryption' => $this->settings->smtp_encryption === 'none' ? null : $this->settings->smtp_encryption,
            'username' => $this->settings->smtp_username,
            'password' => $this->settings->smtp_password,
            'timeout' => 5,
        ]);

        Config::set('mail.from', [
            'address' => $this->settings->mail_from_address,
            'name' => $this->settings->mail_from_name,
        ]);

        Config::set('mail.default', 'smtp');
    }

    // ─── Purchase Request ────────────────────────────────────────

    /**
     * Send PR approval requested notification
     */
    public function sendApprovalRequested(PrApproval $approval): bool
    {
        return $this->sendNotification(
            $approval->approver,
            new ApprovalRequested($approval),
            fn () => $approval->markEmailSent(),
        );
    }

    /**
     * Send PR approval approved notification (to next approver or requestor)
     */
    public function sendApprovalApproved(PrApproval $approval): bool
    {
        $pr = $approval->purchaseRequest;
        $nextApproval = $pr->currentApproval();

        if ($nextApproval) {
            return $this->sendNotification(
                $nextApproval->approver,
                new ApprovalRequested($nextApproval),
                fn () => $nextApproval->markEmailSent(),
            );
        }

        return $this->sendNotification($pr->user, new ApprovalCompleted($pr));
    }

    /**
     * Send PR approval rejected notification (to requestor)
     */
    public function sendApprovalRejected(PrApproval $approval): bool
    {
        return $this->sendNotification(
            $approval->purchaseRequest->user,
            new ApprovalRejected($approval),
        );
    }

    /**
     * Send PR approval completed notification (to requestor)
     */
    public function sendApprovalCompleted($pr): bool
    {
        return $this->sendNotification($pr->user, new ApprovalCompleted($pr));
    }

    // ─── Stock Request ───────────────────────────────────────────

    /**
     * Send ST approval requested notification to the given approver.
     */
    public function sendStApprovalRequested(StockApproval $approval): bool
    {
        return $this->sendNotification(
            $approval->approver,
            new StApprovalRequested($approval),
            function () use ($approval) {
                $approval->update(['email_sent' => true, 'email_sent_at' => now()]);
            },
        );
    }

    /**
     * Send ST approval approved notification to the requester.
     */
    public function sendStApprovalApproved(\App\Models\Modules\Purchasing\StockRequest\StockRequest $stockRequest): bool
    {
        return $this->sendNotification(
            $stockRequest->user,
            new StApprovalApproved($stockRequest),
        );
    }

    /**
     * Send ST approval rejected notification to the requester.
     */
    public function sendStApprovalRejected(StockApproval $approval): bool
    {
        return $this->sendNotification(
            $approval->stockRequest->user,
            new StApprovalRejected($approval),
        );
    }

    // ─── Core dispatch ───────────────────────────────────────────

    /**
     * Dispatch a notification through Laravel's notification system.
     *
     * When email is enabled the SMTP config is applied first so the
     * `mail` channel inside the notification's `via()` uses the
     * dynamic settings.  This is the ONLY place that calls
     * `$notifiable->notify()` — no separate `Mail::html()` call —
     * which eliminates the duplicate-email bug.
     *
     * @param  \Closure|null  $onDispatched  Optional callback executed after a successful dispatch (e.g. mark email_sent on approval record).
     */
    protected function sendNotification($notifiable, Notification $notification, ?\Closure $onDispatched = null): bool
    {
        if (! $notifiable) {
            return false;
        }

        $emailSent = false;

        try {
            // Configure SMTP before notify() so the mail channel uses
            // the dynamic database settings instead of the .env defaults.
            if ($this->isEmailEnabled()) {
                $this->configureMail();
            }

            // Single dispatch — Laravel routes to every channel returned
            // by the notification's via() method (database, broadcast,
            // and mail when email is enabled).
            $notifiable->notify($notification);

            // Mark the dispatch as successful regardless of email being
            // enabled — callers use this to track that the notification
            // was sent (e.g. email_sent flag on approval records).
            if ($onDispatched) {
                $onDispatched();
            }

            if ($this->isEmailEnabled()) {
                $emailSent = true;

                if ($this->settings) {
                    $this->settings->incrementSent();
                }

                Log::info('Email notification sent successfully', [
                    'recipient' => $notifiable->email,
                    'notification_type' => get_class($notification),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
                'recipient' => $notifiable->email ?? 'unknown',
                'notification_type' => get_class($notification),
                'error' => $e->getMessage(),
            ]);

            if ($this->settings) {
                $this->settings->incrementFailed();
            }
        }

        return $emailSent;
    }

    // ─── Utilities ───────────────────────────────────────────────

    /**
     * Test email sending
     */
    public function sendTestEmail(string $recipientEmail, string $recipientName = 'Test User'): array
    {
        try {
            if (! $this->isEmailEnabled()) {
                return [
                    'success' => false,
                    'message' => 'Email notifications are disabled in settings.',
                ];
            }

            $this->configureMail();

            Mail::raw('This is a test email from WNS Purchase Request System. If you receive this email, your SMTP configuration is working correctly.', function ($message) use ($recipientEmail, $recipientName) {
                $message->to($recipientEmail, $recipientName)
                    ->subject('Test Email - WNS Purchase Request System');
            });

            return [
                'success' => true,
                'message' => 'Test email sent successfully to '.$recipientEmail,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to send test email: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get link expiry days
     */
    public function getLinkExpiryDays(): int
    {
        return $this->settings?->link_expiry_days ?? 3;
    }
}
