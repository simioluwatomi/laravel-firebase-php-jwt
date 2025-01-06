<?php

declare(strict_types=1);

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Log;

class JWTCodec
{
    /**
     * Encode a payload into a jwt.
     */
    public function encode(array $payload): string
    {
        return JWT::encode($payload, (string) config('jwt.secret_key'), (string) config('jwt.algorithm'));
    }

    public function decode(string $jwt): ?array
    {
        try {
            $payload = JWT::decode($jwt, new Key((string) config('jwt.secret_key'), (string) config('jwt.algorithm')));

            return (array) $payload;
        } catch (\Throwable $exception) {
            Log::error('JWT decoding failed', [
                'message' => $exception->getMessage(),

                'trace' => $exception->getTraceAsString(),
            ]);

            return null;
        }
    }
}
