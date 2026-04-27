<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class NotificationSetting extends Model
{
    /**
     * In-memory singleton instance (prevents repeated cache lookups in same request)
     */
    protected static ?self $instance = null;

    protected $fillable = [
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'mail_from_address',
        'mail_from_name',
        'email_enabled',
        'fallback_to_database',
        'link_expiry_days',
        'retry_failed_emails',
        'total_sent',
        'total_failed',
        'last_email_sent_at',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'fallback_to_database' => 'boolean',
        'retry_failed_emails' => 'boolean',
        'smtp_port' => 'integer',
        'link_expiry_days' => 'integer',
        'total_sent' => 'integer',
        'total_failed' => 'integer',
        'last_email_sent_at' => 'datetime',
    ];

    /**
     * Boot method to auto-clear cache on updates
     */
    protected static function boot(): void
    {
        parent::boot();

        // Clear cache whenever settings are saved or deleted
        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });
    }

    /**
     * Get SMTP password (decrypted)
     */
    public function getSmtpPasswordAttribute($value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to decrypt SMTP password', [
                'error' => $e->getMessage(),
                'setting_id' => $this->id,
            ]);

            return null;
        }
    }

    /**
     * Set SMTP password (encrypted)
     */
    public function setSmtpPasswordAttribute($value): void
    {
        if ($value) {
            $this->attributes['smtp_password'] = Crypt::encryptString($value);
        } else {
            $this->attributes['smtp_password'] = null;
        }
    }

    /**
     * Get singleton instance (only one settings record should exist)
     * Uses in-memory cache + Laravel cache for optimal performance
     * - First request in process: Cache lookup (0.1ms)
     * - Subsequent requests: In-memory (0.001ms)
     */
    public static function getInstance(): self
    {
        // Return in-memory instance if available (prevents even cache lookups)
        if (static::$instance !== null) {
            return static::$instance;
        }

        // Otherwise get from cache (or database on first access)
        static::$instance = \Illuminate\Support\Facades\Cache::remember(
            'notification_settings_singleton',
            3600, // 1 hour cache
            function () {
                return static::firstOrCreate(
                    [], // No search criteria (we want any record)
                    [
                        'smtp_host' => config('notification.defaults.smtp_host', 'smtp.gmail.com'),
                        'smtp_port' => config('notification.defaults.smtp_port', 587),
                        'smtp_encryption' => config('notification.defaults.smtp_encryption', 'tls'),
                        'mail_from_address' => config('notification.defaults.mail_from_address', 'noreply@werkudara.com'),
                        'mail_from_name' => config('notification.defaults.mail_from_name', 'WNS Purchase Request System'),
                        'email_enabled' => false,
                        'fallback_to_database' => true,
                        'link_expiry_days' => 3,
                        'retry_failed_emails' => false,
                    ]
                );
            }
        );

        return static::$instance;
    }

    /**
     * Clear singleton cache (call this when settings are updated)
     */
    public static function clearCache(): void
    {
        static::$instance = null; // Clear in-memory instance
        \Illuminate\Support\Facades\Cache::forget('notification_settings_singleton');
    }

    /**
     * Increment sent counter (atomic operation to prevent race conditions)
     */
    public function incrementSent(): void
    {
        $this->increment('total_sent', 1, ['last_email_sent_at' => now()]);
    }

    /**
     * Increment failed counter (atomic operation to prevent race conditions)
     */
    public function incrementFailed(): void
    {
        $this->increment('total_failed');
    }
}
