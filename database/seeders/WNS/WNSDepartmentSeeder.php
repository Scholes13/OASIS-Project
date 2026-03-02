<?php

namespace Database\Seeders\WNS;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk departemen Werkudara Nirwana Sakti (WNS)
 *
 * Struktur awal 11 departemen WNS
 */
class WNSDepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wns = BusinessUnit::where('code', 'WNS')->first();

        if (! $wns) {
            $this->command->error('Business Unit WNS tidak ditemukan!');

            return;
        }

        $departments = [
            ['code' => 'ACC', 'name' => 'Accounting'],
            ['code' => 'ACS', 'name' => 'Art & Creative Support'],
            ['code' => 'BAS', 'name' => 'Business & Administrative Services'],
            ['code' => 'BID', 'name' => 'Business Innovation Development'],
            ['code' => 'CFC', 'name' => 'Corporate Finance Controller'],
            ['code' => 'GA', 'name' => 'General Affair'],
            ['code' => 'HR', 'name' => 'Human Resource'],
            ['code' => 'PD', 'name' => 'Product Development'],
            ['code' => 'SO', 'name' => 'Sales Operation'],
            ['code' => 'SS', 'name' => 'Strategic Sourcing'],
            ['code' => 'TEP', 'name' => 'Tour & Event Planning'],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                [
                    'business_unit_id' => $wns->id,
                    'code' => $dept['code'],
                ],
                [
                    'name' => $dept['name'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info("WNS: {$wns->name} - ".count($departments).' departemen berhasil di-seed.');
    }
}
