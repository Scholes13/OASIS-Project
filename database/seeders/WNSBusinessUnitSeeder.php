<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessUnit;
use App\Models\Department;

class WNSBusinessUnitSeeder extends Seeder
{
    public function run(): void
    {
        // Create WNS Business Unit
        $wnsBusinessUnit = BusinessUnit::firstOrCreate(
            ['code' => 'WNS'],
            [
                'name' => 'Work Number System',
                'numbering_config' => [
                    'pr_format' => 'PR.{DEPT}/{YEAR}/{MONTH}/{SEQUENCE}',
                    'cross_department' => true,
                    'yearly_reset' => true,
                ],
                'is_active' => true,
            ]
        );

        // Create IT Department under WNS
        Department::firstOrCreate(
            [
                'business_unit_id' => $wnsBusinessUnit->id,
                'code' => 'IT'
            ],
            [
                'name' => 'Information Technology',
                'is_active' => true,
            ]
        );

        // Create other departments
        $departments = [
            ['code' => 'HR', 'name' => 'Human Resources'],
            ['code' => 'FIN', 'name' => 'Finance'],
            ['code' => 'OPS', 'name' => 'Operations'],
            ['code' => 'MKT', 'name' => 'Marketing'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                [
                    'business_unit_id' => $wnsBusinessUnit->id,
                    'code' => $dept['code']
                ],
                [
                    'name' => $dept['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}