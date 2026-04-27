<?php

namespace App\Services\Modules\Ticket;

use App\Models\Core\BusinessUnit;
use App\Models\Core\NumberingModule;
use App\Services\Core\NumberingService;

class TicketNumberService
{
    protected string $moduleCode = 'IT';

    protected string $formatPattern = 'IT.{BU_CODE}/{YYYYMM}/{SEQUENCE}';

    public function __construct(
        protected NumberingService $numberingService
    ) {}

    /**
     * Generate a ticket number for the given business unit.
     *
     * Format: IT.{BU_CODE}/{YYYYMM}/{zero-padded sequence}
     * Sequence resets monthly per BU.
     */
    public function generateTicketNumber(int $buId): string
    {
        $businessUnit = BusinessUnit::findOrFail($buId);

        // Ensure the IT numbering module exists for this BU
        $this->ensureModule($businessUnit);

        $now = now();

        // Generate with monthly reset, no department separation
        $result = $this->numberingService->generateNumber(
            $businessUnit->id,
            $this->moduleCode,
            null, // No department separation — shared across BU
            $now->year,
            $now->month
        );

        // Override formatted number with our specific format
        return sprintf(
            'IT.%s/%d%02d/%03d',
            $businessUnit->code,
            $now->year,
            $now->month,
            $result['sequence_number']
        );
    }

    /**
     * Ensure the IT numbering module exists for the given business unit.
     */
    protected function ensureModule(BusinessUnit $businessUnit): NumberingModule
    {
        return NumberingModule::firstOrCreate(
            [
                'business_unit_id' => $businessUnit->id,
                'module_code' => $this->moduleCode,
            ],
            [
                'module_name' => 'IT Support Ticket',
                'format_pattern' => $this->formatPattern,
                'config' => [
                    'sequence_padding' => 3,
                    'max_number' => 999,
                    'reset_annually' => false,
                    'reset_monthly' => true,
                    'cross_department' => true,
                    'shared_sequence' => true,
                ],
                'is_active' => true,
            ]
        );
    }
}
