<?php

namespace App\Services\Core;

use App\Models\Core\NumberingModule;
use App\Models\Core\NumberSequence;
use App\Services\Core\Numbering\DistributedLockManager;
use App\Services\Core\Numbering\NumberingFormatter;
use App\Services\Core\Numbering\NumberSequenceGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Facade for the numbering subsystem.
 *
 * The actual work — distributed locking, sequence allocation, and
 * formatting — is delegated to dedicated collaborators under the
 * {@see \App\Services\Core\Numbering} namespace. Public API is preserved
 * so all existing callers (PR, stock request, ticket, etc.) keep working
 * unchanged.
 */
class NumberingService
{
    public function __construct(
        protected DistributedLockManager $lockManager = new DistributedLockManager,
        protected NumberSequenceGenerator $sequenceGenerator = new NumberSequenceGenerator(new NumberingFormatter),
        protected NumberingFormatter $formatter = new NumberingFormatter,
    ) {}

    /**
     * Generate next number for a module.
     *
     * Supports cross-department numbering when $departmentId is null.
     *
     * @return array<string, mixed>
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

        $module = NumberingModule::active()
            ->forBusinessUnit($businessUnitId)
            ->byCode($moduleCode)
            ->first();

        if (! $module) {
            throw new \Exception("Numbering module '{$moduleCode}' not found for business unit");
        }

        // For cross-department numbering, don't use month-based sequences.
        $sequenceMonth = ($departmentId === null) ? null : $month;

        $lockKey = $this->lockManager->getLockKey($module->id, $departmentId, $year, $sequenceMonth);

        return $this->lockManager->withLock(
            $lockKey,
            fn (): array => $this->sequenceGenerator->generate($module, $departmentId, $year, $sequenceMonth)
        );
    }

    /**
     * Void a number and trigger resequencing.
     */
    public function voidNumber(string $formattedNumber, int $sequenceId): bool
    {
        return DB::transaction(function () use ($formattedNumber, $sequenceId) {
            $sequence = NumberSequence::find($sequenceId);
            if (! $sequence) {
                return false;
            }

            $number = $this->formatter->extractNumberFromFormatted(
                $formattedNumber,
                $sequence->numberingModule
            );

            if (! $number) {
                return false;
            }

            $sequence->addVoidNumber($number);

            return true;
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
        return $this->sequenceGenerator->getCachedNumber(
            $moduleId,
            $departmentId,
            $year,
            $month,
            $number
        );
    }

    /**
     * Get current sequence status for a (BU, module, department) tuple.
     *
     * Supports cross-department numbering when $departmentId is null.
     *
     * @return array<string, mixed>|null
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

        // Cross-department numbering uses month=1 as the yearly sequence anchor.
        $sequenceMonth = ($departmentId === null) ? 1 : $month;

        $sequence = $module->getCurrentSequence($departmentId, $year, $sequenceMonth);

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
     * Validate number format.
     */
    public function validateNumberFormat(string $formattedNumber, NumberingModule $module): bool
    {
        return $this->formatter->validateNumberFormat($formattedNumber, $module);
    }

    /**
     * Get numbering statistics for a business unit (optionally a single
     * module).
     *
     * @return array<string, mixed>
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
            'total_void_numbers' => $sequences->sum(fn ($seq) => count($seq->void_numbers ?? [])),
            'modules' => [],
        ];

        foreach ($sequences->groupBy('numbering_module_id') as $moduleId => $moduleSequences) {
            $module = $moduleSequences->first()->numberingModule;

            $stats['modules'][$module->module_code] = [
                'module_name' => $module->module_name,
                'sequences_count' => $moduleSequences->count(),
                'numbers_issued' => $moduleSequences->sum('current_number'),
                'void_numbers' => $moduleSequences->sum(fn ($seq) => count($seq->void_numbers ?? [])),
            ];
        }

        return $stats;
    }
}
