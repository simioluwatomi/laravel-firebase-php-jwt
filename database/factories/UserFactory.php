<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Support\Enums\TwoFactorAuthenticationMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that two factor should be disabled for the model.
     */
    public function twoFactorDisabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_disabled_at' => now(),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_method' => null,
        ]);
    }

    /**
     * Indicate that email two factor should be enabled for the model.
     */
    public function emailTwoFactorEnabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_disabled_at' => null,
            'two_factor_method' => TwoFactorAuthenticationMethod::EMAIL,
        ]);
    }
}
