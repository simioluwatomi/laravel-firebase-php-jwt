<?php

namespace App\Support\DataTransferObjects;

use App\Http\Resources\UserResource;
use App\Models\User;

final class AuthenticationResponse
{
    public function __construct(
        private User $user,
        private string $token,
        private \DateTimeInterface $expiresAt,
    ) {}

    public function toArray(): array
    {
        return [
            'user' => new UserResource($this->user->withoutRelations()),
            'token' => [
                'type' => 'Bearer',
                'access_token' => $this->token,
                'expires_at' => (string) $this->expiresAt->getTimestamp(),
            ],
        ];
    }
}
