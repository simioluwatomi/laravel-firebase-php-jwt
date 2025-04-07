<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\Enums\TwoFactorAuthenticationMethod;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;

class TwoFactorAuthenticationService
{
    /**
     * Generate a time-based one-time password for the user.
     *
     * @throws \Throwable
     */
    public function generateEmailOTP(User $user): string
    {
        $secret = $user->two_factor_secret ?? $this->generateSecretKey();

        if ($user->two_factor_secret === null) {
            $user->update(['two_factor_secret' => $secret]);
        }

        return $this->createTOTP($secret, $user->email, $user->two_factor_method)
            ->now();
    }

    /**
     * Generate a TOTP secret of a given length in bytes.
     * Length must be a power of 2 and at least 16.
     *
     * @param int $length
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function generateSecretKey(int $length = 32): string
    {
        throw_if(
            $length < 16 || ($length & ($length - 1)) !== 0,
            new \UnexpectedValueException('The length of the secret must be a power of two and at least 16.')
        );

        return Base32::encodeUpper(random_bytes($length));
    }

    /**
     * Create a TOTP instance.
     */
    public function createTOTP(string $secret, string $label, TwoFactorAuthenticationMethod $method): TOTP
    {
        $totp = TOTP::create($secret, $method->otpPeriod(), 'sha1', 6);

        $totp->setIssuer(config('app.name'));

        $totp->setLabel($label);

        return $totp;
    }
}
