<?php

declare(strict_types=1);

namespace App\Support\Enums;

enum JsonWebTokenScope: string
{
    case ALL_SCOPES = '*';

    case TWO_FACTOR_SCOPE = '2fa-challenge';

    public function isValid(): bool
    {
        return ! $this->isInvalid();
    }

    public function isInvalid(): bool
    {
        return empty($this->value);
    }
}
