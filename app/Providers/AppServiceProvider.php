<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\JWTCodec;
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
    public function boot(): void {}
}
