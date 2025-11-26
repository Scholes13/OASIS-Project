<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure default settings for email notifications in the purchase
    | request approval workflow. These values can be overridden by
    | Super Admin via the admin notification settings panel.
    |
    */

    'link_expiry_days' => env('NOTIFICATION_LINK_EXPIRY_DAYS', 3),

    /*
    |--------------------------------------------------------------------------
    | Email Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time (in seconds) to wait for email sending before timing out.
    | This prevents the application from hanging if the SMTP server is slow.
    |
    */

    'email_timeout' => env('NOTIFICATION_EMAIL_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    |
    | Configure automatic retry behavior for failed email notifications.
    | Note: Retries are currently manual via admin panel.
    |
    */

    'retry_enabled' => env('NOTIFICATION_RETRY_ENABLED', false),
    'max_retries' => env('NOTIFICATION_MAX_RETRIES', 3),
    'retry_delay' => env('NOTIFICATION_RETRY_DELAY', 300), // 5 minutes

    /*
    |--------------------------------------------------------------------------
    | Database Fallback
    |--------------------------------------------------------------------------
    |
    | Always save notifications to database even if email sending succeeds.
    | This ensures users can always access their notifications via the UI.
    |
    */

    'always_save_to_database' => env('NOTIFICATION_ALWAYS_SAVE_DATABASE', true),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for notification settings to reduce
    | database queries on every email send operation.
    |
    */

    'cache_ttl' => env('NOTIFICATION_CACHE_TTL', 3600), // 1 hour

    /*
    |--------------------------------------------------------------------------
    | Email Templates
    |--------------------------------------------------------------------------
    |
    | Configure email template settings and default content.
    |
    */

    'templates' => [
        'approval_requested' => [
            'subject' => 'Action Required: Purchase Request Approval',
            'greeting' => 'Hello',
        ],
        'approval_approved' => [
            'subject' => 'Purchase Request Approved',
            'greeting' => 'Good news!',
        ],
        'approval_rejected' => [
            'subject' => 'Purchase Request Rejected',
            'greeting' => 'Hello',
        ],
        'approval_completed' => [
            'subject' => 'Purchase Request Fully Approved',
            'greeting' => 'Congratulations!',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default SMTP Settings
    |--------------------------------------------------------------------------
    |
    | Default SMTP configuration used if database settings are not configured.
    | Super Admin should configure these via the admin panel.
    |
    */

    'defaults' => [
        'smtp_host' => env('MAIL_HOST', 'smtp.gmail.com'),
        'smtp_port' => env('MAIL_PORT', 587),
        'smtp_encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'mail_from_address' => env('MAIL_FROM_ADDRESS', 'noreply@werkudara.com'),
        'mail_from_name' => env('MAIL_FROM_NAME', 'WNS Purchase Request System'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Available notification channels for the application.
    | 'mail' and 'database' are currently supported.
    |
    */

    'channels' => [
        'mail' => true,
        'database' => true,
        'sms' => false, // Future feature
        'slack' => false, // Future feature
    ],

];
