<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PDF Generation Method
    |--------------------------------------------------------------------------
    |
    | This option controls which PDF generation method to use:
    | - 'dompdf': Uses DomPDF (traditional, limited CSS support)
    | - 'browsershot': Uses Browsershot with Puppeteer (modern CSS support)
    |
    */
    'pdf_method' => env('PDF_METHOD', 'browsershot'),

    /*
    |--------------------------------------------------------------------------
    | Browsershot Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for Browsershot PDF generation
    |
    */
    'browsershot' => [
        'timeout' => 180, // Increase to 3 minutes
        'format' => 'A4',
        'orientation' => 'landscape',
        'margins' => [
            'top' => 10,
            'right' => 10,
            'bottom' => 10,
            'left' => 10,
        ],
        'wait_until_network_idle' => false, // Disable network idle to prevent timeout
        'enable_javascript' => false, // Disable JS to speed up loading
        'no_sandbox' => true,
        'disable_web_security' => true,
        'ignore_https_errors' => true,
        'remote_url' => env('BROWSERSHOT_CHROME_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | DomPDF Configuration
    |--------------------------------------------------------------------------
    |
    | Fallback configuration for DomPDF when Browsershot is not available
    |
    */
    'dompdf' => [
        'format' => 'A4',
        'orientation' => 'landscape',
    ],
];
