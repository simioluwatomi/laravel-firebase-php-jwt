<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\JWTCodec;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Crypt\RSA;
use Tests\TestCase;

class JWTCodecTest extends TestCase
{
    private string $privateKeyFilename;
    private string $publicKeyFilename;
    private string $privateKeyContent;
    private string $publicKeyContent;
    private JWTCodec $jwtCodec;
    private Filesystem $localDisk;

    protected function setUp(): void
    {
        parent::setUp();

        $key = RSA::createKey();

        $this->privateKeyContent = (string) $key;
        $this->publicKeyContent = (string) $key->getPublicKey();

        $this->privateKeyFilename = 'test_private.key';
        $this->publicKeyFilename = 'test_public.key';

        Config::set('jwt.encryption_keys.private_key_filename', $this->privateKeyFilename);
        Config::set('jwt.encryption_keys.public_key_filename', $this->publicKeyFilename);

        $this->jwtCodec = new JWTCodec();

        $this->localDisk = Storage::fake('local');
        Storage::shouldReceive('build')
            ->with(['driver' => 'local', 'root' => storage_path('keys')])
            ->andReturn($this->localDisk);
    }

    protected function tearDown(): void
    {
        foreach ($this->localDisk->allFiles() as $file) {
            $this->localDisk->delete($file);
        }

        parent::tearDown();
    }

    public function test_encode_throws_exception_when_private_key_not_found()
    {
        static::expectException(FileNotFoundException::class);

        $this->jwtCodec->encode(['name' => 'Arya Stark', 'email' => 'stark_arya@gameofthrones.com']);
    }

    public function test_encode_logs_an_error_and_returns_null_when_encoding_fails(): void
    {
        $this->localDisk->put($this->privateKeyFilename, 'invalid-key-content');

        Log::shouldReceive('error')
            ->once()
            ->with('JWT encoding failed', \Mockery::on(function ($context) {
                return isset($context['message']) && isset($context['trace']);
            }));

        static::assertNull($this->jwtCodec->encode(['name' => 'Arya Stark', 'email' => 'stark_arya@gameofthrones.com']));
    }

    public function test_encode_returns_valid_jwt()
    {
        $this->localDisk->put($this->privateKeyFilename, $this->privateKeyContent);
        $payload = ['name' => 'Arya Stark', 'email' => 'stark_arya@gameofthrones.com'];

        $jwt = $this->jwtCodec->encode($payload);
        $decoded = (array) JWT::decode($jwt, new Key($this->publicKeyContent, 'RS256'));

        static::assertIsString($jwt);
        static::assertNotEmpty($jwt);
        static::assertEquals($payload, $decoded);
    }

    public function test_decode_returns_payload_for_valid_jwt()
    {
        $this->localDisk->put($this->publicKeyFilename, $this->publicKeyContent);

        $payload = ['name' => 'John Snow', 'email' => 'nights_watch@gameofthrones.com'];
        $jwt = JWT::encode($payload, $this->privateKeyContent, 'RS256');

        $decodedPayload = $this->jwtCodec->decode($jwt);

        static::assertEquals($payload, $decodedPayload);
    }

    public function test_decode_throws_exception_when_public_key_not_found()
    {
        static::expectException(FileNotFoundException::class);

        $this->jwtCodec->decode('battle.of.the.bastards');
    }

    public function test_decode_logs_an_error_and_returns_null_when_decoding_fails(): void
    {
        $this->localDisk->put($this->publicKeyFilename, 'invalid-key-content');

        Log::shouldReceive('error')
            ->once()
            ->with('JWT decoding failed', \Mockery::on(function ($context) {
                return isset($context['message']) && isset($context['trace']);
            }));

        static::assertNull($this->jwtCodec->decode('kings.landing.bounty'));
    }
}
