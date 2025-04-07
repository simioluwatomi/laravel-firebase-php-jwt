<?php

declare(strict_types=1);

namespace Tests\Feature\Guards;

use App\Guards\JwtGuard;
use App\Models\User;
use App\Services\JWTCodec;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class JwtGuardTest extends TestCase
{
    use RefreshDatabase;
    public function test_user_returns_null_when_no_bearer_token()
    {
        $request = \Mockery::mock(Request::class);
        $request->shouldReceive('bearerToken')
            ->once()
            ->andReturn(null);

        $guard = new JwtGuard(
            Auth::createUserProvider('users'),
            $request,
            new JWTCodec(),
        );

        static::assertNull($guard->user());
    }

    public function test_user_returns_null_with_invalid_token()
    {
        $codec = \Mockery::mock(JWTCodec::class);
        $codec->shouldReceive('decode')
            ->once()
            ->with('invalid.token')
            ->andReturn([]);

        $request = \Mockery::mock(Request::class);
        $request->shouldReceive('bearerToken')
            ->once()
            ->andReturn('invalid.token');

        $guard = new JwtGuard(
            Auth::createUserProvider('users'),
            $request,
            $codec,
        );

        static::assertNull($guard->user());
    }

    public function test_user_returns_an_authenticatable_when_already_set()
    {
        $guard = new JwtGuard(
            Auth::createUserProvider('users'),
            \Mockery::mock(Request::class),
            new JWTCodec(),
        );

        $factory = User::factory()->create();

        $guard->setUser($factory);

        static::assertSame($factory, $guard->user());
    }

    public function test_user_returns_authenticatable_with_valid_token()
    {
        $factory = User::factory()->create();

        $codec = \Mockery::mock(JWTCodec::class);
        $codec->shouldReceive('decode')
            ->once()
            ->with('valid.token')
            ->andReturn(['sub' => $factory->id]);

        $request = \Mockery::mock(Request::class);
        $request->shouldReceive('bearerToken')->once()->andReturn('valid.token');

        $guard = new JwtGuard(
            Auth::createUserProvider('users'),
            $request,
            $codec,
        );

        static::assertNotNull($guard->user());
        static::assertEquals($factory->id, $guard->user()->getAuthIdentifier());
    }

    public function test_validate_returns_false_for_empty_credentials()
    {
        $guard = new JwtGuard(
            Auth::createUserProvider('users'),
            $this->app['request'],
            new JWTCodec(),
        );

        static::assertFalse($guard->validate([]));

        static::assertNull($guard->getLastAttempted());
    }

    public function test_validate_returns_false_for_invalid_credentials()
    {
        $guard = new JwtGuard(
            Auth::createUserProvider('users'),
            $this->app['request'],
            new JWTCodec(),
        );

        static::assertFalse($guard->validate(['random-property' => 'arya@stark.en']));

        static::assertNull($guard->getLastAttempted());
    }

    public function test_validate_returns_true_for_valid_credentials()
    {
        $guard = new JwtGuard(
            Auth::createUserProvider('users'),
            $this->app['request'],
            new JWTCodec(),
        );

        $factory = User::factory()->create();

        $result = $guard->validate(['email' => $factory->email, 'password' => 'password']);

        static::assertTrue($result);
    }

    public function test_validate_sets_last_attempted()
    {
        $guard = new JwtGuard(
            Auth::createUserProvider('users'),
            $this->app['request'],
            new JWTCodec(),
        );

        static::assertNull($guard->getLastAttempted());

        $factory = User::factory()->create();

        $guard->validate(['email' => $factory->email, 'password' => 'password']);

        $lastAttempted = $guard->getLastAttempted();

        static::assertNotNull($lastAttempted);
        static::assertEquals($factory->id, $lastAttempted->getAuthIdentifier());
    }
}
