<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            // Werkudara Group top management positions
            'CEO' => [
                ['name' => 'Chief Executive Officer', 'code' => 'CEO_LEAD', 'level' => 'hod', 'hierarchy_level' => 1, 'access_level' => 'executive'],
            ],
            'MD' => [
                ['name' => 'Top Management', 'code' => 'TOP_MANAGEMENT', 'level' => 'hod', 'hierarchy_level' => 1, 'access_level' => 'executive'],
            ],
            'SYSADMIN' => [
                ['name' => 'System Administration', 'code' => 'SYS_ADMIN', 'level' => 'hod', 'hierarchy_level' => 1, 'access_level' => 'executive'],
            ],

            // General Administration (GA) positions
            'GA' => [
                ['name' => 'Head of General Administration', 'code' => 'HOD_GA', 'level' => 'hod', 'hierarchy_level' => 1, 'access_level' => 'department_head'],
                ['name' => 'GA Manager', 'code' => 'MGR_GA', 'level' => 'leader', 'hierarchy_level' => 2, 'access_level' => 'team_leader'],
                ['name' => 'Administrative Staff', 'code' => 'STAFF_GA', 'level' => 'staff', 'hierarchy_level' => 3, 'access_level' => 'staff'],
                ['name' => 'Administrative Assistant', 'code' => 'ASST_GA', 'level' => 'staff', 'hierarchy_level' => 3, 'access_level' => 'staff'],
            ],

            // Information Technology (IT) positions
            'IT' => [
                ['name' => 'Head of Information Technology', 'code' => 'HOD_IT', 'level' => 'hod', 'hierarchy_level' => 1, 'access_level' => 'department_head'],
                ['name' => 'IT Manager', 'code' => 'MGR_IT', 'level' => 'leader', 'hierarchy_level' => 2, 'access_level' => 'team_leader'],
                ['name' => 'System Administrator', 'code' => 'SYSADMIN', 'level' => 'staff', 'hierarchy_level' => 3, 'access_level' => 'staff'],
                ['name' => 'Software Developer', 'code' => 'DEV', 'level' => 'staff', 'hierarchy_level' => 3, 'access_level' => 'staff'],
                ['name' => 'IT Support', 'code' => 'SUPPORT', 'level' => 'staff', 'hierarchy_level' => 3, 'access_level' => 'staff'],
            ],

            // Human Resources (HR) positions
            'HR' => [
                ['name' => 'Head of Human Resources', 'code' => 'HOD_HR', 'level' => 'hod', 'hierarchy_level' => 1, 'access_level' => 'department_head'],
                ['name' => 'HR Manager', 'code' => 'MGR_HR', 'level' => 'leader', 'hierarchy_level' => 2, 'access_level' => 'team_leader'],
                ['name' => 'HR Specialist', 'code' => 'SPEC_HR', 'level' => 'staff', 'hierarchy_level' => 3, 'access_level' => 'staff'],
                ['name' => 'Recruiter', 'code' => 'RECRUITER', 'level' => 'staff', 'hierarchy_level' => 3, 'access_level' => 'staff'],
            ],

            // Finance positions
            'FIN' => [
                ['name' => 'Head of Finance', 'code' => 'HOD_FIN', 'level' => 'hod', 'hierarchy_level' => 1, 'access_level' => 'department_head'],
                ['name' => 'Finance Manager', 'code' => 'MGR_FIN', 'level' => 'leader', 'hierarchy_level' => 2, 'access_level' => 'team_leader'],
                ['name' => 'Accountant', 'code' => 'ACCOUNTANT', 'level' => 'staff', 'hierarchy_level' => 3, 'access_level' => 'staff'],
                ['name' => 'Finance Analyst', 'code' => 'ANALYST_FIN', 'level' => 'staff', 'hierarchy_level' => 3, 'access_level' => 'staff'],
            ],

            // Procurement positions
            'PROC' => [
                ['name' => 'Head of Procurement', 'code' => 'HOD_PROC', 'level' => 'hod', 'hierarchy_level' => 1, 'access_level' => 'department_head'],
                ['name' => 'Procurement Manager', 'code' => 'MGR_PROC', 'level' => 'leader', 'hierarchy_level' => 2, 'access_level' => 'team_leader'],
                ['name' => 'Procurement Specialist', 'code' => 'SPEC_PROC', 'level' => 'staff', 'hierarchy_level' => 3, 'access_level' => 'staff'],
                ['name' => 'Buyer', 'code' => 'BUYER', 'level' => 'staff', 'hierarchy_level' => 3, 'access_level' => 'staff'],
            ],

            // Operations positions
            'OPS' => [
                ['name' => 'Head of Operations', 'code' => 'HOD_OPS', 'level' => 'hod', 'hierarchy_level' => 1, 'access_level' => 'department_head'],
                ['name' => 'Operations Manager', 'code' => 'MGR_OPS', 'level' => 'leader', 'hierarchy_level' => 2, 'access_level' => 'team_leader'],
                ['name' => 'Operations Supervisor', 'code' => 'SUP_OPS', 'level' => 'leader', 'hierarchy_level' => 3, 'access_level' => 'team_leader'],
                ['name' => 'Operations Staff', 'code' => 'STAFF_OPS', 'level' => 'staff', 'hierarchy_level' => 4, 'access_level' => 'staff'],
            ],
        ];

        foreach ($positions as $deptCode => $deptPositions) {
            $departments = Department::where('code', $deptCode)->get();

            foreach ($departments as $department) {
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
                            'access_level' => $positionData['access_level'],
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}
