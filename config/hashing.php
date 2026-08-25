<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | Argon2id is the default password hashing algorithm.
    | Set HASH_DRIVER=bcrypt only when Argon2id is unavailable.
    |
    */

    'driver' => env('HASH_DRIVER', 'argon2id'),

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    |
    | Fallback options for environments without Argon2id support.
    |
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => env('HASH_VERIFY', true),
        'limit' => env('BCRYPT_LIMIT', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon Options
    |--------------------------------------------------------------------------
    |
    | Uses lower memory settings for shared hosting environments.
    |
    */

    'argon' => [
        'memory' => env('ARGON_MEMORY', 19456),
        'threads' => env('ARGON_THREADS', 1),
        'time' => env('ARGON_TIME', 2),
        'verify' => env('HASH_VERIFY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rehash On Login
    |--------------------------------------------------------------------------
    |
    | Rehash passwords when the driver or its options change.
    |
    */

    'rehash_on_login' => true,
];
