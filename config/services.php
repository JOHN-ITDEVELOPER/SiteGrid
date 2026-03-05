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
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'mpesa' => [
        'environment' => env('MPESA_ENV', 'sandbox'), // 'sandbox' or 'production'
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        'passkey' => env('MPESA_PASSKEY'),
        'shortcode' => env('MPESA_SHORTCODE'),
        'timeout' => env('MPESA_TIMEOUT', 60),
        'connect_timeout' => env('MPESA_CONNECT_TIMEOUT', 15),
        'retry_times' => env('MPESA_RETRY_TIMES', 1),
        'retry_sleep_ms' => env('MPESA_RETRY_SLEEP_MS', 500),
        'b2c_shortcode' => env('MPESA_B2C_SHORTCODE'),
        'b2c_initiator_name' => env('MPESA_B2C_INITIATOR_NAME'),
        'b2c_security_credential' => env('MPESA_B2C_SECURITY_CREDENTIAL'),
        'default_b2c_fee' => env('MPESA_DEFAULT_B2C_FEE', 25),
        'fee_api' => [
            'enabled' => env('MPESA_FEE_API_ENABLED', false),
            'url' => env('MPESA_FEE_API_URL'),
            'token' => env('MPESA_FEE_API_TOKEN'),
            'currency' => env('MPESA_FEE_API_CURRENCY', 'KES'),
            'timeout' => env('MPESA_FEE_API_TIMEOUT', 10),
        ],
        // Fallback B2C transfer fee bands used when fee API is unavailable.
        'b2c_fee_tiers' => [
            ['min' => 10, 'max' => 100, 'fee' => 0],
            ['min' => 101, 'max' => 500, 'fee' => 13],
            ['min' => 501, 'max' => 1000, 'fee' => 17],
            ['min' => 1001, 'max' => 1500, 'fee' => 22],
            ['min' => 1501, 'max' => 2500, 'fee' => 25],
            ['min' => 2501, 'max' => 5000, 'fee' => 53],
            ['min' => 5001, 'max' => 10000, 'fee' => 57],
            ['min' => 10001, 'max' => 20000, 'fee' => 65],
            ['min' => 20001, 'max' => 35000, 'fee' => 76],
            ['min' => 35001, 'max' => 50000, 'fee' => 108],
            ['min' => 50001, 'max' => 0, 'fee' => 108],
        ],
    ],

    'africastalking' => [
        'username' => env('AFRICASTALKING_USERNAME'),
        'api_key' => env('AFRICASTALKING_API_KEY'),
        'from' => env('AFRICASTALKING_FROM', 'SITEGRID'),
        'environment' => env('AFRICASTALKING_ENV', 'sandbox'), // 'sandbox' or 'production'
    ],

];
