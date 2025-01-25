<?php

declare(strict_types=1);

namespace App\Guards;

use App\Services\JWTCodec;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Timebox;

class JwtGuard implements Guard
{
    use GuardHelpers;

    protected Request $request;
    protected JWTCodec $codec;
    protected int $minimumExecutionTime = 200_000;
    protected ?Timebox $timebox;
    protected ?Authenticatable $lastAttempted = null;

    public function __construct(UserProvider $provider, Request $request, JWTCodec $codec, ?Timebox $timebox = null)
    {
        $this->provider = $provider;
        $this->request = $request;
        $this->codec = $codec;
        $this->timebox = $timebox ?: new Timebox();
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Throwable
     */
    public function user(): ?Authenticatable
    {
        return $this->user ??= $this->getUserFromToken($this->request->bearerToken());
    }

    /**
     * {@inheritDoc}
     */
    public function validate(array $credentials = []): bool
    {
        if (empty($credentials)) {
            return false;
        }

        try {
            $user = $this->provider->retrieveByCredentials($credentials);

            $hasValidCredentials = $this->hasValidCredentials($user, $credentials);

            if ($hasValidCredentials) {
                $this->lastAttempted = $user;
            }

            return $hasValidCredentials;
        } catch (\Throwable $exception) {
            Log::error('Jwt guard credentials validation failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return false;
        }
    }

    public function getLastAttempted(): ?Authenticatable
    {
        return $this->lastAttempted;
    }

    /**
     * @throws \Throwable
     */
    protected function getUserFromToken(?string $bearerToken): ?Authenticatable
    {
        if (empty($bearerToken)) {
            return null;
        }

        $decodedToken = $this->codec->decode($bearerToken);

        if (empty($decodedToken) || empty($decodedToken['sub'])) {
            return null;
        }

        return $this->provider->retrieveById($decodedToken['sub']);
    }

    /**
     * @throws \Throwable
     */
    protected function hasValidCredentials(?Authenticatable $user, array $credentials): bool
    {
        return $this->timebox->call(function (Timebox $timebox) use ($user, $credentials) {
            $validated = ! is_null($user) && $this->provider->validateCredentials($user, $credentials);

            if ($validated) {
                $timebox->returnEarly();
            }

            return $validated;
        }, $this->minimumExecutionTime);
    }
}
