<?php

namespace Database\Seeders\WNS;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use Illuminate\Database\Seeder;

/**
 * Seed custom positions for WNS Sales & Marketing tree + Sales Operations coordinator.
 *
 * Context: PRD docs/specs/2026-05-25-wns-restructure-prd/03-position-hierarchy.md.
 *
 * Note: root department SM still gets the default 4 positions (EXEC/HOD/LEAD/STAFF)
 * from Department::ensureDefaultPositions() on creation. This seeder ADDS custom
 * positions on top (GM, Asisten GM, etc.) without touching the default set.
 *
 * Sub-departments (BS/COM/CMC) skip default positions (see Department::booted()),
 * so this seeder is the only source of positions for them.
 *
 * Idempotent via firstOrCreate on (department_id, code).
 */
class WNSSalesMarketingPositionSeeder extends Seeder
{
    public function run(): void
    {
        $wns = BusinessUnit::where('code', 'WNS')->first();

        if (! $wns) {
            $this->command->error('Business Unit WNS not found.');

            return;
        }

        foreach ($this->positionConfig() as $deptCode => $positions) {
            $dept = Department::where('business_unit_id', $wns->id)
                ->where('code', $deptCode)
                ->first();

            if (! $dept) {
                $this->command->warn("Department WNS/{$deptCode} not found. Skipping its positions.");

                continue;
            }

            foreach ($positions as $position) {
                Position::firstOrCreate(
                    [
                        'department_id' => $dept->id,
                        'code' => $position['code'],
                    ],
                    [
                        'name' => $position['name'],
                        'level' => $position['level'],
                        'access_level' => $position['access_level'],
                        'hierarchy_level' => $position['hierarchy_level'],
                        'is_active' => true,
                    ]
                );
            }

            $this->command->info("Positions seeded for WNS/{$deptCode}: ".count($positions).' role(s).');
        }
    }

    /**
     * Position definitions per department code.
     *
     * Mapping rationale:
     * - GM_SM uses level=hod / access_level=department_head. The descendant
     *   scope (visibility into sub-depts) comes from Department::descendantIds(),
     *   NOT from access_level. Setting access_level=executive would also grant
     *   blanket top-management gates (purchasing admin, view-reports), which
     *   is not what GM should have.
     * - ASGM_SM uses access_level=department_head; same scope as GM via parent
     *   department membership.
     * - Manager in sub-dept uses access_level=department_head (scope = single sub-dept).
     * - Coordinator/Lead uses access_level=team_leader.
     * - Specialist/Analyst/Engineer/Strategist/Designer uses access_level=staff.
     *
     * Level enum after migration 2026_03_02: c_level, hod, leader, staff.
     * `c_level` is reserved for true BOD/Director (CEO, MD, Chief of Staff in WG/EXEC).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function positionConfig(): array
    {
        return [
            'SM' => [
                ['code' => 'GM_SM', 'name' => 'General Manager Sales & Marketing',
                    'level' => 'hod', 'access_level' => 'department_head', 'hierarchy_level' => 0],
                ['code' => 'ASGM_SM', 'name' => 'Asisten GM Sales & Marketing',
                    'level' => 'hod', 'access_level' => 'department_head', 'hierarchy_level' => 1],
            ],
            'BS' => [
                ['code' => 'MGR_BS', 'name' => 'Business Solutions Manager',
                    'level' => 'hod', 'access_level' => 'department_head', 'hierarchy_level' => 1],
                ['code' => 'COORD_BS', 'name' => 'Business Solutions Coordinator',
                    'level' => 'leader', 'access_level' => 'team_leader', 'hierarchy_level' => 2],
                ['code' => 'SPEC_BS', 'name' => 'Business Solutions Specialist',
                    'level' => 'staff', 'access_level' => 'staff', 'hierarchy_level' => 3],
                ['code' => 'ENG_BS', 'name' => 'Commercial Engineer',
                    'level' => 'staff', 'access_level' => 'staff', 'hierarchy_level' => 3],
            ],
            'COM' => [
                ['code' => 'MGR_COM', 'name' => 'Commercial Manager',
                    'level' => 'hod', 'access_level' => 'department_head', 'hierarchy_level' => 1],
                ['code' => 'ANL_COM', 'name' => 'Pricing & Costing Analyst',
                    'level' => 'staff', 'access_level' => 'staff', 'hierarchy_level' => 3],
                ['code' => 'DSN_COM', 'name' => 'Commercial Creative Designer',
                    'level' => 'staff', 'access_level' => 'staff', 'hierarchy_level' => 3],
            ],
            'CMC' => [
                ['code' => 'LEAD_CMC', 'name' => 'Brand Experience & Partnership Lead',
                    'level' => 'leader', 'access_level' => 'team_leader', 'hierarchy_level' => 2],
                ['code' => 'ANL_CMC', 'name' => 'Market Analyst',
                    'level' => 'staff', 'access_level' => 'staff', 'hierarchy_level' => 3],
                ['code' => 'STG_CMC', 'name' => 'Creative Content Strategist',
                    'level' => 'staff', 'access_level' => 'staff', 'hierarchy_level' => 3],
                ['code' => 'DSN_CMC', 'name' => 'Creative Content Designer',
                    'level' => 'staff', 'access_level' => 'staff', 'hierarchy_level' => 3],
            ],
            'SO' => [
                ['code' => 'COORD_SO', 'name' => 'Sales Operation Coordinator',
                    'level' => 'leader', 'access_level' => 'team_leader', 'hierarchy_level' => 2],
            ],
        ];
    }
}
