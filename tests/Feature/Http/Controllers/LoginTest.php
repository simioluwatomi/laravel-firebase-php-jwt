<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_not_log_in()
    {
        $this->withoutExceptionHandling();

        $this->expectException(AuthorizationException::class);

        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson(route('api.login'));

        $response->assertStatus(401);
    }

    public function test_email_is_required_for_login()
    {
        $response = $this->postJson(route('api.login'), ['password' => 'password']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_email_must_be_valid_email_format()
    {
        $response = $this->postJson(route('api.login'), ['email' => 'invalid-email', 'password' => 'password']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_password_is_required_for_login()
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('api.login'), ['email' => $user->email]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_registered_guest_users_can_log_in()
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('api.login'), ['email' => $user->email, 'password' => 'password']);

        $response->assertStatus(200);
        $response->assertJsonPath('data.user.id', $user->id);
        $response->assertJsonPath('data.user.name', $user->name);
        $response->assertJsonPath('data.user.email', $user->email);
        $response->assertJsonPath('data.token.type', 'Bearer');
    }
}
