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
        'key'      => 'a828e15f1db322b77d7f57787ea3d390d06f3e5ab6e9254fe80044afaf4b0d82',
        'username' => 'sandbox',
        'phone'    => null,
        'env'      => 'development',
        'airtime'  => [
            'key'      => '89770c2faea7f33dce6ca5605a0cd7b9e810a999af8ea0c051a5e504d2380928',
            'username' => 'sidooh_airtime',
        ],
        'ussd'     => [
            'code' => '*384*99#',
        ],
    ],

    'sidooh' => [
        'jwt_key'            => env('JWT_KEY'),
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
        'utilities_provider' => 'TANDA',
        'services'           => [
            'accounts' => [
                'enabled' => true,
                'url'     => env('SIDOOH_ACCOUNTS_API_URL'),
            ],
            'notify'   => [
                'enabled' => true,
                'url'     => env('SIDOOH_NOTIFY_API_URL'),
            ],
            'payments' => [
                'enabled' => true,
                'url'     => env('SIDOOH_PAYMENTS_API_URL'),
            ],
            'savings'  => [
                'enabled' => true,
                'url'     => env('SIDOOH_SAVINGS_API_URL'),
            ],
        ],
        'admin_contacts'     => env('ADMIN_CONTACTS', '254110039317,254714611696,254711414987'),
        'country_code'       => env('COUNTRY_CODE', 'KE'),
    ],

    'tanda' => [
        'discounts' => [
            'SAFARICOM'     => [
                'type'  => '%',
                'value' => .06,
            ],
            'FAIBA'         => [
                'type'  => '%',
                'value' => .07,
            ],
            'AIRTEL'        => [
                'type'  => '%',
                'value' => .06,
            ],
            'TELKOM'        => [
                'type'  => '%',
                'value' => .06,
            ],
            'KPLC_POSTPAID' => [
                'type'  => '%',
                'value' => .017,
            ],
            'KPLC_PREPAID'  => [
                'type'  => '%',
                'value' => .02,
            ],
            'DSTV'          => [
                'type'  => '%',
                'value' => .003,
            ],
            'GOTV'          => [
                'type'  => '%',
                'value' => .003,
            ],
            'ZUKU'          => [
                'type'  => '%',
                'value' => .003,
            ],
            'STARTIMES'     => [
                'type'  => '%',
                'value' => .003,
            ],
            'NAIROBI_WTR'   => [
                'type'  => '$',
                'value' => 5,
            ],
        ],
    ],

    'kyanda' => [
        'discounts' => [
            'SAFARICOM'     => [
                'type'  => '%',
                'value' => .06,
            ],
            'FAIBA'         => [
                'type'  => '%',
                'value' => .09,
            ],
            'FAIBA_B'       => [
                'type'  => '%',
                'value' => .09,
            ],
            'AIRTEL'        => [
                'type'  => '%',
                'value' => .06,
            ],
            'TELKOM'        => [
                'type'  => '%',
                'value' => .06,
            ],
            'EQUITEL'       => [
                'type'  => '%',
                'value' => .05,
            ],
            'KPLC_POSTPAID' => [
                'type'  => '%',
                'value' => .01,
            ],
            'KPLC_PREPAID'  => [
                'type'  => '%',
                'value' => .015,
            ],
            'DSTV'          => [
                'type'  => '%',
                'value' => .0025,
            ],
            'GOTV'          => [
                'type'  => '%',
                'value' => .0025,
            ],
            'ZUKU'          => [
                'type'  => '%',
                'value' => .0025,
            ],
            'STARTIMES'     => [
                'type'  => '%',
                'value' => .0025,
            ],
            'NAIROBI_WTR'   => [
                'type'  => '$',
                'value' => 5,
            ],
        ],
    ],
];
