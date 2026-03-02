<?php

namespace Database\Seeders\TEE;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder for Takshaka (TEE) Business Unit
 *
 * Creates:
 * - TEE Department
 * - Positions for TEE (Head, Leader, Staff)
 * - Users with their assignments
 */
class TEESeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Takshaka (TEE) data...');

        // Get TEE Business Unit
        $tee = BusinessUnit::where('code', 'TEE')->first();

        if (! $tee) {
            $this->command->error('TEE Business Unit not found!');

            return;
        }

        // Create TEE Department first
        $department = $this->createDepartment($tee);

        // Create positions for TEE department
        $positions = $this->createPositions($department);

        // Create Users
        $this->createUsers($tee, $department, $positions);

        $this->command->info('TEE seeding completed!');
    }

    private function createDepartment(BusinessUnit $businessUnit): Department
    {
        $department = Department::firstOrCreate(
            [
                'code' => 'TEE',
                'business_unit_id' => $businessUnit->id,
            ],
            [
                'name' => 'TEE',
                'is_active' => true,
            ]
        );

        $this->command->info("Created department: {$department->name}");

        return $department;
    }

    private function createPositions(Department $department): array
    {
        $positions = [];

        // Head of TEE
        $positions['hod'] = Position::firstOrCreate(
            ['name' => 'Head of TEE', 'department_id' => $department->id],
            [
                'level' => 'hod',
                'access_level' => 'department_head',
            ]
        );

        // Leader of TEE
        $positions['leader'] = Position::firstOrCreate(
            ['name' => 'Leader of TEE', 'department_id' => $department->id],
            [
                'level' => 'leader',
                'access_level' => 'team_leader',
            ]
        );

        // Staff of TEE
        $positions['staff'] = Position::firstOrCreate(
            ['name' => 'Staff of TEE', 'department_id' => $department->id],
            [
                'level' => 'staff',
                'access_level' => 'staff',
            ]
        );

        $this->command->info('Created TEE positions');

        return $positions;
    }

    private function createUsers(BusinessUnit $businessUnit, Department $department, array $positions): void
    {
        $password = Hash::make('takshaka88');

        $users = [
            [
                'name' => 'Nofri',
                'email' => 'nofri@takshaka.id',
                'position' => $positions['hod'],
                'role' => 'Head of Department',
            ],
            [
                'name' => 'Rama',
                'email' => 'rama@takshaka.id',
                'position' => $positions['leader'],
                'role' => 'Leader of Department',
            ],
            [
                'name' => 'Viana',
                'email' => 'viana@takshaka.id',
                'position' => $positions['staff'],
                'role' => 'Staff',
            ],
            [
                'name' => 'Yadi',
                'email' => 'yadi@takshaka.id',
                'position' => $positions['staff'],
                'role' => 'Staff',
            ],
            [
                'name' => 'Ayu',
                'email' => 'ayu@takshaka.id',
                'position' => $positions['staff'],
                'role' => 'Staff',
            ],
        ];

        $hodUser = null;

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $password,
                    'phone_number' => '08'.rand(1000000000, 9999999999),
                    'primary_department_id' => $department->id,
                    'primary_position_id' => $userData['position']->id,
                    'global_role' => 'user',
                    'is_active' => true,
                ]
            );

            // Set supervisor for non-HOD users
            if ($userData['position']->level !== 'hod' && $hodUser) {
                $user->supervisor_id = $hodUser->id;
                $user->save();
            }

            // Store HOD user for supervisor assignment
            if ($userData['position']->level === 'hod') {
                $hodUser = $user;
            }

            // Create UserBusinessUnit assignment
            UserBusinessUnit::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'business_unit_id' => $businessUnit->id,
                    'department_id' => $department->id,
                ],
                [
                    'position_id' => $userData['position']->id,
                    'is_primary' => true,
                    'is_active' => true,
                ]
            );

            $this->command->info("Created user: {$user->name} ({$userData['role']})");
        }
    }
}
