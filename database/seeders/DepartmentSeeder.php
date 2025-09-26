<?php

namespace Database\Seeders;

use App\Models\BusinessUnit;
use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $werkudaraGroupDepartments = [
            [
                'code' => 'CEO',
                'name' => 'Chief Executive Office',
            ],
            [
                'code' => 'MD',
                'name' => 'Managing Director',
            ],
            [
                'code' => 'SYSADMIN',
                'name' => 'System Administration',
            ],
        ];

        $wnsDepartments = [
            [
                'code' => 'BAS',
                'name' => 'Business & Administrative Services',
            ],
            [
                'code' => 'CORP',
                'name' => 'Corporate',
            ],
            [
                'code' => 'GA',
                'name' => 'General Affair',
            ],
            [
                'code' => 'HR',
                'name' => 'Human Resource',
            ],
            [
                'code' => 'ACC',
                'name' => 'Accounting',
            ],
            [
                'code' => 'TEP',
                'name' => 'Tour & Event Planning',
            ],
            [
                'code' => 'ACS',
                'name' => 'Art & Creative Support',
            ],
            [
                'code' => 'SO',
                'name' => 'Sales Operation',
            ],
            [
                'code' => 'BID',
                'name' => 'Business Innovation Development',
            ],
        ];

        $defaultDepartments = [
            [
                'code' => 'GA',
                'name' => 'General Administration',
            ],
            [
                'code' => 'IT',
                'name' => 'Information Technology',
            ],
            [
                'code' => 'HR',
                'name' => 'Human Resources',
            ],
            [
                'code' => 'FIN',
                'name' => 'Finance',
            ],
            [
                'code' => 'PROC',
                'name' => 'Procurement',
            ],
            [
                'code' => 'OPS',
                'name' => 'Operations',
            ],
            [
                'code' => 'SALES',
                'name' => 'Sales & Marketing',
            ],
            [
                'code' => 'ENG',
                'name' => 'Engineering',
            ],
        ];

        $businessUnits = BusinessUnit::all();

        foreach ($businessUnits as $businessUnit) {
            if ($businessUnit->code === 'WG') {
                $departments = $werkudaraGroupDepartments;
            } elseif ($businessUnit->code === 'WNS') {
                $departments = $wnsDepartments;
            } else {
                $departments = $defaultDepartments;
            }

            foreach ($departments as $deptData) {
                Department::firstOrCreate(
                    [
                        'business_unit_id' => $businessUnit->id,
                        'code' => $deptData['code'],
                    ],
                    [
                        'name' => $deptData['name'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
