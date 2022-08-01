<?php

return [
    /*
     |------------------------------------------------------
     | Set sandbox mode
     | ------------------------------------------------------
     | Specify whether this is a test app or production app
     |
     | Sandbox base url: https://io-proxy-443.tanda.co.ke/sandbox
     | Production base url: https://io-proxy-443.tanda.co.ke
     */
    'sandbox' => env('TANDA_SANDBOX', false),

    /*
   |--------------------------------------------------------------------------
   | Cache credentials
   |--------------------------------------------------------------------------
   |
   | If you decide to cache credentials, they will be kept in your app cache
   | configuration for some time. Reducing the need for many requests for
   | generating credentials
   |
   */
    'cache_credentials' => true,

    /*
   |--------------------------------------------------------------------------
   | URLs
   |--------------------------------------------------------------------------
   |
   | Callback - Will be used to send you payment notifications.
   |
   */
    'urls' => [
        'base' => 'https://io-proxy-443.tanda.co.ke',
        /*
         * --------------------------------------------------------------------------------------
         * Callbacks:
         * ---------------------------------------------------------------------------------------
         * Please update your app url in .env file
         * Note: This package has already routes for handling this callback.
         * You should leave this values as they are unless you know what you are doing.
         */
        'callback' => config('app.url') . '/tanda/callbacks/notification',
    ],

    /*
   |--------------------------------------------------------------------------
   | Organization ID
   |--------------------------------------------------------------------------
   |
   | Provided by Tanda after account creation.
   |
   */
    'organization_id' => env('TANDA_ORGANIZATION_ID'),


    /*
   |--------------------------------------------------------------------------
   | Client Identification
   |--------------------------------------------------------------------------
   |
   | Provided by Tanda after account creation.
   |
   */
    'client_id' => env('TANDA_CLIENT_ID'),


    /*
   |--------------------------------------------------------------------------
   | Client Secret
   |--------------------------------------------------------------------------
   |
   | Provided by Tanda after account creation.
   |
   */
    'client_secret' => env('TANDA_CLIENT_SECRET'),


    /*
   |--------------------------------------------------------------------------
   | LIMITS
   |--------------------------------------------------------------------------
   |
   | Limits - Will be given by Kyanda and used to validate
   |
   */
    'limits' => [
        'AIRTIME.min' => 10,
        'AIRTIME.max' => 10000,

        'bills' => [
            'KPLC_POSTPAID.min' => 100,
            'KPLC_POSTPAID.max' => 35000,

            'KPLC_PREPAID.min' => 100,
            'KPLC_PREPAID.max' => 35000,
        ]
    ],

    'logging' => [
        'enabled' => env('TANDA_ENABLE_LOGGING', false),
        'channels' => [
            'syslog',
            'single' => [
                'driver' => 'single',
                'path' => storage_path('logs/tanda.log'),
                'level' => env('LOG_LEVEL', 'debug'),
            ],
        ]
    ]
];
