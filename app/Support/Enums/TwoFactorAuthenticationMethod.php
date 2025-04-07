<?php

declare(strict_types=1);

namespace App\Support\Enums;

enum TwoFactorAuthenticationMethod
{
    case EMAIL;

    case AUTHENTICATOR_APP;

    public function otpPeriod(): int
    {
        return match ($this) {
            self::EMAIL => 300,
            default => 30,
        };
    }
}
