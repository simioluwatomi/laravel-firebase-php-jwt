<?php

declare(strict_types=1);

namespace Tests\Feature\Builders;

use App\Builders\JwtPayloadBuilder;
use App\Models\User;
use App\Support\Enums\JsonWebTokenScope;
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
        static::assertNotNull($payload['exp']);
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
            ->withClaims(['role' => 'admin', 'scope' => 'read write'])
            ->getPayload();

        static::assertEquals('admin', $payload['role']);
        static::assertEquals('read write', $payload['scope']);
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

    public function test_without_claim_can_not_remove_default_claims()
    {
        config()->set('app.url', 'https://example.com');

        $user = User::factory()->create();

        $payload = (new JwtPayloadBuilder($user))
            ->withoutClaim('sub')
            ->withoutClaim('jti')
            ->withoutClaim('iss')
            ->withoutClaim('exp')
            ->getPayload();

        static::assertArrayHasKey('sub', $payload);
        static::assertEquals($user->id, $payload['sub']);
        static::assertArrayHasKey('jti', $payload);
        static::assertNotNull($payload['jti']);
        static::assertArrayHasKey('iss', $payload);
        static::assertEquals('https://example.com', $payload['iss']);
        static::assertArrayHasKey('exp', $payload);
        static::assertNotNull($payload['exp']);
    }

    public function test_without_claim_removes_non_default_claims()
    {
        $user = User::factory()->create();

        $payload = (new JwtPayloadBuilder($user))
            ->withClaims(['role' => 'admin', 'scope' => 'read write'])
            ->withoutClaim('role')
            ->getPayload();

        static::assertArrayNotHasKey('role', $payload);
    }

    public function test_it_adds_a_single_scope()
    {
        $user = User::factory()->create();
        $builder = new JwtPayloadBuilder($user);

        $payload = $builder->addScope(JsonWebTokenScope::READ_SCOPE)->getPayload();

        static::assertArrayHasKey('scope', $payload);
        static::assertEquals('read', $payload['scope']);
    }

    public function test_it_adds_multiple_scopes()
    {
        $user = User::factory()->create();
        $builder = new JwtPayloadBuilder($user);

        $payload = $builder
            ->addScope(JsonWebTokenScope::READ_SCOPE)
            ->addScope(JsonWebTokenScope::WRITE_SCOPE)
            ->getPayload();

        static::assertArrayHasKey('scope', $payload);
        static::assertEquals('read write', $payload['scope']);
    }

    public function test_it_does_not_add_duplicate_scopes()
    {
        $user = User::factory()->create();
        $builder = new JwtPayloadBuilder($user);

        $payload = $builder
            ->addScope(JsonWebTokenScope::READ_SCOPE)
            ->addScope(JsonWebTokenScope::READ_SCOPE)
            ->getPayload();

        static::assertArrayHasKey('scope', $payload);
        static::assertEquals('read', $payload['scope']);
    }

    public function test_it_does_not_add_invalid_scopes()
    {
        $user = User::factory()->create();
        $builder = new JwtPayloadBuilder($user);

        $payload = $builder->addScope(JsonWebTokenScope::INVALID_SCOPE)->getPayload();

        static::assertArrayNotHasKey('scope', $payload);
    }

    public function test_it_removes_scopes()
    {
        $user = User::factory()->create();
        $builder = new JwtPayloadBuilder($user);

        $payload = $builder
            ->addScope(JsonWebTokenScope::READ_SCOPE)
            ->addScope(JsonWebTokenScope::WRITE_SCOPE)
            ->removeScope(JsonWebTokenScope::READ_SCOPE)
            ->getPayload();

        static::assertArrayHasKey('scope', $payload);
        static::assertEquals('write', $payload['scope']);
    }

    public function test_it_removes_scope_claim_when_all_scopes_removed()
    {
        $user = User::factory()->create();
        $builder = new JwtPayloadBuilder($user);

        $payload = $builder
            ->addScope(JsonWebTokenScope::READ_SCOPE)
            ->removeScope(JsonWebTokenScope::READ_SCOPE)
            ->getPayload();

        static::assertArrayNotHasKey('scope', $payload);
    }

    public function test_it_replaces_existing_scopes_when_all_scopes_added()
    {
        $user = User::factory()->create();
        $builder = new JwtPayloadBuilder($user);

        $payload = $builder
            ->addScope(JsonWebTokenScope::READ_SCOPE)
            ->addScope(JsonWebTokenScope::WRITE_SCOPE)
            ->addScope(JsonWebTokenScope::ALL_SCOPES)
            ->getPayload();

        static::assertArrayHasKey('scope', $payload);
        static::assertEquals('*', $payload['scope']);
    }

    public function test_it_throws_exception_when_adding_two_factor_scope_with_all_scopes()
    {
        $user = User::factory()->create();
        $builder = new JwtPayloadBuilder($user);

        $builder->addScope(JsonWebTokenScope::ALL_SCOPES);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Can not add 2FA challenge scope when all scopes are granted.');

        $builder->addScope(JsonWebTokenScope::TWO_FACTOR_SCOPE);
    }

    public function test_it_throws_exception_when_adding_all_scopes_with_two_factor_scope()
    {
        $user = User::factory()->create();
        $builder = new JwtPayloadBuilder($user);

        $builder->addScope(JsonWebTokenScope::TWO_FACTOR_SCOPE);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Can not grant all scopes when 2FA challenge scope is present.');

        $builder->addScope(JsonWebTokenScope::ALL_SCOPES);
    }

    public function test_removing_scopes_does_not_affect_other_claims()
    {
        $user = User::factory()->create();
        $builder = new JwtPayloadBuilder($user);

        $payload = $builder
            ->withClaim('custom', 'value')
            ->addScope(JsonWebTokenScope::READ_SCOPE)
            ->removeScope(JsonWebTokenScope::READ_SCOPE)
            ->getPayload();

        static::assertArrayHasKey('custom', $payload);
        static::assertEquals('value', $payload['custom']);
    }
}
