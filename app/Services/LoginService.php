<?php

namespace App\Services;

use App\Builders\JwtPayloadBuilder;
use App\Guards\JwtGuard;
use App\Models\User;
use App\Support\DataTransferObjects\AuthenticationResponse;
use App\Support\DataTransferObjects\LoginDataTransferObject;
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

        $payload = (new JwtPayloadBuilder($user))
            ->issuedNow()
            ->getPayload();

        $token = $this->codec->encode($payload);

        throw_if(
            $token === null,
            new AuthenticationException(trans('auth.failed'))
        );

        $response = new AuthenticationResponse(
            $user,
            $token,
            Carbon::parse($payload['exp']),
        );

        event(new Authenticated('jwt', $user));

        return $response;
    }
}
