<?php

use League\Flysystem\GoogleCloudStorage\UniformBucketLevelAccessVisibility;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3", "rackspace"
    |
    */

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'throw' => false,
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],
        'gcs' => [
            'driver' => 'gcs',
            'key_file_path' => env('GOOGLE_CLOUD_KEY_FILE', null),
            // either key_file_path or key_file should be provided
            'key_file' => [
                'type' => env('GOOGLE_CLOUD_ACCOUNT_TYPE', null),
                'private_key_id' => env('GOOGLE_CLOUD_PRIVATE_KEY_ID', null),
                'private_key' => env('GOOGLE_CLOUD_PRIVATE_KEY', null),
                'client_email' => env('GOOGLE_CLOUD_CLIENT_EMAIL', null),
                'client_id' => env('GOOGLE_CLOUD_CLIENT_ID', null),
                'auth_uri' => env('GOOGLE_CLOUD_AUTH_URI', null),
                'token_uri' => env('GOOGLE_CLOUD_TOKEN_URI', null),
                'auth_provider_x509_cert_url' => env('GOOGLE_CLOUD_AUTH_PROVIDER_CERT_URL', null),
                'client_x509_cert_url' => env('GOOGLE_CLOUD_CLIENT_CERT_URL', null),
            ],
            'project_id' => env('GOOGLE_CLOUD_PROJECT_ID', ''),
            'bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET', ''),
            'path_prefix' => env('GOOGLE_CLOUD_STORAGE_PATH_PREFIX', null),
            'storage_api_uri' => env('GOOGLE_CLOUD_STORAGE_API_URI', 'https://storage.googleapis.com'),
            'api_endpoint' => env('GOOGLE_CLOUD_STORAGE_API_ENDPOINT', null),
            'visibility' => 'public',
            'visibility_handler' => UniformBucketLevelAccessVisibility::class,
            'metadata' => ['cacheControl' => 'public,max-age=86400'],
            'throw' => true,
        ],
    ],
];
