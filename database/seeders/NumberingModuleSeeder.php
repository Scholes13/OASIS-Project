<?php

namespace Database\Seeders;

use App\Models\BusinessUnit;
use App\Models\NumberingModule;
use Illuminate\Database\Seeder;

class NumberingModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            [
                'module_code' => 'PR',
                'module_name' => 'Purchase Request',
                'format_pattern' => 'PR.{DEPT}/{YEAR}/{MONTH}/{SEQUENCE}',
                'config' => [
                    'sequence_padding' => 3,
                    'max_number' => 999,
                    'reset_annually' => false,
                    'reset_monthly' => false,
                    'description' => 'Purchase Request numbering for all departments',
                ],
            ],
            [
                'module_code' => 'RT',
                'module_name' => 'Receipt',
                'format_pattern' => 'RT.{DEPT}/{YEAR}/{MONTH}/{SEQUENCE}',
                'config' => [
                    'sequence_padding' => 3,
                    'max_number' => 999,
                    'reset_annually' => false,
                    'reset_monthly' => false,
                    'description' => 'Receipt numbering for all departments',
                ],
            ],
            [
                'module_code' => 'INV',
                'module_name' => 'Invoice',
                'format_pattern' => 'INV.{DEPT}/{YEAR}/{MONTH}/{SEQUENCE}',
                'config' => [
                    'sequence_padding' => 4,
                    'max_number' => 9999,
                    'reset_annually' => true,
                    'reset_monthly' => false,
                    'description' => 'Invoice numbering for finance department',
                ],
            ],
            [
                'module_code' => 'PO',
                'module_name' => 'Purchase Order',
                'format_pattern' => 'PO.{DEPT}/{YEAR}/{MONTH}/{SEQUENCE}',
                'config' => [
                    'sequence_padding' => 3,
                    'max_number' => 999,
                    'reset_annually' => false,
                    'reset_monthly' => false,
                    'description' => 'Purchase Order numbering for procurement',
                ],
            ],
            [
                'module_code' => 'WO',
                'module_name' => 'Work Order',
                'format_pattern' => 'WO.{DEPT}/{YEAR}/{MONTH}/{SEQUENCE}',
                'config' => [
                    'sequence_padding' => 3,
                    'max_number' => 999,
                    'reset_annually' => false,
                    'reset_monthly' => false,
                    'description' => 'Work Order numbering for operations',
                ],
            ],
        ];

        // Create modules for each business unit
        $businessUnits = BusinessUnit::all();
        
        foreach ($businessUnits as $businessUnit) {
            foreach ($modules as $moduleData) {
                NumberingModule::firstOrCreate(
                    [
                        'business_unit_id' => $businessUnit->id,
                        'module_code' => $moduleData['module_code'],
                    ],
                    [
                        'module_name' => $moduleData['module_name'],
                        'format_pattern' => $moduleData['format_pattern'],
                        'config' => $moduleData['config'],
                        'is_active' => true,
                    ]
                );
            }
        }

        // Create specific modules for WNS (our focus business unit)
        $wns = BusinessUnit::where('code', 'WNS')->first();
        
        if ($wns) {
            // Additional WNS-specific modules
            $wnsSpecificModules = [
                [
                    'module_code' => 'QT',
                    'module_name' => 'Quotation',
                    'format_pattern' => 'QT.{DEPT}/{YEAR}/{MONTH}/{SEQUENCE}',
                    'config' => [
                        'sequence_padding' => 3,
                        'max_number' => 999,
                        'reset_annually' => false,
                        'reset_monthly' => false,
                        'description' => 'Quotation numbering for sales',
                    ],
                ],
                [
                    'module_code' => 'CT',
                    'module_name' => 'Contract',
                    'format_pattern' => 'CT.{DEPT}/{YEAR}/{MONTH}/{SEQUENCE}',
                    'config' => [
                        'sequence_padding' => 3,
                        'max_number' => 999,
                        'reset_annually' => true,
                        'reset_monthly' => false,
                        'description' => 'Contract numbering for legal/business',
                    ],
                ],
            ];

            foreach ($wnsSpecificModules as $moduleData) {
                NumberingModule::firstOrCreate(
                    [
                        'business_unit_id' => $wns->id,
                        'module_code' => $moduleData['module_code'],
                    ],
                    [
                        'module_name' => $moduleData['module_name'],
                        'format_pattern' => $moduleData['format_pattern'],
                        'config' => $moduleData['config'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}