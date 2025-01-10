<?php

declare(strict_types=1);

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class JWTCodec
{
    /**
     * Encode a payload into a jwt.
     *
     * @throws \Throwable
     */
    public function encode(array $payload): ?string
    {
        $disk = Storage::build(['driver' => 'local', 'root' => storage_path('keys')]);

        $filename = (string) config('jwt.encryption_keys.private_key_filename');

        throw_if(
            ! $disk->exists($filename),
            new FileNotFoundException(sprintf('Could not find the jwt private key file: %s', $filename))
        );

        $key = $disk->get($filename);

        try {
            return JWT::encode($payload, $key, config('jwt.algorithm'));
        } catch (\Throwable $exception) {
            Log::error('JWT encoding failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Decode a jwt into an array.
     *
     * @throws \Throwable
     */
    public function decode(string $jwt): ?array
    {
        $disk = Storage::build(['driver' => 'local', 'root' => storage_path('keys')]);

        $filename = (string) config('jwt.encryption_keys.public_key_filename');

        throw_if(
            ! $disk->exists($filename),
            new FileNotFoundException(sprintf('Could not find the jwt public key: %s', $filename))
        );

        $key = $disk->get($filename);

        try {
            $payload = JWT::decode($jwt, new Key($key, (string) config('jwt.algorithm')));

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
