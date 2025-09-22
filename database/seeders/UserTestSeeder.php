<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\BusinessUnit;
use App\Models\Department;
use App\Models\Position;
use App\Models\UserBusinessUnit;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating test users for Werkudara Group...');

        // Get Werkudara Group business unit
        $businessUnit = BusinessUnit::where('code', 'WG')->first();
        
        if (!$businessUnit) {
            $this->command->error('Werkudara Group business unit not found!');
            return;
        }

        // Get departments
        $corpDept = Department::where('business_unit_id', $businessUnit->id)
                             ->where('code', 'CORP')
                             ->first();
        
        $itDept = Department::where('business_unit_id', $businessUnit->id)
                           ->where('code', 'IT')
                           ->first();

        $hrDept = Department::where('business_unit_id', $businessUnit->id)
                           ->where('code', 'HR')
                           ->first();

        $finDept = Department::where('business_unit_id', $businessUnit->id)
                            ->where('code', 'FIN')
                            ->first();

        // Ensure departments exist
        if (!$corpDept) {
            $corpDept = Department::create([
                'business_unit_id' => $businessUnit->id,
                'code' => 'CORP',
                'name' => 'Corporate',
                'is_active' => true,
            ]);
        }

        if (!$itDept) {
            $itDept = Department::create([
                'business_unit_id' => $businessUnit->id,
                'code' => 'IT',
                'name' => 'Information Technology',
                'is_active' => true,
            ]);
        }

        if (!$hrDept) {
            $hrDept = Department::create([
                'business_unit_id' => $businessUnit->id,
                'code' => 'HR',
                'name' => 'Human Resources',
                'is_active' => true,
            ]);
        }

        if (!$finDept) {
            $finDept = Department::create([
                'business_unit_id' => $businessUnit->id,
                'code' => 'FIN',
                'name' => 'Finance',
                'is_active' => true,
            ]);
        }

        // Create positions if they don't exist
        $managerPosition = Position::firstOrCreate([
            'department_id' => $corpDept->id,
            'code' => 'MGR',
        ], [
            'name' => 'Manager',
            'level' => 'hod',
            'hierarchy_level' => 3,
            'is_active' => true,
        ]);

        $staffPosition = Position::firstOrCreate([
            'department_id' => $corpDept->id,
            'code' => 'STAFF',
        ], [
            'name' => 'Staff',
            'level' => 'staff',
            'hierarchy_level' => 1,
            'is_active' => true,
        ]);

        // Ensure roles exist
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['guard_name' => 'web']);

        // Test users data
        $testUsers = [
            [
                'name' => 'John Manager',
                'email' => 'john.manager@werkudara.com',
                'password' => Hash::make('password123'),
                'phone_number' => '081234567890',
                'primary_department_id' => $corpDept->id,
                'primary_position_id' => $managerPosition->id,
                'global_role' => 'user',
                'is_active' => true,
                'email_verified_at' => now(),
                'department' => $corpDept,
                'position' => $managerPosition,
                'role' => $adminRole,
                'bu_role' => 'manager'
            ],
            [
                'name' => 'Sarah Supervisor',
                'email' => 'sarah.supervisor@werkudara.com',
                'password' => Hash::make('password123'),
                'phone_number' => '081234567891',
                'primary_department_id' => $hrDept->id,
                'primary_position_id' => $managerPosition->id,
                'global_role' => 'user',
                'is_active' => true,
                'email_verified_at' => now(),
                'department' => $hrDept,
                'position' => $managerPosition,
                'role' => $userRole,
                'bu_role' => 'supervisor'
            ]
        ];

        foreach ($testUsers as $userData) {
            // Check if user already exists
            $existingUser = User::where('email', $userData['email'])->first();
            
            if ($existingUser) {
                $this->command->warn("User {$userData['email']} already exists. Skipping...");
                continue;
            }

            // Create user
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'phone_number' => $userData['phone_number'],
                'primary_department_id' => $userData['primary_department_id'],
                'primary_position_id' => $userData['primary_position_id'],
                'global_role' => $userData['global_role'],
                'is_active' => $userData['is_active'],
                'email_verified_at' => $userData['email_verified_at'],
            ]);

            // Assign role
            $user->assignRole($userData['role']);

            // Create business unit assignment
            UserBusinessUnit::create([
                'user_id' => $user->id,
                'business_unit_id' => $businessUnit->id,
                'department_id' => $userData['department']->id,
                'position_id' => $userData['position']->id,
                'is_primary' => true,
                'is_active' => true,
                'permissions' => json_encode([
                    'can_create_pr' => true,
                    'can_approve_pr' => true,
                    'can_view_all_pr' => $userData['role']->name === 'admin',
                    'can_manage_users' => $userData['role']->name === 'admin',
                ]),
            ]);

            $this->command->info("✓ Created user: {$userData['name']} ({$userData['email']})");
            $this->command->info("  - Department: {$userData['department']->name}");
            $this->command->info("  - Position: {$userData['position']->name}");
            $this->command->info("  - Role: {$userData['role']->name}");
            $this->command->info("  - Global Role: {$userData['global_role']}");
        }

        $this->command->info('');
        $this->command->info('=== Test Users Created ===');
        $this->command->info('Login credentials for testing:');
        $this->command->info('');
        $this->command->info('1. Manager User:');
        $this->command->info('   Email: john.manager@werkudara.com');
        $this->command->info('   Password: password123');
        $this->command->info('   Role: Admin (can approve PRs)');
        $this->command->info('');
        $this->command->info('2. Supervisor User:');
        $this->command->info('   Email: sarah.supervisor@werkudara.com');
        $this->command->info('   Password: password123');
        $this->command->info('   Role: User (can approve PRs)');
        $this->command->info('');
        $this->command->info('Both users are in Werkudara Group business unit and can be used for approval testing.');
    }
}
