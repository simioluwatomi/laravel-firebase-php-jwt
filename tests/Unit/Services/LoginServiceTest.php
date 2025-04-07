<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Events\TwoFactorChallengeInitiated;
use App\Models\User;
use App\Services\JWTCodec;
use App\Services\LoginService;
use App\Support\DataTransferObjects\AuthenticationResponse;
use App\Support\DataTransferObjects\LoginDataTransferObject;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LoginServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_throws_exception_for_invalid_credentials()
    {
        $loginData = new LoginDataTransferObject('test@example.com', 'password123');

        static::expectException(AuthenticationException::class);
        static::expectExceptionMessage(trans('auth.failed'));

        $service = app()->make(LoginService::class);

        $service->login($loginData);
    }

    public function test_it_throws_exception_when_token_encoding_fails()
    {
        $user = User::factory()->twoFactorDisabled()->create();

        $loginData = new LoginDataTransferObject($user->email, 'password');

        $codec = $this->mock(JWTCodec::class);

        $service = new LoginService($codec);

        $codec->shouldReceive('encode')
            ->once()
            ->andReturn(null);

        static::expectException(AuthenticationException::class);
        static::expectExceptionMessage(trans('auth.failed'));

        $service->login($loginData);
    }

    public function test_it_returns_authentication_response_for_users_without_two_factor()
    {
        Event::fake();

        Carbon::setTestNow(now());

        $user = User::factory()->twoFactorDisabled()->create();

        $loginData = new LoginDataTransferObject($user->email, 'password');

        $service = app()->make(LoginService::class);

        $response = $service->login($loginData);

        $responseArray = $response->toArray();

        static::assertInstanceOf(AuthenticationResponse::class, $response);
        static::assertNotNull($responseArray['user']);
        static::assertNotNull($responseArray['token']['access_token']);
        static::assertNotNull($responseArray['token']['expires_at']);
        static::assertNull($responseArray['two_factor_method']);
        static::assertTrue(now()->diffInMinutes(now()->parse((int) $responseArray['token']['expires_at'])) > 58);
        static::assertFalse($responseArray['requires_two_factor_challenge']);
        Event::assertDispatched(fn (Authenticated $event) => $event->user->is($user) && $event->guard = 'jwt');
        Event::assertNotDispatched(TwoFactorChallengeInitiated::class);
    }

    public function test_it_returns_authentication_response_for_2fa_enabled_user()
    {
        Event::fake();

        $user = User::factory()->emailTwoFactorEnabled()->create();

        $loginData = new LoginDataTransferObject($user->email, 'password');

        $service = app()->make(LoginService::class);

        $response = $service->login($loginData);

        $responseArray = $response->toArray();

        static::assertInstanceOf(AuthenticationResponse::class, $response);
        static::assertNull($responseArray['user']);
        static::assertNotNull($responseArray['token']['access_token']);
        static::assertNotNull($responseArray['token']['expires_at']);
        static::assertEquals('email', $responseArray['two_factor_method']);
        static::assertTrue(now()->diffInMinutes(now()->parse((int) $responseArray['token']['expires_at'])) < 20);
        static::assertTrue($responseArray['requires_two_factor_challenge']);
        Event::assertDispatched(fn (TwoFactorChallengeInitiated $event) => $event->user->is($user));
        Event::assertNotDispatched(Authenticated::class);
    }
}
