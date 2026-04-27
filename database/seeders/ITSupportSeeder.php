<?php

namespace Database\Seeders;

use App\Models\Core\BusinessUnit;
use App\Models\Core\NumberingModule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ITSupportSeeder extends Seeder
{
    /**
     * Default SLA resolution hours by priority.
     */
    private const SLA_DEFAULTS = [
        'low' => 48,
        'medium' => 24,
        'high' => 8,
        'critical' => 2,
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedNumberingModule();
        $this->seedSlaSettings();
    }

    /**
     * Register the IT numbering module for every business unit.
     */
    private function seedNumberingModule(): void
    {
        $businessUnits = BusinessUnit::all();

        foreach ($businessUnits as $businessUnit) {
            NumberingModule::firstOrCreate(
                [
                    'business_unit_id' => $businessUnit->id,
                    'module_code' => 'IT',
                ],
                [
                    'module_name' => 'IT Support Ticket',
                    'format_pattern' => 'IT.{DEPT}/{YEAR}/{MONTH}/{SEQUENCE}',
                    'config' => [
                        'sequence_padding' => 4,
                        'max_number' => 9999,
                        'reset_annually' => false,
                        'reset_monthly' => false,
                        'description' => 'IT Support Ticket numbering for helpdesk',
                    ],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('IT numbering module seeded for '.$businessUnits->count().' business unit(s).');
    }

    /**
     * Seed default SLA settings for every business unit.
     */
    private function seedSlaSettings(): void
    {
        $businessUnits = BusinessUnit::all();
        $now = now();
        $inserted = 0;

        foreach ($businessUnits as $businessUnit) {
            foreach (self::SLA_DEFAULTS as $priority => $hours) {
                $exists = DB::table('ticket_sla_settings')
                    ->where('business_unit_id', $businessUnit->id)
                    ->where('priority', $priority)
                    ->exists();

                if (! $exists) {
                    DB::table('ticket_sla_settings')->insert([
                        'business_unit_id' => $businessUnit->id,
                        'priority' => $priority,
                        'resolution_hours' => $hours,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $inserted++;
                }
            }
        }

        $this->command->info("SLA settings seeded: {$inserted} row(s) inserted for {$businessUnits->count()} business unit(s).");
    }
}
