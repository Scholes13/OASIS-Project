<?php

namespace App\Services\Modules\Ticket\LegacyImport;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;

final readonly class LegacyImportOptions
{
    public function __construct(
        public BusinessUnit $businessUnit,
        public User $fallbackUser,
        public Department $fallbackDepartment,
        public string $source,
        public ?string $legacyStorage,
        public bool $copyAttachments,
        public bool $updateExisting,
        public ?int $limit,
    ) {}
}
