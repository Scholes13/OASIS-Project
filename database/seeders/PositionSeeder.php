<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Department;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            // General Administration (GA) positions
            'GA' => [
                ['name' => 'Head of General Administration', 'code' => 'HOD_GA', 'level' => 'hod', 'hierarchy_level' => 1],
                ['name' => 'GA Manager', 'code' => 'MGR_GA', 'level' => 'leader', 'hierarchy_level' => 2],
                ['name' => 'Administrative Staff', 'code' => 'STAFF_GA', 'level' => 'staff', 'hierarchy_level' => 3],
                ['name' => 'Administrative Assistant', 'code' => 'ASST_GA', 'level' => 'staff', 'hierarchy_level' => 3],
            ],
            
            // Information Technology (IT) positions
            'IT' => [
                ['name' => 'Head of Information Technology', 'code' => 'HOD_IT', 'level' => 'hod', 'hierarchy_level' => 1],
                ['name' => 'IT Manager', 'code' => 'MGR_IT', 'level' => 'leader', 'hierarchy_level' => 2],
                ['name' => 'System Administrator', 'code' => 'SYSADMIN', 'level' => 'staff', 'hierarchy_level' => 3],
                ['name' => 'Software Developer', 'code' => 'DEV', 'level' => 'staff', 'hierarchy_level' => 3],
                ['name' => 'IT Support', 'code' => 'SUPPORT', 'level' => 'staff', 'hierarchy_level' => 3],
            ],
            
            // Human Resources (HR) positions
            'HR' => [
                ['name' => 'Head of Human Resources', 'code' => 'HOD_HR', 'level' => 'hod', 'hierarchy_level' => 1],
                ['name' => 'HR Manager', 'code' => 'MGR_HR', 'level' => 'leader', 'hierarchy_level' => 2],
                ['name' => 'HR Specialist', 'code' => 'SPEC_HR', 'level' => 'staff', 'hierarchy_level' => 3],
                ['name' => 'Recruiter', 'code' => 'RECRUITER', 'level' => 'staff', 'hierarchy_level' => 3],
            ],
            
            // Finance positions
            'FIN' => [
                ['name' => 'Head of Finance', 'code' => 'HOD_FIN', 'level' => 'hod', 'hierarchy_level' => 1],
                ['name' => 'Finance Manager', 'code' => 'MGR_FIN', 'level' => 'leader', 'hierarchy_level' => 2],
                ['name' => 'Accountant', 'code' => 'ACCOUNTANT', 'level' => 'staff', 'hierarchy_level' => 3],
                ['name' => 'Finance Analyst', 'code' => 'ANALYST_FIN', 'level' => 'staff', 'hierarchy_level' => 3],
            ],
            
            // Procurement positions
            'PROC' => [
                ['name' => 'Head of Procurement', 'code' => 'HOD_PROC', 'level' => 'hod', 'hierarchy_level' => 1],
                ['name' => 'Procurement Manager', 'code' => 'MGR_PROC', 'level' => 'leader', 'hierarchy_level' => 2],
                ['name' => 'Procurement Specialist', 'code' => 'SPEC_PROC', 'level' => 'staff', 'hierarchy_level' => 3],
                ['name' => 'Buyer', 'code' => 'BUYER', 'level' => 'staff', 'hierarchy_level' => 3],
            ],
            
            // Operations positions
            'OPS' => [
                ['name' => 'Head of Operations', 'code' => 'HOD_OPS', 'level' => 'hod', 'hierarchy_level' => 1],
                ['name' => 'Operations Manager', 'code' => 'MGR_OPS', 'level' => 'leader', 'hierarchy_level' => 2],
                ['name' => 'Operations Supervisor', 'code' => 'SUP_OPS', 'level' => 'leader', 'hierarchy_level' => 3],
                ['name' => 'Operations Staff', 'code' => 'STAFF_OPS', 'level' => 'staff', 'hierarchy_level' => 4],
            ],
        ];

        foreach ($positions as $deptCode => $deptPositions) {
            // Get all business units
            $businessUnits = \App\Models\BusinessUnit::all();
            
            foreach ($businessUnits as $businessUnit) {
                $department = Department::where('business_unit_id', $businessUnit->id)
                    ->where('code', $deptCode)
                    ->first();
                    
                if ($department) {
                    foreach ($deptPositions as $positionData) {
                        Position::firstOrCreate(
                            [
                                'department_id' => $department->id,
                                'code' => $positionData['code'],
                            ],
                            [
                                'name' => $positionData['name'],
                                'level' => $positionData['level'],
                                'hierarchy_level' => $positionData['hierarchy_level'],
                                'is_active' => true,
                            ]
                        );
                    }
                }
            }
        }
    }
}