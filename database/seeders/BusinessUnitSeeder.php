<?php

namespace Database\Seeders;

use App\Models\BusinessUnit;
use Illuminate\Database\Seeder;

class BusinessUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businessUnits = [
            [
                'code' => 'WNS',
                'name' => 'Werkudara Nirwana Sakti',
                'numbering_config' => [
                    'pr_format' => 'PR.{DEPT}/{YEAR}/{MONTH}/{SEQUENCE}',
                    'sequence_padding' => 3,
                    'max_sequence' => 999,
                    'reset_annually' => false,
                    'reset_monthly' => false,
                ],
                'is_active' => true,
            ],
            [
                'code' => 'UT',
                'name' => 'Utama Kalpana',
                'numbering_config' => [
                    'pr_format' => 'PR.{DEPT}/{YEAR}/{MONTH}/{SEQUENCE}',
                    'sequence_padding' => 3,
                    'max_sequence' => 999,
                    'reset_annually' => false,
                    'reset_monthly' => false,
                ],
                'is_active' => true,
            ],
            [
                'code' => 'MRP',
                'name' => 'Maharaja Pratama',
                'numbering_config' => [
                    'pr_format' => 'PR.{DEPT}/{YEAR}/{MONTH}/{SEQUENCE}',
                    'sequence_padding' => 3,
                    'max_sequence' => 999,
                    'reset_annually' => false,
                    'reset_monthly' => false,
                ],
                'is_active' => true,
            ],
            [
                'code' => 'WNN',
                'name' => 'Werkudara Nirwana Nadi',
                'numbering_config' => [
                    'pr_format' => 'PR.{DEPT}/{YEAR}/{MONTH}/{SEQUENCE}',
                    'sequence_padding' => 3,
                    'max_sequence' => 999,
                    'reset_annually' => false,
                    'reset_monthly' => false,
                ],
                'is_active' => true,
            ],
        ];

        foreach ($businessUnits as $unitData) {
            BusinessUnit::firstOrCreate(
                ['code' => $unitData['code']],
                $unitData
            );
        }
    }
}
