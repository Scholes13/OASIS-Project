<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const RESOLUTION_HOURS = 48;

    /**
     * Apply the current 2 x 24 hour SLA policy to every priority and BU.
     */
    public function up(): void
    {
        if (! Schema::hasTable('business_units') || ! Schema::hasTable('ticket_sla_settings')) {
            return;
        }

        $now = now();
        $rows = [];

        foreach (DB::table('business_units')->pluck('id') as $businessUnitId) {
            foreach (['low', 'medium', 'high', 'critical'] as $priority) {
                $rows[] = [
                    'business_unit_id' => $businessUnitId,
                    'priority' => $priority,
                    'resolution_hours' => self::RESOLUTION_HOURS,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('ticket_sla_settings')->upsert(
                $rows,
                ['business_unit_id', 'priority'],
                ['resolution_hours', 'updated_at'],
            );
        }
    }

    /**
     * The previous per-BU values cannot be reconstructed safely.
     */
    public function down(): void
    {
        // Intentionally irreversible data-policy migration.
    }
};
