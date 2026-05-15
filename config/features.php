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

];
