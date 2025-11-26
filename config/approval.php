<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Approval Thresholds
    |--------------------------------------------------------------------------
    |
    | Define amount thresholds that trigger different approval levels.
    | These thresholds determine which approvers are required based on
    | the total amount of the purchase request.
    |
    */
    'thresholds' => [
        'department_head' => 500000,      // > 500K requires dept head
        'finance_manager' => 1000000,     // > 1M requires finance manager
        'general_manager' => 5000000,     // > 5M requires general manager
        'director' => 10000000,           // > 10M requires director
    ],

    /*
    |--------------------------------------------------------------------------
    | Special Category Keywords
    |--------------------------------------------------------------------------
    |
    | Define keywords that identify special item categories requiring
    | specialized approval. Keywords are case-insensitive and use
    | regular expression matching against item names.
    |
    */
    'special_categories' => [
        'it' => [
            'computer',
            'laptop',
            'server',
            'software',
            'hardware',
            'printer',
            'monitor',
            'keyboard',
            'mouse',
            'router',
            'switch',
            'network',
        ],
        'vehicle' => [
            'vehicle',
            'car',
            'truck',
            'motorcycle',
            'bike',
            'transport',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Special Category Approvers
    |--------------------------------------------------------------------------
    |
    | Define which role should approve purchase requests containing
    | items from special categories. Maps category type to role name.
    |
    */
    'special_category_approvers' => [
        'it' => 'it_manager',
        'vehicle' => 'fleet_manager',
    ],

    /*
    |--------------------------------------------------------------------------
    | Approval Timeout Settings
    |--------------------------------------------------------------------------
    |
    | Configure timeout periods for approval responses.
    |
    */
    'timeouts' => [
        'escalation_hours' => 24,         // Auto-escalate after 24 hours
        'auto_approve_days' => 7,         // Auto-approve after 7 days
    ],
];
