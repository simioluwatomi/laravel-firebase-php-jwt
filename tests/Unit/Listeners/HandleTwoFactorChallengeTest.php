<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\TwoFactorChallengeInitiated;
use App\Listeners\HandleTwoFactorChallenge;
use App\Models\User;
use App\Notifications\TwoFactorChallengeNotification;
use App\Services\TwoFactorAuthenticationService;
use App\Support\Enums\TwoFactorAuthenticationMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
use Tests\TestCase;

class HandleTwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;
    private MockInterface $twoFactorServiceMock;
    private HandleTwoFactorChallenge $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->twoFactorServiceMock = \Mockery::mock(TwoFactorAuthenticationService::class);

        $this->listener = new HandleTwoFactorChallenge($this->twoFactorServiceMock);
    }

    public function test_it_does_nothing_when_user_uses_two_factor_authenticator_app()
    {
        $user = User::factory()->create(['two_factor_method' => TwoFactorAuthenticationMethod::AUTHENTICATOR_APP]);

        $event = new TwoFactorChallengeInitiated($user);

        $this->twoFactorServiceMock->shouldNotReceive('generateEmailOTP');

        $this->listener->handle($event);
    }

    public function test_it_generates_and_sends_otp_when_user_does_not_use_auth_app()
    {
        Notification::fake();

        $user = User::factory()->emailTwoFactorEnabled()->create();

        $this->twoFactorServiceMock->shouldReceive('generateEmailOTP')
            ->once()
            ->with($user)
            ->andReturn('123456');

        $event = new TwoFactorChallengeInitiated($user);

        $this->listener->handle($event);

        Notification::assertSentTo($user, TwoFactorChallengeNotification::class);
    }

    public function it_handles_exceptions_from_generate_email_otp()
    {
        $user = User::factory()->emailTwoFactorEnabled()->create();

        $exception = new \Exception('Failed to generate OTP');

        $this->twoFactorServiceMock->shouldReceive('generateEmailOTP')
            ->once()
            ->with($user)
            ->andThrow($exception);

        $event = new TwoFactorChallengeInitiated($user);

        static::expectException(\Exception::class);
        static::expectExceptionMessage('Failed to generate OTP');

        $this->listener->handle($event);
    }
}
