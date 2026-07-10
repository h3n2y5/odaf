<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    | ODAF menggunakan Oracle sebagai single source of truth untuk metadata dan
    | data bisnis (Vol.1 - Oracle-centric).
    */
    'default' => env('DB_CONNECTION', 'oracle'),

    'connections' => [
        // Koneksi utama Oracle via yajra/laravel-oci8.
        'oracle' => [
            'driver' => 'oracle',
            'tns' => env('DB_TNS', ''),
            'host' => env('DB_HOST', 'oracle'),
            'port' => env('DB_PORT', '1521'),
            'database' => env('DB_SERVICE_NAME', 'FREEPDB1'),
            'service_name' => env('DB_SERVICE_NAME', 'FREEPDB1'),
            'username' => env('DB_USERNAME', 'ODAF'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'AL32UTF8'),
            'prefix' => '',
            'prefix_schema' => env('DB_SCHEMA_PREFIX', ''),
            'edition' => 'ora$base',
            'server_version' => '23c',
            'load_balance' => 'yes',
            'dynamic' => [],
        ],

        // Koneksi runtime terpisah (isolasi design-time vs runtime, CORE-004).
        // Untuk dev, service name/skema sama dengan koneksi utama.
        'oracle_runtime' => [
            'driver' => 'oracle',
            'host' => env('DB_HOST', 'oracle'),
            'port' => env('DB_PORT', '1521'),
            'database' => env('DB_SERVICE_NAME', 'FREEPDB1'),
            'service_name' => env('DB_SERVICE_NAME', 'FREEPDB1'),
            'username' => env('DB_USERNAME', 'ODAF'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'AL32UTF8'),
            'prefix' => '',
            'prefix_schema' => env('ODAF_RUNTIME_SCHEMA', ''),
        ],

        // SQLite in-memory untuk unit test yang tidak butuh Oracle.
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE_TEST', ':memory:'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ],

    'migrations' => [
        'table' => 'ODAF_MIGRATIONS',
        'update_date_on_publish' => true,
    ],

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
    ],
];
