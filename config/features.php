<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Toggle application features on/off. Useful for temporarily disabling
    | features without removing code.
    |
    */

    'backdate_approval' => env('FEATURE_BACKDATE_APPROVAL', false),
    'sales_crm' => env('FEATURE_SALES_CRM', false),

    /*
    |--------------------------------------------------------------------------
    | Backdate Approval Window (days)
    |--------------------------------------------------------------------------
    |
    | How many calendar days an approved backdate permission stays usable
    | after approval.  The window starts at end-of-day on the approval day
    | so an approval at 23:00 still gives the requester a full extra day.
    |
    */
    'backdate_grant_days' => (int) env('FEATURE_BACKDATE_GRANT_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Activity Module
    |--------------------------------------------------------------------------
    */
    'activity' => [
        // TTL (seconds) for the Activity dashboard analytics cache.
        'dashboard_cache_ttl' => (int) env('ACTIVITY_DASHBOARD_CACHE_TTL', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cashflow Projection Module
    |--------------------------------------------------------------------------
    */
    'cashflow' => [
        // Global minimum cash balance threshold (IDR). Closing balances below
        // this trigger the dashboard "is_warning" flag.
        'minimum_balance_global' => (int) env('CASHFLOW_MINIMUM_BALANCE_GLOBAL', 200000000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Purchasing Module
    |--------------------------------------------------------------------------
    */
    'purchasing' => [
        // Max PHP execution time (seconds) when generating PR PDFs via Browsershot.
        'pdf_generation_timeout' => (int) env('PURCHASING_PDF_TIMEOUT', 300),
    ],

];
