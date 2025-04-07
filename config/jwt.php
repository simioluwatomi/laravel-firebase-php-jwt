<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Algorithm
    |--------------------------------------------------------------------------
    |
    | JWT uses an algorithm to encode and decode tokens.
    | Valid algorithms are listed here
    | https://datatracker.ietf.org/doc/html/rfc7518#section-3
    |
    */
    'algorithm' => env('JWT_ALGORITHM', 'RS256'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Keys
    |--------------------------------------------------------------------------
    |
    | JWT uses encryption keys while generating secure access tokens for your
    | application. By default, the keys are stored as local files but can
    | be set via environment variables when that is more convenient.
    |
    */
    'encryption_keys' => [
        'private_key' => env('JWT_PRIVATE_KEY'),
        'private_key_filename' => env('JWT_PRIVATE_KEY_FILENAME', 'auth_private.key'),

        'public_key' => env('JWT_PUBLIC_KEY'),
        'public_key_filename' => env('JWT_PUBLIC_KEY_FILENAME', 'auth_public.key'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Lifetime Expiry
    |--------------------------------------------------------------------------
    |
    | Here you may define the amount in minutes before authentication tokens
    | expire
    |
    */

    'token_lifetime' => env('JWT_TOKEN_LIFETIME', 60),
];
