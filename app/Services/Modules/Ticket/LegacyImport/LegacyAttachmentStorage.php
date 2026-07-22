<?php

namespace App\Services\Modules\Ticket\LegacyImport;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class LegacyAttachmentStorage
{
    /** @var list<string> */
    private array $newPaths = [];

    public function validateRoot(?string $root): string
    {
        if ($root === null || trim($root) === '') {
            throw new RuntimeException('Provide --legacy-storage when copying attachments.');
        }

        $resolved = realpath($root);
        if ($resolved === false || ! is_dir($resolved)) {
            throw new RuntimeException('The legacy attachment storage root does not exist.');
        }

        return rtrim($resolved, DIRECTORY_SEPARATOR);
    }

    public function resolveSource(string $root, ?string $relativePath): string
    {
        $relativePath = str_replace('\\', '/', trim((string) $relativePath));
        if ($relativePath === '' || str_contains($relativePath, "\0")) {
            throw new LegacyAttachmentPathException('Attachment path is empty or invalid.', false);
        }

        if (Str::startsWith($relativePath, ['/'])
            || preg_match('/^[A-Za-z]:\//', $relativePath) === 1
            || in_array('..', explode('/', $relativePath), true)) {
            throw new LegacyAttachmentPathException("Unsafe attachment path [{$relativePath}].", false);
        }

        $source = realpath($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        if ($source === false || ! is_file($source)) {
            throw new LegacyAttachmentPathException("Attachment file is missing [{$relativePath}].", true);
        }

        $rootPrefix = $root.DIRECTORY_SEPARATOR;
        if ($source !== $root && ! str_starts_with($source, $rootPrefix)) {
            throw new LegacyAttachmentPathException("Attachment escapes the legacy storage root [{$relativePath}].", false);
        }

        return $source;
    }

    public function store(
        string $sourcePath,
        int $ticketId,
        string $kind,
        string $importId,
        string $originalFilename,
    ): string {
        $disk = $this->disk();
        $safeName = Str::limit(Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)), 70, '');
        $safeName = $safeName !== '' ? $safeName : 'attachment';
        $extension = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', pathinfo($originalFilename, PATHINFO_EXTENSION)) ?? '');
        $hash = substr((string) hash_file('sha256', $sourcePath), 0, 16);
        $identity = preg_replace('/[^a-zA-Z0-9_-]/', '-', $importId) ?: 'unknown';
        $filename = "legacy-{$kind}-{$identity}-{$hash}-{$safeName}".($extension !== '' ? ".{$extension}" : '');
        $targetPath = "ticket-attachments/{$ticketId}/{$filename}";

        if ($disk->exists($targetPath)) {
            return $targetPath;
        }

        $stream = fopen($sourcePath, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Unable to open a legacy attachment for copying.');
        }

        try {
            if (! $disk->put($targetPath, $stream)) {
                throw new RuntimeException('Unable to write an attachment to the local disk.');
            }
        } finally {
            fclose($stream);
        }

        $this->newPaths[] = $targetPath;

        return $targetPath;
    }

    public function cleanupNewFiles(): void
    {
        if ($this->newPaths !== []) {
            $this->disk()->delete($this->newPaths);
        }

        $this->newPaths = [];
    }

    public function clearTracking(): void
    {
        $this->newPaths = [];
    }

    public function existsOnLocalDisk(?string $disk, ?string $path): bool
    {
        return $disk === 'local' && $path !== null && $path !== '' && $this->disk()->exists($path);
    }

    private function disk(): FilesystemAdapter
    {
        return Storage::disk('local');
    }
}
