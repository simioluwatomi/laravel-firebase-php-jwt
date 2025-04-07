<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\TwoFactorChallengeInitiated;
use App\Guards\JwtGuard;
use App\Listeners\HandleTwoFactorChallenge;
use App\Services\JWTCodec;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(JWTCodec::class, fn ($app) => new JWTCodec());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::extend('jwt', function (Application $app, string $name, array $config) {
            return new JwtGuard(
                Auth::createUserProvider($config['provider']),
                $this->app['request'],
                $app->make(JWTCodec::class)
            );
        });

        Event::listen(
            TwoFactorChallengeInitiated::class,
            HandleTwoFactorChallenge::class,
        );
    }
}
