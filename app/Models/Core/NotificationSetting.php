<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class NotificationSetting extends Model
{
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
     * Get SMTP password (decrypted)
     */
    public function getSmtpPasswordAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
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
     */
    public static function getInstance(): self
    {
        $settings = static::first();

        if (!$settings) {
            // Create default settings
            $settings = static::create([
                'smtp_host' => 'smtp.gmail.com',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
                'mail_from_address' => 'noreply@werkudara.com',
                'mail_from_name' => 'WNS Purchase Request System',
                'email_enabled' => false,
                'fallback_to_database' => true,
                'link_expiry_days' => 3,
                'retry_failed_emails' => false,
            ]);
        }

        return $settings;
    }

    /**
     * Increment sent counter
     */
    public function incrementSent(): void
    {
        $this->increment('total_sent');
        $this->update(['last_email_sent_at' => now()]);
    }

    /**
     * Increment failed counter
     */
    public function incrementFailed(): void
    {
        $this->increment('total_failed');
    }
}
