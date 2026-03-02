<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'c_level' to positions.level enum and fix hierarchy data.
     *
     * This enables per-BU role differentiation:
     * - A user can be HOD in BU-A but C-Level (Director) in BU-B
     * - The system reads position from user_business_units.position_id per BU context
     *
     * Hierarchy after migration:
     * c_level (0) → executive access (BOD/Director)
     * hod (1) → department_head access
     * leader (2) → team_leader access
     * staff (3) → staff access
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        // Step 1: Add 'c_level' to the level enum
        // SQLite doesn't support ENUM or ALTER COLUMN — level is stored as TEXT
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE positions MODIFY COLUMN level ENUM('c_level', 'hod', 'leader', 'staff') NOT NULL");
        }
        // For SQLite (tests): column is TEXT, any string value is accepted

        // Step 2: Update existing executive positions to use c_level level
        // These are positions that already have access_level='executive' but level='hod'
        DB::table('positions')
            ->where('access_level', 'executive')
            ->update(['level' => 'c_level']);

        // Step 3: Standardize hierarchy_level values for consistency
        // c_level = 0, hod = 1, leader = 2, staff = 3
        DB::table('positions')
            ->where('level', 'c_level')
            ->update(['hierarchy_level' => 0]);

        DB::table('positions')
            ->where('level', 'hod')
            ->where('access_level', 'department_head')
            ->update(['hierarchy_level' => 1]);

        DB::table('positions')
            ->where('level', 'leader')
            ->update(['hierarchy_level' => 2]);

        DB::table('positions')
            ->where('level', 'staff')
            ->update(['hierarchy_level' => 3]);

        // Step 4: Fix Bagus (user_id=2) position
        // Currently: Staff of Managing Director (ID:6, level=staff)
        // Should be: Top Management (ID:110, level=c_level, access_level=executive)
        $topManagementPosition = DB::table('positions')
            ->where('id', 110)
            ->first();

        if ($topManagementPosition) {
            DB::table('user_business_units')
                ->where('user_id', 2)
                ->where('business_unit_id', 1)
                ->update([
                    'position_id' => 110,
                    'updated_at' => now(),
                ]);

            DB::table('users')
                ->where('id', 2)
                ->update([
                    'primary_position_id' => 110,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert Bagus position
        DB::table('user_business_units')
            ->where('user_id', 2)
            ->where('business_unit_id', 1)
            ->update([
                'position_id' => 6,
                'updated_at' => now(),
            ]);

        DB::table('users')
            ->where('id', 2)
            ->update([
                'primary_position_id' => 6,
                'updated_at' => now(),
            ]);

        // Revert c_level positions back to hod
        DB::table('positions')
            ->where('level', 'c_level')
            ->update(['level' => 'hod']);

        // Remove c_level from enum
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE positions MODIFY COLUMN level ENUM('hod', 'leader', 'staff') NOT NULL");
        }
    }
};
