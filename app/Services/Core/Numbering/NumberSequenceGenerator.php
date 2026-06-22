<?php

namespace App\Services\Core\Numbering;

use App\Models\Core\Department;
use App\Models\Core\NumberingModule;
use App\Models\Core\NumberSequence;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Allocate the next sequence number for a numbering module and return the
 * formatted output. Runs inside a database transaction so concurrent
 * generators see consistent counters.
 *
 * Caching is best-effort: cached entries help the UI surface recently
 * generated numbers, but the source of truth remains
 * {@see NumberSequence}.
 */
class NumberSequenceGenerator
{
    public function __construct(
        protected NumberingFormatter $formatter,
        protected string $cachePrefix = 'numbering_',
    ) {}

    /**
     * Generate the next number for the module.
     *
     * @return array{
     *     formatted_number: string,
     *     sequence_number: int,
     *     sequence_id: int,
     *     department_code: ?string,
     *     business_unit_code: string,
     *     year: int,
     *     month: int,
     * }
     */
    public function generate(
        NumberingModule $module,
        ?int $departmentId,
        int $year,
        ?int $month
    ): array {
        return DB::transaction(function () use ($module, $departmentId, $year, $month) {
            $sequence = NumberSequence::getOrCreateSequence(
                $module->business_unit_id,
                $module->id,
                $departmentId,
                $year,
                $month ?? 1 // Use 1 as default month for yearly sequences.
            );

            $nextNumber = $sequence->getNextAvailableNumber();

            // For cross-department (departmentId = null), department stays null.
            $department = ($departmentId === null) ? null : Department::find($departmentId);
            $businessUnit = $module->businessUnit;

            $formattedNumber = $this->formatter->format(
                $module,
                $businessUnit,
                $department,
                $year,
                $month ?? now()->month, // Use current month for formatting in yearly sequences.
                $nextNumber
            );

            $this->cacheNumberResult(
                $module->id,
                $departmentId,
                $year,
                $month ?? 1,
                $nextNumber,
                $formattedNumber
            );

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
     * Look up a cached generation result.
     *
     * @return array<string, mixed>|null
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
     * Cache the generation result for quick lookups.
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
     * Build the cache key for a generated number lookup.
     */
    protected function getCacheKey(?int $moduleId, ?int $departmentId, int $year, int $month, int $number): string
    {
        $deptKey = $departmentId ?? 'shared';

        return $this->cachePrefix."result:{$moduleId}:{$deptKey}:{$year}:{$month}:{$number}";
    }
}
