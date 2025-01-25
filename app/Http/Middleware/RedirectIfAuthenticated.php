<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated as BaseRedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated extends BaseRedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(Request): (Response) $next
     *
     * @throws \Throwable
     */
    public function handle(Request $request, \Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                throw_if(
                    $request->expectsJson(),
                    new AuthorizationException('Authenticated users cannot perform this action.')
                );

                return redirect($this->redirectTo($request));
            }
        }

        return $next($request);
    }
}
