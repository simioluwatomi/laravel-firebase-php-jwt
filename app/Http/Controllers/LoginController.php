<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Builders\JwtPayloadBuilder;
use App\Guards\JwtGuard;
use App\Http\Resources\UserResource;
use App\Services\JWTCodec;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /**
     * @throws \Throwable
     */
    public function __invoke(Request $request, JWTCodec $codec): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        /** @var JwtGuard $guard */
        $guard = auth()->guard('api');

        throw_if(
            ! $guard->validate($credentials),
            new AuthenticationException(trans('auth.failed'))
        );

        $user = $guard->getLastAttempted();

        $payload = (new JwtPayloadBuilder($user))
            ->issuedNow()
            ->getPayload();

        $token = $codec->encode($payload);

        throw_if(
            $token === null,
            new AuthenticationException(trans('auth.failed'))
        );

        event(new Authenticated('jwt', $user));

        return response()->json(['data' => [
            'user' => new UserResource($user->withoutRelations()),
            'token' => [
                'type' => 'Bearer',
                'access_token' => $token,
                'expires_at' => (string) $payload['exp'],
            ],
        ]]);
    }
}
