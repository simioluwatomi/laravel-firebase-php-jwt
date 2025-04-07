<?php

declare(strict_types=1);

namespace App\Support\DataTransferObjects;

use App\Http\Resources\UserResource;
use App\Models\User;

final class AuthenticationResponse
{
    public function __construct(
        private User $user,
        private string $token,
        private \DateTimeInterface $expiresAt,
        private bool $enforceTwoFactorAuthentication
    ) {}

    public function toArray(): array
    {
        return [
            'user' => $this->enforceTwoFactorAuthentication ? null : new UserResource($this->user->withoutRelations()),
            'requires_two_factor_challenge' => $this->enforceTwoFactorAuthentication,
            'two_factor_method' => $this->enforceTwoFactorAuthentication ? strtolower($this->user->two_factor_method->name) : null,
            'token' => [
                'type' => 'Bearer',
                'access_token' => $this->token,
                'expires_at' => (string) $this->expiresAt->getTimestamp(),
            ],
        ];
    }
}
