<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\JWTCodec;
use App\Services\TwoFactorAuthenticationService;
use App\Support\DataTransferObjects\AuthenticationResponse;
use App\Support\Enums\TwoFactorAuthenticationMethod;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use Tests\TestCase;

class TwoFactorAuthenticationServiceTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_it_returns_false_when_two_factor_secret_is_null()
    {
        $user = User::factory()->emailTwoFactorEnabled()->create(['two_factor_secret' => null]);

        $result = $this->service->verifyOneTimePassword($user, '123456');

        static::assertFalse($result);
    }

    public function test_it_verifies_one_time_passwords()
    {
        $user = User::factory()->emailTwoFactorEnabled()->create();

        $otp = $this->service->generateEmailOTP($user);

        $result = $this->service->verifyOneTimePassword($user, $otp);

        static::assertTrue($result);
    }

    public function test_it_returns_false_for_invalid_otp()
    {
        $user = User::factory()->emailTwoFactorEnabled()->create();

        $result = $this->service->verifyOneTimePassword($user, 'invalid-otp');

        static::assertFalse($result);
    }

    public function test_complete_two_factor_challenge_throws_exception_when_two_factor_is_not_enabled()
    {
        $user = User::factory()->twoFactorDisabled()->create();

        static::expectException(AuthorizationException::class);
        static::expectExceptionMessage('Two factor authentication is not enabled for this user.');

        $this->service->completeTwoFactorChallenge($user, '123456');
    }

    public function test_complete_two_factor_challenge_throws_exception_when_otp_is_invalid()
    {
        $user = User::factory()->emailTwoFactorEnabled()->create();

        static::expectException(ValidationException::class);

        $this->service->completeTwoFactorChallenge($user, '123456');
    }

    public function test_complete_two_factor_challenge_throws_exception_when_token_generation_fails()
    {
        $user = User::factory()->emailTwoFactorEnabled()->create();

        $codec = $this->mock(JWTCodec::class);

        $codec->shouldReceive('encode')
            ->once()
            ->andReturn(null);

        $otp = $this->service->generateEmailOTP($user);

        static::expectException(AuthenticationException::class);
        static::expectExceptionMessage('Failed to generate authentication token.');

        $this->service->completeTwoFactorChallenge($user, $otp);
    }

    public function test_complete_two_factor_challenge_returns_response_and_fires_event_when_successful()
    {
        Event::fake();

        $user = User::factory()->emailTwoFactorEnabled()->create();

        $otp = $this->service->generateEmailOTP($user);

        $response = $this->service->completeTwoFactorChallenge($user, $otp);

        $responseArray = $response->toArray();

        static::assertInstanceOf(AuthenticationResponse::class, $response);
        static::assertNotNull($responseArray['user']);
        static::assertNotNull($responseArray['token']['access_token']);
        static::assertNotNull($responseArray['token']['expires_at']);
        static::assertNull($responseArray['two_factor_method']);
        static::assertTrue(now()->diffInMinutes(now()->parse((int) $responseArray['token']['expires_at'])) > 58);
        static::assertFalse($responseArray['requires_two_factor_challenge']);
        Event::assertDispatched(fn (Authenticated $event) => $event->user->is($user) && $event->guard = 'jwt');
    }
}
