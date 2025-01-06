<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Secret Key
    |--------------------------------------------------------------------------
    |
    | A secret key is used to sign and validate JWTs when using symmetric
    | algorithms. Asymmetric algorithms use a private/public key pair.
    |
    */
    'secret_key' => env('JWT_SECRET_KEY'),

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
    'algorithm' => env('JWT_ALGORITHM', 'HS256'),
];
