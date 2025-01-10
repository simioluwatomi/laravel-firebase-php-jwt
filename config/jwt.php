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
    | application. By default, the keys are stored as local files.
    | The filenames of the files are set below.
    |
    */
    'encryption_keys' => [
        'private_key_filename' => env('JWT_PRIVATE_KEY_FILENAME', 'auth_private.key'),
        'public_key_filename' => env('JWT_PUBLIC_KEY_FILENAME', 'auth_public.key'),
    ],
];
