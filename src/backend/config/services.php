<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION', 'us-east-1'),
    ],
    'pagination' => 25,
    'manufacturer_master_key' => '36dKhvjwE58!M2.A@L', // the key which is required to  add a new  manufacturer
    'sms' => [
        'android' => [
            'url' => 'https://fcm.googleapis.com/fcm/send',
            'key' => 'AAAARAca1HM:APA91bHTTU2ksDRKWf7O7zsN5KZebDHVdnM_GeTAmFWtZp3R4__n0g8b3s9Vu7hWEEBfYpOq5_CmMMfJlLmW5FjNatp__4G3m1Mim7fRp-3CFs2ByKnvzXC8X9V1kxKZuBT_UK_bmQYO',
        ],
        'callback' => 'https://cloud.micropowermanager.com/api/sms-android-callback/%s/confirm/',
    ],
    'queues' => [
        'payment' => env('QUEUE_PAYMENT', 'payment'),
        'energy' => env('QUEUE_ENERGY', 'energy_payment'),
        'token' => env('QUEUE_TOKEN', 'token'),
        'sms' => env('QUEUE_SMS', 'sms'),
        'report' => env('QUEUE_REPORT', 'report_generator'),
        'misc' => env('QUEUE_MISC', 'misc'),
    ],
    'payment' => [
        'data-stream' => 'placeholder-url',
        'maintenance' => 'placeholder-url',
    ],
    'agent' => [
        'key' => 'key=AAAAdSTAIwc:APA91bHl4w-l4QSlHFPbfM-soHzf0hf1rQSgV-ubjzSxALNYjb_lnJigRvyWvp1IybrZTDfM-CaZ7yFBSoZh47V49fdOz5gLCSriN5T1qmLJ40S1WWUCLWNV32g7YPaz-6lcxbunyHcB',
    ],
    'bingMapApi' => [
        'url' => env('BINGMAP_API_URL', 'https://dev.virtualearth.net/REST/v1/Imagery/Metadata/Aerial?key='),
    ],
    'sunKing' => [
        'url' => env('SUNKING_API_URL'),
    ],
    'waveMoney' => [
        'url' => env('WAVEMONEY_API_URL'),
    ],
];
