<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TwoFactorChallengeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_users_can_not_verify_two_factor_code()
    {
        $this->withoutExceptionHandling();

        $this->expectException(AuthenticationException::class);

        $response = $this->postJson(route('api.two-factor.verify'));

        $response->assertStatus(401);
    }

    public function test_code_is_required_to_complete_two_factor_verification()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson(route('api.two-factor.verify'));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }

    public function test_users_with_two_factor_disabled_can_not_complete_two_factor_verification()
    {
        $this->withoutExceptionHandling();

        $this->expectException(AuthorizationException::class);

        $user = User::factory()->twoFactorDisabled()->create();

        $response = $this->actingAs($user, 'api')->postJson(route('api.two-factor.verify'), ['code' => '123456']);

        $response->assertStatus(403);
    }

    public function test_invalid_code_can_not_complete_two_factor_verification()
    {
        $user = User::factory()->emailTwoFactorEnabled()->create();

        $response = $this->actingAs($user, 'api')->postJson(route('api.two-factor.verify'), ['code' => '123456']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }

    public function test_authenticated_users_with_valid_code_can_complete_two_factor_verification()
    {
        Event::fake();

        $user = User::factory()->emailTwoFactorEnabled()->create();

        $service = new TwoFactorAuthenticationService();

        $otp = $service->generateEmailOTP($user);

        $response = $this->actingAs($user, 'api')->postJson(route('api.two-factor.verify'), ['code' => $otp]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.user.id', $user->id);
        $response->assertJsonPath('data.user.name', $user->name);
        $response->assertJsonPath('data.user.email', $user->email);
        $response->assertJsonPath('data.token.type', 'Bearer');
    }
}
