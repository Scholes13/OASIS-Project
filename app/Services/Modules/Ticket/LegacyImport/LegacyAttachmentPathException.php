<?php

namespace App\Services\Modules\Ticket\LegacyImport;

use RuntimeException;

final class LegacyAttachmentPathException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $missing)
    {
        parent::__construct($message);
    }
}
