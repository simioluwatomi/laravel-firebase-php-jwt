<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Console\Commands\GenerateEncryptionKeys;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Crypt\PublicKeyLoader;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Command\Command;
use Tests\TestCase;

class GenerateEncryptionKeysTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->publicKey = 'test_public.key';

        $this->privateKey = 'test_private.key';

        Config::set('jwt.encryption_keys.private_key_filename', $this->privateKey);

        Config::set('jwt.encryption_keys.public_key_filename', $this->publicKey);

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

    public function test_it_prevents_overwriting_without_force_option()
    {
        static::assertFalse($this->localDisk->exists($this->publicKey));
        static::assertFalse($this->localDisk->exists($this->privateKey));

        $this->artisan(GenerateEncryptionKeys::class, ['--force' => true, '--length' => 3_072])
            ->assertExitCode(Command::SUCCESS);

        $this->assertTrue($this->localDisk->exists($this->publicKey));

        $firstKey = PublicKeyLoader::loadPublicKey($this->localDisk->get($this->publicKey));
        static::assertEquals(3_072, $firstKey->getLength());

        $this->artisan(GenerateEncryptionKeys::class, ['--length' => 4_096])
            ->assertExitCode(Command::FAILURE);

        $secondKey = PublicKeyLoader::loadPublicKey($this->localDisk->get($this->publicKey));
        static::assertNotEquals(4_096, $secondKey->getLength());
    }

    public function test_it_fails_with_invalid_key_length()
    {
        static::assertFalse($this->localDisk->exists($this->publicKey));
        static::assertFalse($this->localDisk->exists($this->privateKey));

        $this->artisan(GenerateEncryptionKeys::class, ['--length' => 1_024])
            ->assertExitCode(Command::FAILURE);

        static::assertFalse($this->localDisk->exists($this->publicKey));
        static::assertFalse($this->localDisk->exists($this->privateKey));
    }

    #[DataProvider('validKeyLengthProvider')]
    public function test_it_generates_keys_with_valid_lengths(int $length): void
    {
        static::assertFalse($this->localDisk->exists($this->publicKey));
        static::assertFalse($this->localDisk->exists($this->privateKey));

        $this->artisan(GenerateEncryptionKeys::class, ['--force' => true, '--length' => $length])
            ->assertExitCode(Command::SUCCESS);

        static::assertTrue($this->localDisk->exists($this->publicKey));
        static::assertTrue($this->localDisk->exists($this->privateKey));

        $key = PublicKeyLoader::loadPublicKey($this->localDisk->get($this->publicKey));
        $this->assertEquals($length, $key->getLength());
    }

    public static function validKeyLengthProvider(): array
    {
        return [
            [2_048],
            [3_072],
            [4_096],
        ];
    }
}
