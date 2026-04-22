<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;

class ViteHotFileGuard
{
    public function __construct(
        private readonly Filesystem $files
    ) {}

    public function cleanup(string $environment, string $hotFilePath): bool
    {
        if (! $this->shouldDelete($environment, $hotFilePath)) {
            return false;
        }

        return (bool) $this->files->delete($hotFilePath);
    }

    public function shouldDelete(string $environment, string $hotFilePath): bool
    {
        return ! in_array($environment, ['local', 'testing'], true)
            && $this->files->exists($hotFilePath);
    }
}
