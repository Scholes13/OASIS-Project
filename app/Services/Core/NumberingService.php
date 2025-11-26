<?php

namespace App\Services\Core;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\NumberingModule;
use App\Models\Core\NumberSequence;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NumberingService
{
    protected $cachePrefix = 'numbering_';

    protected $lockTimeout = 30; // seconds

    /**
     * Generate next number for a module
     * Supports cross-department numbering when department_id is null
     */
    public function generateNumber(
        int $businessUnitId,
        string $moduleCode,
        ?int $departmentId,
        ?int $year = null,
        ?int $month = null
    ): array {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;

        // Get numbering module
        $module = NumberingModule::active()
            ->forBusinessUnit($businessUnitId)
            ->byCode($moduleCode)
            ->first();

        if (! $module) {
            throw new \Exception("Numbering module '{$moduleCode}' not found for business unit");
        }

        // For cross-department numbering (WNS), use null department_id
        $sequenceDepartmentId = $departmentId;

        // For cross-department numbering, don't use month-based sequences
        $sequenceMonth = ($departmentId === null) ? null : $month;

        // Generate with concurrent access protection
        return $this->generateWithLock($module, $sequenceDepartmentId, $year, $sequenceMonth);
    }

    /**
     * Generate number with distributed lock for concurrency
     * Supports yearly sequences when month is null
     */
    protected function generateWithLock(
        NumberingModule $module,
        ?int $departmentId,
        int $year,
        ?int $month
    ): array {
        $lockKey = $this->getLockKey($module->id, $departmentId, $year, $month);

        // Try to acquire distributed lock
        $lockAcquired = $this->acquireLock($lockKey);

        if (! $lockAcquired) {
            throw new \Exception('Unable to acquire lock for number generation. Please try again.');
        }

        try {
            return $this->generateNumberWithSequence($module, $departmentId, $year, $month);
        } finally {
            $this->releaseLock($lockKey);
        }
    }

    /**
     * Generate number using database sequence
     * Supports cross-department numbering and yearly sequences
     */
    protected function generateNumberWithSequence(
        NumberingModule $module,
        ?int $departmentId,
        int $year,
        ?int $month
    ): array {
        return DB::transaction(function () use ($module, $departmentId, $year, $month) {
            // Get or create sequence
            $sequence = NumberSequence::getOrCreateSequence(
                $module->business_unit_id,
                $module->id,
                $departmentId,
                $year,
                $month ?? 1 // Use 1 as default month for yearly sequences
            );

            // Get next available number (considering voids for resequencing)
            $nextNumber = $sequence->getNextAvailableNumber();

            // Get department and business unit for formatting
            // For cross-department (departmentId = null), department will be null for formatting
            $department = ($departmentId === null) ? null : Department::find($departmentId);
            $businessUnit = $module->businessUnit;

            // Format the number
            $formattedNumber = $this->formatNumber(
                $module,
                $businessUnit,
                $department,
                $year,
                $month ?? now()->month, // Use current month for formatting even in yearly sequences
                $nextNumber
            );

            // Cache the result for quick lookups
            $this->cacheNumberResult($module->id, $departmentId, $year, $month ?? 1, $nextNumber, $formattedNumber);

            return [
                'formatted_number' => $formattedNumber,
                'sequence_number' => $nextNumber,
                'sequence_id' => $sequence->id,
                'department_code' => $department?->code,
                'business_unit_code' => $businessUnit->code,
                'year' => $year,
                'month' => $month ?? now()->month,
            ];
        });
    }

    /**
     * Format number according to module pattern
     * Supports null department for cross-department numbering
     */
    protected function formatNumber(
        NumberingModule $module,
        BusinessUnit $businessUnit,
        ?Department $department,
        int $year,
        int $month,
        int $number
    ): string {
        $variables = [
            'MODULE' => $module->module_code,
            'BU' => $businessUnit->code,
            'DEPT' => $department?->code ?? 'SHARED', // Use 'SHARED' for cross-department
            'YEAR' => $year,
            'MONTH' => str_pad($month, 2, '0', STR_PAD_LEFT),
            'SEQUENCE' => str_pad($number, 3, '0', STR_PAD_LEFT), // Default 3-digit padding
        ];

        // Get custom padding from module config
        $padding = $module->getConfigValue('sequence_padding', 3);
        $variables['SEQUENCE'] = str_pad($number, $padding, '0', STR_PAD_LEFT);

        return $module->parseFormatPattern($variables);
    }

    /**
     * Void a number and trigger resequencing
     */
    public function voidNumber(string $formattedNumber, int $sequenceId): bool
    {
        return DB::transaction(function () use ($formattedNumber, $sequenceId) {
            $sequence = NumberSequence::find($sequenceId);
            if (! $sequence) {
                return false;
            }

            // Extract number from formatted number
            $number = $this->extractNumberFromFormatted($formattedNumber, $sequence->numberingModule);

            if ($number) {
                $sequence->addVoidNumber($number);

                // Clear cache
                $this->clearSequenceCache($sequence);

                return true;
            }

            return false;
        });
    }

    /**
     * Extract sequence number from formatted number
     * Improved to handle different format patterns dynamically
     */
    protected function extractNumberFromFormatted(string $formattedNumber, NumberingModule $module): ?int
    {
        // Get the format pattern from module
        $pattern = $module->format_pattern;

        // Find where {SEQUENCE} is in the pattern
        $patternParts = explode('/', $pattern);
        $numberParts = explode('/', $formattedNumber);

        // Find the position of {SEQUENCE} in pattern
        foreach ($patternParts as $index => $part) {
            if (stripos($part, '{SEQUENCE}') !== false || stripos($part, 'SEQUENCE') !== false) {
                // Extract number from corresponding position
                if (isset($numberParts[$index])) {
                    // Remove leading zeros and convert to int
                    return (int) ltrim($numberParts[$index], '0') ?: 0;
                }
            }
        }

        // Fallback: try to find any numeric part (backwards compatibility)
        foreach ($numberParts as $part) {
            if (is_numeric($part)) {
                return (int) ltrim($part, '0') ?: 0;
            }
        }

        return null;
    }

    /**
     * Get distributed lock key
     * Supports null month for yearly sequences and null department for cross-department
     */
    protected function getLockKey(int $moduleId, ?int $departmentId, int $year, ?int $month): string
    {
        $monthKey = $month ?? 'yearly';
        $deptKey = $departmentId ?? 'shared';

        return $this->cachePrefix."lock:{$moduleId}:{$deptKey}:{$year}:{$monthKey}";
    }

    /**
     * Acquire distributed lock using Cache (fallback for Redis)
     * CRITICAL: Throws exception if lock cannot be acquired to prevent race conditions
     */
    protected function acquireLock(string $lockKey): bool
    {
        try {
            // Use cache-based locking as primary method
            $acquired = Cache::add($lockKey, time(), $this->lockTimeout);

            if (! $acquired) {
                throw new \RuntimeException('Lock is currently held by another process. Please try again.');
            }

            return true;

        } catch (\RuntimeException $e) {
            // Re-throw runtime exceptions (lock acquisition failures)
            throw $e;
        } catch (\Exception $e) {
            // CRITICAL: If cache system fails, DO NOT proceed
            // This prevents race conditions when cache is down
            throw new \RuntimeException(
                'Unable to acquire lock due to cache system failure. Please try again later.',
                0,
                $e
            );
        }
    }

    /**
     * Release distributed lock
     */
    protected function releaseLock(string $lockKey): void
    {
        try {
            Cache::forget($lockKey);
        } catch (\Exception $e) {
            // Ignore errors when releasing lock
        }
    }

    /**
     * Cache number generation result
     */
    protected function cacheNumberResult(
        int $moduleId,
        ?int $departmentId,
        int $year,
        ?int $month,
        int $number,
        string $formattedNumber
    ): void {
        $cacheKey = $this->getCacheKey($moduleId, $departmentId, $year, $month ?? 1, $number);

        $data = [
            'formatted_number' => $formattedNumber,
            'generated_at' => now()->toISOString(),
        ];

        Cache::put($cacheKey, $data, now()->addDays(30));
    }

    /**
     * Get cached number result
     */
    public function getCachedNumber(
        int $moduleId,
        ?int $departmentId,
        int $year,
        ?int $month,
        int $number
    ): ?array {
        $cacheKey = $this->getCacheKey($moduleId, $departmentId, $year, $month ?? 1, $number);

        return Cache::get($cacheKey);
    }

    /**
     * Get cache key for number result
     */
    protected function getCacheKey(?int $moduleId, ?int $departmentId, int $year, int $month, int $number): string
    {
        $deptKey = $departmentId ?? 'shared';

        return $this->cachePrefix."result:{$moduleId}:{$deptKey}:{$year}:{$month}:{$number}";
    }

    /**
     * Clear cache for a sequence
     */
    protected function clearSequenceCache(NumberSequence $sequence): void
    {
        // Since we can't do pattern-based cache clearing with basic Cache facade,
        // we'll let the cache expire naturally (30 days TTL)
        // This is acceptable for most use cases
    }

    /**
     * Get current sequence status
     * Supports cross-department numbering when department_id is null
     */
    public function getSequenceStatus(
        int $businessUnitId,
        string $moduleCode,
        ?int $departmentId,
        ?int $year = null,
        ?int $month = null
    ): ?array {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;

        $module = NumberingModule::active()
            ->forBusinessUnit($businessUnitId)
            ->byCode($moduleCode)
            ->first();

        if (! $module) {
            return null;
        }

        // For cross-department numbering, use department_id = null
        $sequenceDepartmentId = $departmentId;
        $sequenceMonth = ($departmentId === null) ? 1 : $month; // Use month 1 for yearly sequences

        $sequence = $module->getCurrentSequence($sequenceDepartmentId, $year, $sequenceMonth);

        if (! $sequence) {
            return [
                'current_number' => 0,
                'max_number' => 999,
                'available_numbers' => 999,
                'void_numbers' => [],
                'next_number' => 1,
            ];
        }

        $voidNumbers = $sequence->void_numbers ?? [];
        $availableFromVoids = count($voidNumbers);
        $availableSequential = $sequence->max_number - $sequence->current_number;

        return [
            'current_number' => $sequence->current_number,
            'max_number' => $sequence->max_number,
            'available_numbers' => $availableFromVoids + $availableSequential,
            'void_numbers' => $voidNumbers,
            'next_number' => ! empty($voidNumbers) ? min($voidNumbers) : $sequence->current_number + 1,
        ];
    }

    /**
     * Validate number format
     */
    public function validateNumberFormat(string $formattedNumber, NumberingModule $module): bool
    {
        // Extract components and validate against pattern
        $pattern = $module->format_pattern;

        // Simple validation - in production you might want regex-based validation
        $requiredParts = substr_count($pattern, '/') + 1;
        $actualParts = substr_count($formattedNumber, '/') + 1;

        return $requiredParts === $actualParts;
    }

    /**
     * Get numbering statistics
     */
    public function getNumberingStatistics(int $businessUnitId, ?string $moduleCode = null): array
    {
        $query = NumberSequence::where('business_unit_id', $businessUnitId);

        if ($moduleCode) {
            $module = NumberingModule::forBusinessUnit($businessUnitId)
                ->byCode($moduleCode)
                ->first();

            if ($module) {
                $query->where('numbering_module_id', $module->id);
            }
        }

        $sequences = $query->with(['numberingModule', 'department'])->get();

        $stats = [
            'total_sequences' => $sequences->count(),
            'total_numbers_issued' => $sequences->sum('current_number'),
            'total_void_numbers' => $sequences->sum(function ($seq) {
                return count($seq->void_numbers ?? []);
            }),
            'modules' => [],
        ];

        foreach ($sequences->groupBy('numbering_module_id') as $moduleId => $moduleSequences) {
            $module = $moduleSequences->first()->numberingModule;

            $stats['modules'][$module->module_code] = [
                'module_name' => $module->module_name,
                'sequences_count' => $moduleSequences->count(),
                'numbers_issued' => $moduleSequences->sum('current_number'),
                'void_numbers' => $moduleSequences->sum(function ($seq) {
                    return count($seq->void_numbers ?? []);
                }),
            ];
        }

        return $stats;
    }
}
