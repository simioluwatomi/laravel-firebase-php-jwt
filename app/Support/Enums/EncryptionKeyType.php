<?php

declare(strict_types=1);

namespace App\Support\Enums;

enum EncryptionKeyType
{
    case PUBLIC;

    case PRIVATE;

    public function value(): string
    {
        return strtolower($this->name);
    }
}
