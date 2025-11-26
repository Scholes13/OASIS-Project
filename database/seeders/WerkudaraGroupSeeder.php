<?php

namespace Database\Seeders;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use Illuminate\Database\Seeder;

class WerkudaraGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Werkudara Group as parent company
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

        // Update existing business units to be children of Werkudara Group
        $childBusinessUnits = ['WNS', 'UT', 'MRP', 'WNN'];

        foreach ($childBusinessUnits as $code) {
            BusinessUnit::where('code', $code)->update([
                'parent_id' => $werkudaraGroup->id,
            ]);
        }

        // Create Corporate department under Werkudara Group
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

        // Create BOD position
        $bodPosition = Position::firstOrCreate(
            [
                'department_id' => $corporateDept->id,
                'code' => 'BOD',
            ],
            [
                'name' => 'Board of Directors',
                'level' => 'hod',
                'hierarchy_level' => 0,
                'access_level' => 'executive',
                'is_active' => true,
            ]
        );

        // Create CEO position
        $ceoPosition = Position::firstOrCreate(
            [
                'department_id' => $corporateDept->id,
                'code' => 'CEO',
            ],
            [
                'name' => 'Chief Executive Officer',
                'level' => 'hod',
                'hierarchy_level' => 1,
                'access_level' => 'executive',
                'is_active' => true,
            ]
        );

        // Update super admin to be assigned to Werkudara Group
        $superAdmin = User::where('email', 'admin@wns.com')->first();

        if ($superAdmin) {
            // Update primary business unit assignment
            $superAdmin->update([
                'primary_department_id' => $corporateDept->id,
                'primary_position_id' => $ceoPosition->id,
            ]);

            // Create new business unit assignment for Werkudara Group
            UserBusinessUnit::firstOrCreate(
                [
                    'user_id' => $superAdmin->id,
                    'business_unit_id' => $werkudaraGroup->id,
                    'department_id' => $corporateDept->id,
                ],
                [
                    'position_id' => $ceoPosition->id,
                    'role' => 'admin', // Use 'admin' role instead of 'super_admin'
                    'is_primary' => true,
                    'is_active' => true,
                ]
            );

            // Update existing WNS assignment to not be primary
            UserBusinessUnit::where('user_id', $superAdmin->id)
                ->where('business_unit_id', '!=', $werkudaraGroup->id)
                ->update(['is_primary' => false]);
        }

        $this->command->info('Werkudara Group created successfully');
        $this->command->info('Super admin now has access to all business units through Werkudara Group');
    }
}
