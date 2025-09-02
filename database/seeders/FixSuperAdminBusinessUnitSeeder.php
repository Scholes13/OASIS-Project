<?php

namespace Database\Seeders;

use App\Models\BusinessUnit;
use App\Models\User;
use App\Models\UserBusinessUnit;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;

class FixSuperAdminBusinessUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Fixing super admin business unit assignment...');

        // Check if Werkudara Group exists
        $werkudaraGroup = BusinessUnit::where('code', 'WG')->first();
        
        if (!$werkudaraGroup) {
            $this->command->error('Werkudara Group (WG) business unit not found. Please run WerkudaraGroupSeeder first.');
            return;
        }

        // Get super admin user
        $superAdmin = User::where('email', 'admin@wns.com')->first();
        
        if (!$superAdmin) {
            $this->command->error('Super admin user (admin@wns.com) not found.');
            return;
        }

        // Get Corporate department
        $corporateDept = Department::where('business_unit_id', $werkudaraGroup->id)
            ->where('code', 'CORP')
            ->first();

        if (!$corporateDept) {
            $this->command->error('Corporate department not found in Werkudara Group.');
            return;
        }

        // Get CEO position
        $ceoPosition = Position::where('department_id', $corporateDept->id)
            ->where('code', 'CEO')
            ->first();

        if (!$ceoPosition) {
            $this->command->error('CEO position not found in Corporate department.');
            return;
        }

        // Update super admin primary assignments
        $superAdmin->update([
            'primary_department_id' => $corporateDept->id,
            'primary_position_id' => $ceoPosition->id,
        ]);

        // Remove existing primary assignment
        UserBusinessUnit::where('user_id', $superAdmin->id)
            ->update(['is_primary' => false]);

        // Create or update Werkudara Group assignment as primary
        UserBusinessUnit::updateOrCreate(
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

        $this->command->info('✅ Super admin now assigned to Werkudara Group as primary business unit');
        $this->command->info('✅ Super admin can now access all child business units:');
        
        // Show accessible business units
        $childBusinessUnits = BusinessUnit::where('parent_id', $werkudaraGroup->id)->get();
        foreach ($childBusinessUnits as $child) {
            $this->command->info("   - {$child->code}: {$child->name}");
        }

        // Verify access
        $accessibleIds = $superAdmin->getAccessibleBusinessUnitIds();
        $this->command->info("✅ Total accessible business units: " . count($accessibleIds));
    }
}