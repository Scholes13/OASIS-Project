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
     * Default ticket categories with colors.
     */
    private const CATEGORY_DEFAULTS = [
        ['name' => 'Account Access', 'description' => 'Masalah login, reset password, akses akun', 'color' => '#3b82f6'],
        ['name' => 'Email Issue', 'description' => 'Masalah email, konfigurasi, spam', 'color' => '#8b5cf6'],
        ['name' => 'Hardware Issue', 'description' => 'Kerusakan laptop, printer, monitor, perangkat keras lainnya', 'color' => '#ef4444'],
        ['name' => 'Network Issue', 'description' => 'Masalah WiFi, LAN, VPN, koneksi internet', 'color' => '#f59e0b'],
        ['name' => 'Software Issue', 'description' => 'Instalasi, update, error aplikasi, lisensi', 'color' => '#10b981'],
        ['name' => 'Service Request', 'description' => 'Permintaan layanan IT baru, setup perangkat, akses sistem', 'color' => '#06b6d4'],
        ['name' => 'Other', 'description' => 'Permintaan atau masalah IT lainnya', 'color' => '#6b7280'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedNumberingModule();
        $this->seedSlaSettings();
        $this->seedTicketCategories();
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
                } else {
                    // Fix existing rows if resolution_hours is wrong
                    DB::table('ticket_sla_settings')
                        ->where('business_unit_id', $businessUnit->id)
                        ->where('priority', $priority)
                        ->where('resolution_hours', '!=', $hours)
                        ->update(['resolution_hours' => $hours, 'updated_at' => $now]);
                }
            }
        }

        $this->command->info("SLA settings seeded: {$inserted} row(s) inserted for {$businessUnits->count()} business unit(s).");
    }

    /**
     * Seed default ticket categories for every business unit.
     */
    private function seedTicketCategories(): void
    {
        $businessUnits = BusinessUnit::all();
        $now = now();
        $inserted = 0;

        foreach ($businessUnits as $businessUnit) {
            foreach (self::CATEGORY_DEFAULTS as $category) {
                $exists = DB::table('ticket_categories')
                    ->where('business_unit_id', $businessUnit->id)
                    ->where('name', $category['name'])
                    ->exists();

                if (! $exists) {
                    DB::table('ticket_categories')->insert([
                        'business_unit_id' => $businessUnit->id,
                        'name' => $category['name'],
                        'description' => $category['description'],
                        'color' => $category['color'],
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $inserted++;
                }
            }
        }

        $this->command->info("Ticket categories seeded: {$inserted} row(s) inserted for {$businessUnits->count()} business unit(s).");
    }
}
