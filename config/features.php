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

];
