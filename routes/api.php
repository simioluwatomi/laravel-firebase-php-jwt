<?php

declare(strict_types=1);

use App\Http\Controllers\LoginController;
use App\Http\Controllers\TwoFactorChallengeController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::middleware('guest')->post('/login', LoginController::class)->name('login');

    Route::middleware(['auth:api'])
        ->post('/two-factor/verify', TwoFactorChallengeController::class)
        ->name('two-factor.verify');

    Route::middleware('auth:api')->get('/user', function (Request $request) {
        $user = $request->user();

        return response()->json(['data' => new UserResource($user->withoutRelations())]);
    })->name('user');
});
