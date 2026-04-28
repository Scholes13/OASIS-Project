<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Create Strategic Sourcing department for all business units that don't have one.
     * Assign ALL SS members from WNS to SS department in each BU.
     * Set all SS members as Purchasing Admin.
     */
    public function up(): void
    {
        // Get the source SS department from WNS for reference
        $sourceSSdept = DB::table('departments')
            ->where('code', 'SS')
            ->where('business_unit_id', 2) // WNS
            ->first();

        if (! $sourceSSdept) {
            // Skip on fresh database or test environment – seeder will handle this
            return;
        }

        // Get ALL SS members from WNS with their positions
        $ssMembers = DB::table('user_business_units')
            ->where('department_id', $sourceSSdept->id)
            ->where('is_active', 1)
            ->get();

        if ($ssMembers->isEmpty()) {
            // Skip on fresh database or test environment – seeder will handle this
            return;
        }

        // Step 1: Set all SS members in WNS as Purchasing Admin
        DB::table('user_business_units')
            ->where('department_id', $sourceSSdept->id)
            ->update(['is_purchasing_admin' => 1]);

        // Get all active BUs except WG (parent) and WNS (already has SS)
        $businessUnits = DB::table('business_units')
            ->where('is_active', 1)
            ->whereNotIn('id', [1, 2]) // Exclude WG and WNS
            ->get();

        foreach ($businessUnits as $bu) {
            // Check if SS already exists in this BU
            $existingSS = DB::table('departments')
                ->where('business_unit_id', $bu->id)
                ->where('code', 'SS')
                ->first();

            if ($existingSS) {
                $newDeptId = $existingSS->id;
            } else {
                // Create SS department for this BU
                $newDeptId = DB::table('departments')->insertGetId([
                    'business_unit_id' => $bu->id,
                    'code' => 'SS',
                    'name' => 'Strategic Sourcing',
                    'is_active' => 1,
                    'is_purchasing_department' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Assign ALL SS members to this BU's SS department
            foreach ($ssMembers as $member) {
                // Check if member is already assigned to this BU's SS
                $existingAssignment = DB::table('user_business_units')
                    ->where('user_id', $member->user_id)
                    ->where('business_unit_id', $bu->id)
                    ->where('department_id', $newDeptId)
                    ->first();

                if (! $existingAssignment) {
                    DB::table('user_business_units')->insert([
                        'user_id' => $member->user_id,
                        'business_unit_id' => $bu->id,
                        'department_id' => $newDeptId,
                        'position_id' => $member->position_id,
                        'is_primary' => 0, // WNS remains primary
                        'is_active' => 1,
                        'is_purchasing_admin' => 1, // All SS members are purchasing admin
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        // Get the source SS department from WNS
        $sourceSSdept = DB::table('departments')
            ->where('code', 'SS')
            ->where('business_unit_id', 2)
            ->first();

        if ($sourceSSdept) {
            // Get all SS member user IDs from WNS
            $ssMemberIds = DB::table('user_business_units')
                ->where('department_id', $sourceSSdept->id)
                ->pluck('user_id');

            // Remove SS members' assignments in non-WNS BUs
            DB::table('user_business_units')
                ->whereIn('user_id', $ssMemberIds)
                ->whereIn('business_unit_id', function ($query) {
                    $query->select('id')
                        ->from('business_units')
                        ->whereNotIn('id', [1, 2]);
                })
                ->whereIn('department_id', function ($query) {
                    $query->select('id')
                        ->from('departments')
                        ->where('code', 'SS');
                })
                ->delete();

            // Revert is_purchasing_admin for WNS SS members (except those already set)
            // Note: We can't know original state, so we leave this as-is
        }

        // Remove SS departments from non-WNS BUs
        DB::table('departments')
            ->where('code', 'SS')
            ->whereNotIn('business_unit_id', [1, 2])
            ->delete();
    }
};
