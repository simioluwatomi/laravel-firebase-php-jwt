<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Crypt\RSA;

class GenerateEncryptionKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:generate-encryption-keys
                                                        {--force : Overwrite keys if they already exist}
                                                        {--length=4096 : The length of the private key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the encryption keys for API authentication';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $validKeyLengths = [
            2_048,
            3_072,
            4_096,
        ];

        $length = (int) $this->option('length');

        if (! in_array($length, $validKeyLengths, true)) {
            $message = sprintf('Invalid key length provided. Valid values are: %s', implode(',', $validKeyLengths));

            $this->error($message);

            return Command::FAILURE;
        }

        $publicKey = config('jwt.encryption_keys.public_key_filename');

        $privateKey = config('jwt.encryption_keys.private_key_filename');

        $disk = Storage::build(['driver' => 'local', 'root' => storage_path('keys')]);

        if (($disk->exists($publicKey) || $disk->exists($privateKey)) && ! $this->option('force')) {
            $this->error('Encryption keys already exist. Use the --force option to overwrite them.');

            return Command::FAILURE;
        }

        $key = RSA::createKey($length);

        $disk->put($publicKey, (string) $key->getPublicKey());

        $disk->put($privateKey, (string) $key);

        $this->info('Encryption keys generated successfully.');

        return Command::SUCCESS;
    }
}
