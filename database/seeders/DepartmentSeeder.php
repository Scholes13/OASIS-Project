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
        // WNS specific departments as per design requirements
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

        // Default departments for other business units
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

        // Get business units
        $businessUnits = BusinessUnit::all();
        
        foreach ($businessUnits as $businessUnit) {
            // Use WNS specific departments for WNS business unit
            $departments = ($businessUnit->code === 'WNS') ? $wnsDepartments : $defaultDepartments;
            
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
