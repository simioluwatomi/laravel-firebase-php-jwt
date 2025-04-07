<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use App\Support\Enums\TwoFactorAuthenticationMethod;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use Tests\TestCase;

class TwoFactorAuthenticationServiceTest extends TestCase
{
    private TwoFactorAuthenticationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TwoFactorAuthenticationService();

        config()->set('app.name', 'Test App');
    }

    public function test_it_generates_secret_key_with_default_length()
    {
        $secret = $this->service->generateSecretKey();

        static::assertEquals(56, strlen($secret));
        static::assertNotFalse(Base32::decodeUpper($secret));
    }

    public function test_it_generates_secret_key_with_custom_length()
    {
        $secret = $this->service->generateSecretKey(16);

        static::assertEquals(32, strlen($secret));
        static::assertNotFalse(Base32::decodeUpper($secret));
    }

    public function test_it_throws_exception_for_invalid_secret_length()
    {
        static::expectException(\UnexpectedValueException::class);
        static::expectExceptionMessage('The length of the secret must be a power of two and at least 16.');

        $this->service->generateSecretKey(15);
        $this->service->generateSecretKey(24);
    }

    public function test_it_creates_totp_instance()
    {
        $secret = $this->service->generateSecretKey();
        $email = 'test@example.com';
        $method = TwoFactorAuthenticationMethod::EMAIL;

        $totp = $this->service->createTOTP($secret, $email, $method);

        static::assertInstanceOf(TOTP::class, $totp);
        static::assertEquals('Test App', $totp->getIssuer());
        static::assertEquals($email, $totp->getLabel());
        static::assertEquals($method->otpPeriod(), $totp->getPeriod());
        static::assertEquals(6, $totp->getDigits());
        static::assertEquals('sha1', $totp->getDigest());
    }

    public function test_it_generates_email_otp_for_user_with_existing_secret()
    {
        $secret = $this->service->generateSecretKey();

        $user = User::factory()->emailTwoFactorEnabled()
            ->make(['two_factor_secret' => $secret]);

        $otp = $this->service->generateEmailOTP($user);

        static::assertEquals(6, strlen($otp));
    }

    public function test_it_generates_email_otp_for_user_without_existing_secret()
    {
        $user = User::factory()->emailTwoFactorEnabled()->create();

        $otp = $this->service->generateEmailOTP($user);

        static::assertEquals(6, strlen($otp));
        static::assertNotNull($user->two_factor_secret);
    }
}
