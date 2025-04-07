<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorChallengeController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @throws \Throwable
     */
    public function __invoke(Request $request, TwoFactorAuthenticationService $twoFactorService): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        /** @var User $user */
        $user = auth()->guard('api')->user();

        $response = $twoFactorService->completeTwoFactorChallenge($user, $request->input('code'));

        return response()->json(['data' => $response->toArray()], Response::HTTP_OK);
    }
}
