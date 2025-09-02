<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BusinessUnit;
use App\Models\Department;
use App\Models\Position;
use App\Models\UserBusinessUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get business units and departments
        $wns = BusinessUnit::where('code', 'WNS')->first();
        $gaDept = Department::where('business_unit_id', $wns->id)->where('code', 'GA')->first();
        $itDept = Department::where('business_unit_id', $wns->id)->where('code', 'IT')->first();
        $hrDept = Department::where('business_unit_id', $wns->id)->where('code', 'HR')->first();
        $finDept = Department::where('business_unit_id', $wns->id)->where('code', 'FIN')->first();
        $procDept = Department::where('business_unit_id', $wns->id)->where('code', 'PROC')->first();
        
        // Get positions
        $hodGa = Position::where('department_id', $gaDept->id)->where('level', 'hod')->first();
        $mgrGa = Position::where('department_id', $gaDept->id)->where('level', 'leader')->first();
        $staffGa = Position::where('department_id', $gaDept->id)->where('level', 'staff')->first();
        
        $hodIt = Position::where('department_id', $itDept->id)->where('level', 'hod')->first();
        $devIt = Position::where('department_id', $itDept->id)->where('code', 'DEV')->first();
        
        $hodHr = Position::where('department_id', $hrDept->id)->where('level', 'hod')->first();
        $specHr = Position::where('department_id', $hrDept->id)->where('code', 'SPEC_HR')->first();
        
        $hodFin = Position::where('department_id', $finDept->id)->where('level', 'hod')->first();
        $hodProc = Position::where('department_id', $procDept->id)->where('level', 'hod')->first();

        $sampleUsers = [
            // Super Admin
            [
                'name' => 'System Administrator',
                'email' => 'admin@wns.com',
                'phone_number' => '+62812345678901',
                'primary_department_id' => $itDept->id,
                'primary_position_id' => $hodIt->id,
                'supervisor_id' => null,
                'global_role' => 'super_admin',
                'password' => Hash::make('password'),
                'business_units' => [
                    [
                        'business_unit_id' => $wns->id,
                        'department_id' => $itDept->id,
                        'position_id' => $hodIt->id,
                        'role' => 'admin',
                        'is_primary' => true,
                    ]
                ]
            ],

            // Board of Director (BOD)
            [
                'name' => 'Robert Johnson',
                'email' => 'robert.johnson@wns.com',
                'phone_number' => '+62812345678902',
                'primary_department_id' => $gaDept->id,
                'primary_position_id' => $hodGa->id,
                'supervisor_id' => null,
                'global_role' => 'user',
                'password' => Hash::make('password'),
                'business_units' => [
                    [
                        'business_unit_id' => $wns->id,
                        'department_id' => $gaDept->id,
                        'position_id' => $hodGa->id,
                        'role' => 'bod',
                        'is_primary' => true,
                    ]
                ]
            ],

            // Head of Departments (HODs)
            [
                'name' => 'Alice Smith',
                'email' => 'alice.smith@wns.com',
                'phone_number' => '+62812345678903',
                'primary_department_id' => $gaDept->id,
                'primary_position_id' => $hodGa->id,
                'supervisor_id' => null, // Will be set to BOD user
                'global_role' => 'user',
                'password' => Hash::make('password'),
                'business_units' => [
                    [
                        'business_unit_id' => $wns->id,
                        'department_id' => $gaDept->id,
                        'position_id' => $hodGa->id,
                        'role' => 'hod',
                        'is_primary' => true,
                    ]
                ]
            ],

            [
                'name' => 'John Doe',
                'email' => 'john.doe@wns.com',
                'phone_number' => '+62812345678904',
                'primary_department_id' => $itDept->id,
                'primary_position_id' => $hodIt->id,
                'supervisor_id' => null, // Will be set to BOD user
                'global_role' => 'user',
                'password' => Hash::make('password'),
                'business_units' => [
                    [
                        'business_unit_id' => $wns->id,
                        'department_id' => $itDept->id,
                        'position_id' => $hodIt->id,
                        'role' => 'hod',
                        'is_primary' => true,
                    ]
                ]
            ],

            [
                'name' => 'Sarah Wilson',
                'email' => 'sarah.wilson@wns.com',
                'phone_number' => '+62812345678905',
                'primary_department_id' => $hrDept->id,
                'primary_position_id' => $hodHr->id,
                'supervisor_id' => null, // Will be set to BOD user
                'global_role' => 'user',
                'password' => Hash::make('password'),
                'business_units' => [
                    [
                        'business_unit_id' => $wns->id,
                        'department_id' => $hrDept->id,
                        'position_id' => $hodHr->id,
                        'role' => 'hod',
                        'is_primary' => true,
                    ]
                ]
            ],

            [
                'name' => 'Michael Brown',
                'email' => 'michael.brown@wns.com',
                'phone_number' => '+62812345678906',
                'primary_department_id' => $finDept->id,
                'primary_position_id' => $hodFin->id,
                'supervisor_id' => null, // Will be set to BOD user
                'global_role' => 'user',
                'password' => Hash::make('password'),
                'business_units' => [
                    [
                        'business_unit_id' => $wns->id,
                        'department_id' => $finDept->id,
                        'position_id' => $hodFin->id,
                        'role' => 'hod',
                        'is_primary' => true,
                    ]
                ]
            ],

            [
                'name' => 'Lisa Davis',
                'email' => 'lisa.davis@wns.com',
                'phone_number' => '+62812345678907',
                'primary_department_id' => $procDept->id,
                'primary_position_id' => $hodProc->id,
                'supervisor_id' => null, // Will be set to BOD user
                'global_role' => 'user',
                'password' => Hash::make('password'),
                'business_units' => [
                    [
                        'business_unit_id' => $wns->id,
                        'department_id' => $procDept->id,
                        'position_id' => $hodProc->id,
                        'role' => 'hod',
                        'is_primary' => true,
                    ]
                ]
            ],

            // Leaders
            [
                'name' => 'Tom Miller',
                'email' => 'tom.miller@wns.com',
                'phone_number' => '+62812345678908',
                'primary_department_id' => $gaDept->id,
                'primary_position_id' => $mgrGa->id,
                'supervisor_id' => null, // Will be set to HOD GA
                'global_role' => 'user',
                'password' => Hash::make('password'),
                'business_units' => [
                    [
                        'business_unit_id' => $wns->id,
                        'department_id' => $gaDept->id,
                        'position_id' => $mgrGa->id,
                        'role' => 'leader',
                        'is_primary' => true,
                    ]
                ]
            ],

            // Staff members
            [
                'name' => 'Emma Garcia',
                'email' => 'emma.garcia@wns.com',
                'phone_number' => '+62812345678909',
                'primary_department_id' => $gaDept->id,
                'primary_position_id' => $staffGa->id,
                'supervisor_id' => null, // Will be set to GA Manager
                'global_role' => 'user',
                'password' => Hash::make('password'),
                'business_units' => [
                    [
                        'business_unit_id' => $wns->id,
                        'department_id' => $gaDept->id,
                        'position_id' => $staffGa->id,
                        'role' => 'staff',
                        'is_primary' => true,
                    ]
                ]
            ],

            [
                'name' => 'James Rodriguez',
                'email' => 'james.rodriguez@wns.com',
                'phone_number' => '+62812345678910',
                'primary_department_id' => $itDept->id,
                'primary_position_id' => $devIt->id,
                'supervisor_id' => null, // Will be set to HOD IT
                'global_role' => 'user',
                'password' => Hash::make('password'),
                'business_units' => [
                    [
                        'business_unit_id' => $wns->id,
                        'department_id' => $itDept->id,
                        'position_id' => $devIt->id,
                        'role' => 'staff',
                        'is_primary' => true,
                    ]
                ]
            ],

            [
                'name' => 'Sophie Chen',
                'email' => 'sophie.chen@wns.com',
                'phone_number' => '+62812345678911',
                'primary_department_id' => $hrDept->id,
                'primary_position_id' => $specHr->id,
                'supervisor_id' => null, // Will be set to HOD HR
                'global_role' => 'user',
                'password' => Hash::make('password'),
                'business_units' => [
                    [
                        'business_unit_id' => $wns->id,
                        'department_id' => $hrDept->id,
                        'position_id' => $specHr->id,
                        'role' => 'staff',
                        'is_primary' => true,
                    ]
                ]
            ],
        ];

        $createdUsers = [];

        // Create users first
        foreach ($sampleUsers as $userData) {
            $businessUnits = $userData['business_units'];
            unset($userData['business_units']);
            
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
            
            $createdUsers[$userData['email']] = [
                'user' => $user,
                'business_units' => $businessUnits
            ];
        }

        // Set up supervisor relationships
        $bodUser = $createdUsers['robert.johnson@wns.com']['user'];
        $hodGaUser = $createdUsers['alice.smith@wns.com']['user'];
        $gaManagerUser = $createdUsers['tom.miller@wns.com']['user'];

        // Update supervisor relationships
        User::where('email', 'alice.smith@wns.com')->update(['supervisor_id' => $bodUser->id]);
        User::where('email', 'john.doe@wns.com')->update(['supervisor_id' => $bodUser->id]);
        User::where('email', 'sarah.wilson@wns.com')->update(['supervisor_id' => $bodUser->id]);
        User::where('email', 'michael.brown@wns.com')->update(['supervisor_id' => $bodUser->id]);
        User::where('email', 'lisa.davis@wns.com')->update(['supervisor_id' => $bodUser->id]);
        User::where('email', 'tom.miller@wns.com')->update(['supervisor_id' => $hodGaUser->id]);
        User::where('email', 'emma.garcia@wns.com')->update(['supervisor_id' => $gaManagerUser->id]);
        User::where('email', 'james.rodriguez@wns.com')->update(['supervisor_id' => $createdUsers['john.doe@wns.com']['user']->id]);
        User::where('email', 'sophie.chen@wns.com')->update(['supervisor_id' => $createdUsers['sarah.wilson@wns.com']['user']->id]);

        // Create business unit assignments
        foreach ($createdUsers as $userData) {
            $user = $userData['user'];
            
            foreach ($userData['business_units'] as $buData) {
                UserBusinessUnit::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'business_unit_id' => $buData['business_unit_id'],
                        'department_id' => $buData['department_id'],
                    ],
                    array_merge($buData, [
                        'position_id' => $buData['position_id'],
                        'role' => $buData['role'],
                        'is_primary' => $buData['is_primary'],
                        'is_active' => true,
                    ])
                );
            }
        }
    }
}