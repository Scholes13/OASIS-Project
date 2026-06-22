<?php

namespace App\Services\Core\Numbering;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\NumberingModule;

/**
 * Render a final formatted number string from the module's format pattern.
 *
 * Centralises the variable substitution and sequence padding rules so the
 * sequence generator only has to worry about producing the next number.
 */
class NumberingFormatter
{
    /**
     * Format the next number per the module's pattern.
     *
     * Supports null department for cross-department numbering (renders as
     * "SHARED" so the produced string still has every segment).
     */
    public function format(
        NumberingModule $module,
        BusinessUnit $businessUnit,
        ?Department $department,
        int $year,
        int $month,
        int $number
    ): string {
        $padding = $module->getConfigValue('sequence_padding', 3);

        $variables = [
            'MODULE' => $module->module_code,
            'BU' => $businessUnit->code,
            'DEPT' => $department?->code ?? 'SHARED',
            'YEAR' => $year,
            'MONTH' => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
            'SEQUENCE' => str_pad((string) $number, $padding, '0', STR_PAD_LEFT),
        ];

        return $module->parseFormatPattern($variables);
    }

    /**
     * Extract the sequence number from a previously formatted string.
     *
     * Walks the format pattern segments to locate the {SEQUENCE} slot;
     * falls back to the first numeric segment for backwards compatibility
     * with older patterns.
     */
    public function extractNumberFromFormatted(string $formattedNumber, NumberingModule $module): ?int
    {
        $pattern = $module->format_pattern;

        $patternParts = explode('/', $pattern);
        $numberParts = explode('/', $formattedNumber);

        foreach ($patternParts as $index => $part) {
            $hasSequenceToken = stripos($part, '{SEQUENCE}') !== false
                || stripos($part, 'SEQUENCE') !== false;

            if ($hasSequenceToken && isset($numberParts[$index])) {
                return (int) ltrim($numberParts[$index], '0') ?: 0;
            }
        }

        // Fallback: try to find any numeric part (backwards compatibility).
        foreach ($numberParts as $part) {
            if (is_numeric($part)) {
                return (int) ltrim($part, '0') ?: 0;
            }
        }

        return null;
    }

    /**
     * Validate that a formatted number has the expected number of segments
     * for the module pattern. This is a structural check only; the
     * individual segment values are not validated.
     */
    public function validateNumberFormat(string $formattedNumber, NumberingModule $module): bool
    {
        $pattern = $module->format_pattern;

        $requiredParts = substr_count($pattern, '/') + 1;
        $actualParts = substr_count($formattedNumber, '/') + 1;

        return $requiredParts === $actualParts;
    }
}
