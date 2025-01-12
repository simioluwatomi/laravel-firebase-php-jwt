<?php

declare(strict_types=1);

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

if (! function_exists('get_encryption_keys_storage_disk')) {
    function get_encryption_keys_storage_disk(): Filesystem
    {
        return Storage::build(['driver' => 'local', 'root' => storage_path('keys')]);
    }
}
