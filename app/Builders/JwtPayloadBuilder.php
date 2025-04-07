<?php

declare(strict_types=1);

namespace App\Builders;

use App\Support\Enums\JsonWebTokenScope;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class JwtPayloadBuilder
{
    private array $payload = [];

    private array $scopes = [];

    public function __construct(Authenticatable $user)
    {
        $this->forSubject($user)
            ->generateIdentifier()
            ->issuedBy(config('app.url'))
            ->lifetimeInMinutes(config('jwt.token_lifetime'));
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

    public function lifetimeInMinutes(int $minutes): self
    {
        return $this->withClaim('exp', Carbon::now()->addMinutes(abs($minutes))->getTimestamp());
    }

    public function issuedNow(): self
    {
        $now = Carbon::now();

        return $this->withClaim('iat', $now->getTimestamp())
            ->withClaim('nbf', $now->getTimestamp());
    }

    public function addScope(JsonWebTokenScope $scope): self
    {
        if ($scope->isInvalid() || in_array($scope->value, $this->scopes, true)) {
            return $this;
        }

        if ($scope === JsonWebTokenScope::TWO_FACTOR_SCOPE && in_array(JsonWebTokenScope::ALL_SCOPES->value, $this->scopes, true)) {
            throw new \InvalidArgumentException('Can not add 2FA challenge scope when all scopes are granted.');
        }

        if ($scope === JsonWebTokenScope::ALL_SCOPES && in_array(JsonWebTokenScope::TWO_FACTOR_SCOPE->value, $this->scopes, true)) {
            throw new \InvalidArgumentException('Can not grant all scopes when 2FA challenge scope is present.');
        }

        if ($scope === JsonWebTokenScope::ALL_SCOPES) {
            $this->scopes = [JsonWebTokenScope::ALL_SCOPES->value];

            return $this->withClaim('scope', implode(' ', $this->scopes));
        }

        $this->scopes[] = $scope->value;

        return $this->withClaim('scope', implode(' ', $this->scopes));
    }

    public function removeScope(JsonWebTokenScope $scope): self
    {
        $this->scopes = array_filter($this->scopes, fn ($element) => $element !== $scope->value);

        return empty($this->scopes)
            ? $this->withoutClaim('scope')
            : $this->withClaim('scope', implode(' ', $this->scopes));
    }

    public function withoutClaim(string $claim): self
    {
        unset($this->payload[$claim]);

        return $this;
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
