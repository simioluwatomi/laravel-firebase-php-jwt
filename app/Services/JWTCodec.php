<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Enums\EncryptionKeyType;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Log;

class JWTCodec
{
    private ?string $privateKey = null;
    private ?string $publicKey = null;

    /**
     * Encode a payload into a jwt.
     *
     * @throws \Throwable
     */
    public function encode(array $payload): ?string
    {
        $privateKey = $this->getEncryptionKey(EncryptionKeyType::PRIVATE);

        try {
            return JWT::encode($payload, $privateKey, (string) config('jwt.algorithm'));
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
        $publicKey = $this->getEncryptionKey(EncryptionKeyType::PUBLIC);

        try {
            $payload = JWT::decode($jwt, new Key($publicKey, (string) config('jwt.algorithm')));

            return (array) $payload;
        } catch (\Throwable $exception) {
            Log::error('JWT decoding failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Retrieve the encryption key from config or storage.
     *
     * @throws \Throwable
     */
    private function getEncryptionKey(EncryptionKeyType $type): string
    {
        if ($type === EncryptionKeyType::PRIVATE && ! empty($this->privateKey)) {
            return $this->privateKey;
        }

        if ($type === EncryptionKeyType::PUBLIC && ! empty($this->publicKey)) {
            return $this->publicKey;
        }

        $key = (string) config("jwt.encryption_keys.{$type->value()}_key");

        if (empty($key)) {
            $disk = get_encryption_keys_storage_disk();

            $filename = (string) config("jwt.encryption_keys.{$type->value()}_key_filename");

            throw_if(
                ! $disk->exists($filename),
                new FileNotFoundException(sprintf('Could not find the %s key file: %s', $type->value(), $filename))
            );

            $key = $disk->get($filename);
        }

        if ($type === EncryptionKeyType::PRIVATE) {
            $this->privateKey = $key;
        }

        if ($type === EncryptionKeyType::PUBLIC) {
            $this->publicKey = $key;
        }

        return $key;
    }
}
