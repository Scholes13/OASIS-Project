<?php

namespace App\Services\Modules\Activity\Export;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SpreadsheetText
{
    public static function sanitize(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return preg_match('/^[=+\-@]/', ltrim($value)) === 1 ? "'".$value : $value;
    }

    public static function set(Worksheet $sheet, string $cell, mixed $value): void
    {
        if (is_string($value)) {
            $sheet->setCellValueExplicit($cell, self::sanitize($value), DataType::TYPE_STRING);

            return;
        }

        $sheet->setCellValue($cell, $value);
    }

    /** @param array<int, mixed> $values */
    public static function sanitizeRow(array $values): array
    {
        return array_map(self::sanitize(...), $values);
    }
}
