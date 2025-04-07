<?php

declare(strict_types=1);

namespace Tests\Feature\Builders;

use App\Builders\JwtPayloadBuilder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class JwtPayloadBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_payload_with_default_claims()
    {
        config()->set('app.url', 'https://example.com');

        $user = User::factory()->create();

        $payload = (new JwtPayloadBuilder($user))->getPayload();

        static::assertArrayHasKey('sub', $payload);
        static::assertEquals($user->id, $payload['sub']);
        static::assertArrayHasKey('jti', $payload);
        static::assertNotNull($payload['jti']);
        static::assertArrayHasKey('iss', $payload);
        static::assertEquals('https://example.com', $payload['iss']);
        static::assertArrayHasKey('exp', $payload);
    }

    public function test_it_adds_custom_claim()
    {
        $user = User::factory()->create();

        $payload = (new JwtPayloadBuilder($user))
            ->withClaim('custom', 'value')
            ->getPayload();

        static::assertEquals('value', $payload['custom']);
    }

    public function test_it_adds_multiple_claims()
    {
        $user = User::factory()->create();

        $payload = (new JwtPayloadBuilder($user))
            ->withClaims(['role' => 'admin', 'scope' => 'read:write'])
            ->getPayload();

        static::assertEquals('admin', $payload['role']);
        static::assertEquals('read:write', $payload['scope']);
    }

    public function test_it_calculates_expiration_correctly()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $user = User::factory()->create();

        $payload = (new JwtPayloadBuilder($user))
            ->lifetimeInMinutes(30)
            ->getPayload();

        static::assertEquals($now->addMinutes(30)->getTimestamp(), $payload['exp']);
    }

    public function test_it_sets_issued_time_claims()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $user = User::factory()->create();

        $payload = (new JwtPayloadBuilder($user))
            ->issuedNow()
            ->getPayload();

        static::assertEquals($now->getTimestamp(), $payload['iat']);
        static::assertEquals($now->getTimestamp(), $payload['nbf']);
    }

    public function test_it_generates_unique_token_identifier()
    {
        $user = User::factory()->create();

        $builder1 = new JwtPayloadBuilder($user);
        $builder2 = new JwtPayloadBuilder($user);

        static::assertNotEquals(
            $builder1->getPayload()['jti'],
            $builder2->getPayload()['jti']
        );
    }
}
