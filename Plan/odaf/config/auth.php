<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    | Guard 'web' berbasis session, memakai provider 'odaf' (SEC_USER).
    */
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'odaf',
        ],
    ],

    'providers' => [
        // Provider kustom terdaftar via Auth::provider('odaf', ...) pada
        // OdafServiceProvider::boot(). Mengautentikasi terhadap SEC_USER.
        'odaf' => [
            'driver' => 'odaf',
        ],
    ],

    // Reset password bawaan Laravel tidak dipakai (dikelola via metadata/CLI).
    'passwords' => [
        'users' => [
            'provider' => 'odaf',
            'table' => 'SEC_USER',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
