<?php

declare(strict_types=1);

namespace Feature\Http\Middleware;

use App\Http\Middleware\RedirectIfAuthenticated;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RedirectIfAuthenticatedTest extends TestCase
{
    use RefreshDatabase;
    public function test_it_allows_request_when_user_is_not_authenticated()
    {
        $request = Request::create('/login', 'GET');

        $response = (new RedirectIfAuthenticated())->handle($request, fn () => response('Next'));

        static::assertEquals('Next', $response->getContent());
    }

    public function test_it_throws_authorization_exception_for_json_request()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $request = Request::create('/login', 'GET');
        $request->headers->set('Accept', 'application/json');

        static::expectException(AuthorizationException::class);
        static::expectExceptionMessage('Authenticated users cannot perform this action.');

        (new RedirectIfAuthenticated())->handle($request, fn () => response('Next'));
    }

    public function test_it_redirects_for_web_request_when_authenticated()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $request = Request::create('/login', 'GET');
        $response = (new RedirectIfAuthenticated())->handle($request, fn () => response('Next'));

        static::assertEquals(302, $response->getStatusCode());
    }

    public function test_it_handles_multiple_guards()
    {
        $user = User::factory()->create();
        Auth::guard('web')->login($user);

        $request = Request::create('/login', 'GET');
        $request->headers->set('Accept', 'application/json');

        static::expectException(AuthorizationException::class);

        (new RedirectIfAuthenticated())->handle($request, fn () => response('Next'), 'web', 'api');
    }
}
