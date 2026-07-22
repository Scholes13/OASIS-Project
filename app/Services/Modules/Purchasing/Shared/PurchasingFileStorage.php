<?php

namespace App\Services\Modules\Purchasing\Shared;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PurchasingFileStorage
{
    public function store(UploadedFile $file, string $directory): string
    {
        $path = $file->store($directory, 'public');
        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Failed to store purchasing file.');
        }

        return $path;
    }

    /** @param array<int, string|null>|string|null $paths */
    public function deleteSafely(array|string|null $paths, array $context = []): void
    {
        $paths = array_values(array_unique(array_filter((array) $paths)));
        if ($paths === []) {
            return;
        }

        try {
            if (! Storage::disk('public')->delete($paths)) {
                throw new \RuntimeException('Storage adapter did not delete all files.');
            }
        } catch (\Throwable $exception) {
            Log::error('Failed to delete purchasing files', [
                ...$context,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
