<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LoginService;
use App\Support\DataTransferObjects\LoginDataTransferObject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    /**
     * @throws \Throwable
     */
    public function __invoke(Request $request, LoginService $service): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $data = new LoginDataTransferObject($request->input('email'), $request->input('password'));

        $response = $service->login($data);

        return response()->json(['data' => $response->toArray()], Response::HTTP_OK);
    }
}
