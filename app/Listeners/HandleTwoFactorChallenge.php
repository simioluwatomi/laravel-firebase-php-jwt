<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TwoFactorChallengeInitiated;
use App\Notifications\TwoFactorChallengeNotification;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleTwoFactorChallenge implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(private TwoFactorAuthenticationService $twoFactorService) {}

    /**
     * Handle the event.
     *
     * @throws \Throwable
     */
    public function handle(TwoFactorChallengeInitiated $event): void
    {
        if ($event->user->usesTwoFactorAuthApp()) {
            return;
        }

        $otp = $this->twoFactorService->generateEmailOTP($event->user);

        $event->user->notify(new TwoFactorChallengeNotification($otp));
    }
}
