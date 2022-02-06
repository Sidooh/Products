<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'at' => [
        'key'      => '24f8a51e90338e4d2a6ebb81899fb4dc66099df456338be9da55cd8b5ce196f7',
        'username' => 'sandbox',
        'phone'    => NULL,
        'env'      => 'production',
        'airtime'  => [
            'key'      => '89770c2faea7f33dce6ca5605a0cd7b9e810a999af8ea0c051a5e504d2380928',
            'username' => 'sidooh_airtime',
        ],
        'ussd'     => [
            'code' => '*384*99#',
        ],
    ],

    'sidooh' => [
        'earnings'           => [
            'users_percentage' => 0.6,
        ],
        'tagline'            => 'Sidooh, Makes You Money with Every Purchase.',
        'mpesa'              => [
            'env' => 'local',
            'b2c' => [
                'phone'      => '254708374149',
                'min_amount' => '10',
                'max_amount' => '70000',
            ],
        ],
        'utilities_enabled'  => true,
        'utilities_provider' => 'AT',
        'services'           => [
            'notify'   => [
                'enabled' => true,
                'url'     => 'https://hoodis-notify.herokuapp.com/api/notifications',
            ],
            'accounts' => [
                'enabled' => true,
                'url'     => 'https://sidooh-accounts.herokuapp.com/api/accounts'
            ]
        ],
    ],

];
