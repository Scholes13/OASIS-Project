<?php

namespace Database\Seeders\WNS;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use Illuminate\Database\Seeder;

/**
 * Seed Sales & Marketing department + divisions for WNS.
 *
 * Context: PRD docs/specs/2026-05-25-wns-restructure-prd/02-target-structure.md.
 *
 * Structure:
 *   WNS / SM (root)
 *   ├── BS  (sub, parent: SM) — Business Solutions
 *   ├── COM (sub, parent: SM)
 *   └── CMC (sub, parent: SM)
 *
 * Idempotent via updateOrCreate on (business_unit_id, code).
 * Sub-departments deliberately skip Department::ensureDefaultPositions()
 * (handled in WNSSalesMarketingPositionSeeder with custom roles).
 */
class WNSSalesMarketingSeeder extends Seeder
{
    public function run(): void
    {
        $wns = BusinessUnit::where('code', 'WNS')->first();

        if (! $wns) {
            $this->command->error('Business Unit WNS not found. Run BusinessUnitSeeder first.');

            return;
        }

        $sm = Department::updateOrCreate(
            [
                'business_unit_id' => $wns->id,
                'code' => 'SM',
            ],
            [
                'parent_department_id' => null,
                'name' => 'Sales & Marketing',
                'is_active' => true,
            ]
        );

        $this->command->info("Root department seeded: WNS / {$sm->code} (id={$sm->id})");

        $divisions = [
            ['code' => 'BS', 'name' => 'Business Solutions'],
            ['code' => 'COM', 'name' => 'Commercial'],
            ['code' => 'CMC', 'name' => 'Corporate Marketing Communication'],
        ];

        foreach ($divisions as $division) {
            $sub = Department::updateOrCreate(
                [
                    'business_unit_id' => $wns->id,
                    'code' => $division['code'],
                ],
                [
                    'parent_department_id' => $sm->id,
                    'name' => $division['name'],
                    'is_active' => true,
                ]
            );

            $this->command->info("Sub-department seeded: WNS / {$sm->code} / {$sub->code} (id={$sub->id})");
        }

        $this->command->info('WNS Sales & Marketing tree seeded successfully.');
    }
}
