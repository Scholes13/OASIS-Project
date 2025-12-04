<?php

namespace App\Services\Core;

use App\Models\Core\NotificationSetting;
use App\Models\Modules\PurchaseRequest\PrApproval;
use App\Notifications\PurchaseRequest\ApprovalRequested;
use App\Notifications\PurchaseRequest\ApprovalRejected;
use App\Notifications\PurchaseRequest\ApprovalCompleted;
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
        if (!$this->settings) {
            return;
        }

        Config::set('mail.mailers.smtp', [
            'transport' => 'smtp',
            'host' => $this->settings->smtp_host,
            'port' => $this->settings->smtp_port,
            'encryption' => $this->settings->smtp_encryption === 'none' ? null : $this->settings->smtp_encryption,
            'username' => $this->settings->smtp_username,
            'password' => $this->settings->smtp_password, // Already decrypted by model accessor
            'timeout' => 5, // 5 second timeout for synchronous sending
        ]);

        Config::set('mail.from', [
            'address' => $this->settings->mail_from_address,
            'name' => $this->settings->mail_from_name,
        ]);

        Config::set('mail.default', 'smtp');
    }

    /**
     * Send approval requested notification
     */
    public function sendApprovalRequested(PrApproval $approval): bool
    {
        $approver = $approval->approver;
        $notification = new ApprovalRequested($approval);

        return $this->sendNotification($approver, $notification, $approval);
    }

    /**
     * Send approval approved notification (to next approver or requestor)
     */
    public function sendApprovalApproved(PrApproval $approval): bool
    {
        $pr = $approval->purchaseRequest;
        $nextApproval = $pr->currentApproval();

        if ($nextApproval) {
            // Notify next approver
            $notification = new ApprovalRequested($nextApproval);
            return $this->sendNotification($nextApproval->approver, $notification, $nextApproval);
        } else {
            // All approvals complete - notify requestor
            $notification = new ApprovalCompleted($pr);
            return $this->sendNotification($pr->user, $notification);
        }
    }

    /**
     * Send approval rejected notification (to requestor)
     */
    public function sendApprovalRejected(PrApproval $approval): bool
    {
        $pr = $approval->purchaseRequest;
        $notification = new ApprovalRejected($approval);

        return $this->sendNotification($pr->user, $notification);
    }

    /**
     * Send approval completed notification (to requestor)
     */
    public function sendApprovalCompleted($pr): bool
    {
        $notification = new ApprovalCompleted($pr);
        return $this->sendNotification($pr->user, $notification);
    }

    /**
     * Generic notification sender with email fallback
     */
    protected function sendNotification($notifiable, $notification, ?PrApproval $approval = null): bool
    {
        $emailSent = false;

        try {
            // Always save to database (fallback) if enabled and settings available
            if ($this->settings && $this->settings->fallback_to_database) {
                $notifiable->notify($notification);
            }

            // Try to send email if enabled
            if ($this->isEmailEnabled()) {
                $this->configureMail();

                // Get the mail message from notification
                $mailMessage = $notification->toMail($notifiable);
                
                // Render the view content
                $htmlContent = view($mailMessage->view, $mailMessage->viewData)->render();

                // Send email using Mailable approach
                Mail::html($htmlContent, function ($message) use ($notifiable, $mailMessage) {
                    $message->to($notifiable->email, $notifiable->name)
                            ->subject($mailMessage->subject ?? 'Purchase Request Approval Notification');
                });

                $emailSent = true;

                // Update statistics if settings available
                if ($this->settings) {
                    $this->settings->incrementSent();
                }

                // Mark approval email as sent
                if ($approval) {
                    $approval->markEmailSent();
                }

                Log::info('Email notification sent successfully', [
                    'recipient' => $notifiable->email,
                    'notification_type' => get_class($notification),
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail (database notification already saved)
            Log::error('Failed to send email notification', [
                'recipient' => $notifiable->email ?? 'unknown',
                'notification_type' => get_class($notification),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update failed counter
            if ($this->settings) {
                $this->settings->incrementFailed();
            }

            $emailSent = false;
        }

        return $emailSent;
    }

    /**
     * Test email sending
     */
    public function sendTestEmail(string $recipientEmail, string $recipientName = 'Test User'): array
    {
        try {
            if (!$this->isEmailEnabled()) {
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
                'message' => 'Test email sent successfully to ' . $recipientEmail,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage(),
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
