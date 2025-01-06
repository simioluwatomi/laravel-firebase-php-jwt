<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\JWTCodec;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class JWTCodecTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('jwt.secret_key', 'random_secret_key');

        Config::set('jwt.algorithm', 'HS256');

        $this->jwtCodec = new JWTCodec();
    }

    public function test_encode_returns_valid_jwt()
    {
        $payload = ['name' => 'Arya Stark', 'email' => 'stark_arya@gameofthrones.com'];

        $jwt = $this->jwtCodec->encode($payload);

        static::assertNotEmpty($jwt);

        $decoded = (array) JWT::decode($jwt, new Key('random_secret_key', 'HS256'));

        static::assertEquals($payload, $decoded);
    }

    public function test_decode_returns_payload_for_valid_jwt()
    {
        $payload = ['name' => 'John Snow', 'email' => 'nights_watch@gameofthrones.com'];

        $jwt = $this->jwtCodec->encode($payload);

        $decodedPayload = $this->jwtCodec->decode($jwt);

        static::assertEquals($payload, $decodedPayload);
    }

    public function test_decode_logs_an_error_and_returns_null_for_invalid_jwt(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('JWT decoding failed', \Mockery::on(function ($context) {
                return isset($context['message']) && isset($context['trace']);
            }));

        static::assertNull($this->jwtCodec->decode('joffery.baratheon.token'));
    }
}
