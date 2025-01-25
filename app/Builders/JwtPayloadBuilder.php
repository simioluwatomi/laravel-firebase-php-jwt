<?php

declare(strict_types=1);

namespace App\Builders;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class JwtPayloadBuilder
{
    private array $payload = [];

    public function __construct(Authenticatable $user)
    {
        $this->forSubject($user)
            ->generateIdentifier()
            ->issuedBy(config('app.url'))
            ->lifetimeInHours(config('jwt.token_lifetime'));
    }

    public function withClaim(string $claim, bool|float|int|string $value): self
    {
        $this->payload[$claim] = $value;

        return $this;
    }

    public function withClaims(array $claims): self
    {
        foreach ($claims as $key => $value) {
            $this->withClaim($key, $value);
        }

        return $this;
    }

    public function lifetimeInHours(int $hours): self
    {
        return $this->withClaim('exp', Carbon::now()->addHours(abs($hours))->getTimestamp());
    }

    public function issuedNow(): self
    {
        $now = Carbon::now();

        return $this->withClaim('iat', $now->getTimestamp())
            ->withClaim('nbf', $now->getTimestamp());
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    private function forSubject(Authenticatable $user): self
    {
        return $this->withClaim('sub', $user->getAuthIdentifier());
    }

    private function generateIdentifier(): self
    {
        return $this->withClaim('jti', Str::uuid()->getHex()->toString());
    }

    private function issuedBy(string $issuer): self
    {
        return $this->withClaim('iss', $issuer);
    }
}
