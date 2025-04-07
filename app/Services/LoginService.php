<?php

declare(strict_types=1);

namespace App\Services;

use App\Builders\JwtPayloadBuilder;
use App\Events\TwoFactorChallengeInitiated;
use App\Guards\JwtGuard;
use App\Models\User;
use App\Support\DataTransferObjects\AuthenticationResponse;
use App\Support\DataTransferObjects\LoginDataTransferObject;
use App\Support\Enums\JsonWebTokenScope;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\Carbon;

class LoginService
{
    public function __construct(private JWTCodec $codec) {}

    /**
     * @throws \Throwable
     */
    public function login(LoginDataTransferObject $data): AuthenticationResponse
    {
        /** @var JwtGuard $guard */
        $guard = auth()->guard('api');

        throw_if(
            ! $guard->validate($data->toArray()),
            new AuthenticationException(trans('auth.failed'))
        );

        /** @var User $user */
        $user = $guard->getLastAttempted();

        $builder = (new JwtPayloadBuilder($user))->issuedNow();

        if ($user->hasTwoFactorAuthenticationEnabled()) {
            $builder->lifetimeInMinutes(config('jwt.two_factor_token_lifetime'))
                ->addScope(JsonWebTokenScope::TWO_FACTOR_SCOPE);
        } else {
            $builder->addScope(JsonWebTokenScope::ALL_SCOPES);
        }

        $payload = $builder->getPayload();

        $token = $this->codec->encode($payload);

        throw_if(
            $token === null,
            new AuthenticationException(trans('auth.failed'))
        );

        $response = new AuthenticationResponse(
            $user,
            $token,
            Carbon::parse($payload['exp']),
            $user->hasTwoFactorAuthenticationEnabled()
        );

        $user->hasTwoFactorAuthenticationEnabled()
            ? event(new TwoFactorChallengeInitiated($user))
            : event(new Authenticated('jwt', $user));

        return $response;
    }
}
