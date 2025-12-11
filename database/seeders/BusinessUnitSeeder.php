<?php

namespace Database\Seeders;

use App\Models\Core\BusinessUnit;
use Illuminate\Database\Seeder;

class BusinessUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $commonConfig = [
            'pr_format' => 'PR.{DEPT}/{YEAR}/{MONTH}/{SEQUENCE}',
            'sequence_padding' => 3,
            'max_sequence' => 999,
            'reset_annually' => false,
            'reset_monthly' => false,
        ];

        BusinessUnit::unguard();

        $werkudaraGroup = BusinessUnit::updateOrCreate(
            ['id' => 1],
            [
                'code' => 'WG',
                'name' => 'Werkudara Group',
                'description' => 'Parent company and top management for Werkudara Group',
                'numbering_config' => $commonConfig,
                'parent_id' => null,
                'is_active' => true,
            ]
        );

        BusinessUnit::reguard();

        $businessUnits = [
            [
                'code' => 'WNS',
                'name' => 'Werkudara Nirwana Sakti',
            ],
            [
                'code' => 'UT',
                'name' => 'Utama Kalpana',
            ],
            [
                'code' => 'MRP',
                'name' => 'Maharaja Pratama',
            ],
            [
                'code' => 'WNN',
                'name' => 'Werkudara Nirwana Nadi',
            ],
        ];

        foreach ($businessUnits as $unitData) {
            BusinessUnit::updateOrCreate(
                ['code' => $unitData['code']],
                array_merge($unitData, [
                    'numbering_config' => $commonConfig,
                    'parent_id' => $werkudaraGroup->id,
                    'is_active' => true,
                ])
            );
        }
    }
}
