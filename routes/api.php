<?php

declare(strict_types=1);

use App\Http\Controllers\LoginController;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::name('api.')->group(function () {
    Route::middleware('guest')->post('/login', LoginController::class)->name('login');

    Route::middleware('auth:api')->get('/user', function (Request $request) {
        $user = $request->user();

        return response()->json(['data' => new UserResource($user->withoutRelations())]);
    })->name('user');
});

