<?php

namespace Database\Seeders;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure Werkudara Group business unit exists
        $werkudaraGroup = BusinessUnit::firstOrCreate(
            ['code' => 'WG'],
            [
                'name' => 'Werkudara Group',
                'description' => 'Parent company of all Werkudara business units',
                'parent_id' => null,
                'numbering_config' => [
                    'pr_format' => 'PR.{DEPT}/{YEAR}/{MONTH}/{SEQUENCE}',
                    'sequence_padding' => 3,
                    'max_sequence' => 999,
                    'reset_annually' => false,
                    'reset_monthly' => false,
                ],
                'is_active' => true,
            ]
        );

        // Create Corporate department under Werkudara Group if not exists
        $corporateDept = Department::firstOrCreate(
            [
                'business_unit_id' => $werkudaraGroup->id,
                'code' => 'CORP',
            ],
            [
                'name' => 'Corporate',
                'is_active' => true,
            ]
        );

        // Create Super Admin position if not exists
        $superAdminPosition = Position::firstOrCreate(
            [
                'department_id' => $corporateDept->id,
                'code' => 'SUPERADMIN',
            ],
            [
                'name' => 'Super Administrator',
                'level' => 'hod',
                'hierarchy_level' => 0,
                'access_level' => 'executive',
                'is_active' => true,
            ]
        );

        // Create Super Admin User
        $superAdmin = User::firstOrCreate(
            ['email' => 'super@admin.com'],
            [
                'name' => 'Super Administrator',
                'phone_number' => '+628123456789',
                'primary_department_id' => $corporateDept->id,
                'primary_position_id' => $superAdminPosition->id,
                'supervisor_id' => null,
                'global_role' => 'super_admin',
                'password' => Hash::make('werkudara88'),
                'email_verified_at' => now(),
            ]
        );

        // Create business unit assignment for Super Admin
        UserBusinessUnit::firstOrCreate(
            [
                'user_id' => $superAdmin->id,
                'business_unit_id' => $werkudaraGroup->id,
                'department_id' => $corporateDept->id,
            ],
            [
                'position_id' => $superAdminPosition->id,
                'is_primary' => true,
                'is_active' => true,
            ]
        );

        $this->command->info('Super Admin created successfully:');
        $this->command->info('Email: super@admin.com');
        $this->command->info('Password: werkudara88');
        $this->command->info('Business Unit: Werkudara Group');
        $this->command->info('Role: Super Administrator');
    }
}
