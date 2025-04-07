<?php

namespace App\Support\Enums;

enum TwoFactorAuthenticationMethod
{
    case EMAIL;

    case AUTHENTICATOR_APP;
}
