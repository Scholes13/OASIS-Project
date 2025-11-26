<?php

namespace Database\Seeders;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $werkudaraGroup = BusinessUnit::where('code', 'WG')->first();

        if (! $werkudaraGroup) {
            $this->command->warn('Werkudara Group business unit not found. Skipping user seeding.');

            return;
        }

        $departments = Department::where('business_unit_id', $werkudaraGroup->id)
            ->whereIn('code', ['CEO', 'MD', 'SYSADMIN'])
            ->get()
            ->keyBy('code');

        $positions = [
            'CEO' => Position::where('department_id', $departments['CEO']->id ?? null)
                ->where('code', 'CEO_LEAD')
                ->first(),
            'MD' => Position::where('department_id', $departments['MD']->id ?? null)
                ->where('code', 'TOP_MANAGEMENT')
                ->first(),
            'SYSADMIN' => Position::where('department_id', $departments['SYSADMIN']->id ?? null)
                ->where('code', 'SYS_ADMIN')
                ->first(),
        ];

        foreach (['CEO', 'MD', 'SYSADMIN'] as $key) {
            if (! isset($departments[$key]) || ! $positions[$key]) {
                $this->command->warn("Missing department or position for {$key}. Skipping related user seeding.");

                return;
            }
        }

        $users = [
            [
                'name' => 'Fadli Fahmi Ali',
                'email' => 'fadli@werkudara.com',
                'phone_number' => '+6281200000001',
                'primary_department_id' => $departments['CEO']->id,
                'primary_position_id' => $positions['CEO']->id,
                'supervisor_id' => null,
                'global_role' => 'user',
                'password' => Hash::make('werkudara88'),
                'assignment' => [
                    'business_unit_id' => $werkudaraGroup->id,
                    'department_id' => $departments['CEO']->id,
                    'position_id' => $positions['CEO']->id,
                    'role' => 'bod',
                ],
            ],
            [
                'name' => 'I Gusti Putu Yaktianuraga',
                'email' => 'bagus@werkudara.com',
                'phone_number' => '+6281200000002',
                'primary_department_id' => $departments['MD']->id,
                'primary_position_id' => $positions['MD']->id,
                'supervisor_id' => null,
                'global_role' => 'user',
                'password' => Hash::make('werkudara88'),
                'assignment' => [
                    'business_unit_id' => $werkudaraGroup->id,
                    'department_id' => $departments['MD']->id,
                    'position_id' => $positions['MD']->id,
                    'role' => 'bod',
                ],
            ],
            [
                'name' => 'Super Admin',
                'email' => 'super@werkudara.com',
                'phone_number' => '+6281200000003',
                'primary_department_id' => $departments['SYSADMIN']->id,
                'primary_position_id' => $positions['SYSADMIN']->id,
                'supervisor_id' => null,
                'global_role' => 'super_admin',
                'password' => Hash::make('werkudara88'),
                'assignment' => [
                    'business_unit_id' => $werkudaraGroup->id,
                    'department_id' => $departments['SYSADMIN']->id,
                    'position_id' => $positions['SYSADMIN']->id,
                    'role' => 'admin',
                ],
            ],
        ];

        foreach ($users as $userData) {
            $assignment = $userData['assignment'];
            unset($userData['assignment']);

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, ['is_active' => true])
            );

            UserBusinessUnit::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'business_unit_id' => $assignment['business_unit_id'],
                    'department_id' => $assignment['department_id'],
                ],
                [
                    'position_id' => $assignment['position_id'],
                    'role' => $assignment['role'],
                    'is_primary' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}
