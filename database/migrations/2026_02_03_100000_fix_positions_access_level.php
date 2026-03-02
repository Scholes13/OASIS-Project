<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix access_level values based on position level.
     *
     * Mapping:
     * - level = 'hod' → access_level = 'department_head'
     * - level = 'leader' → access_level = 'team_leader'
     * - level = 'staff' → access_level = 'staff'
     */
    public function up(): void
    {
        // Fix HOD positions - should be department_head
        DB::table('positions')
            ->where('level', 'hod')
            ->where('access_level', 'staff')
            ->update(['access_level' => 'department_head']);

        // Fix Leader positions - should be team_leader
        DB::table('positions')
            ->where('level', 'leader')
            ->where('access_level', 'staff')
            ->update(['access_level' => 'team_leader']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to staff (not recommended)
        DB::table('positions')
            ->where('level', 'hod')
            ->where('access_level', 'department_head')
            ->update(['access_level' => 'staff']);

        DB::table('positions')
            ->where('level', 'leader')
            ->where('access_level', 'team_leader')
            ->update(['access_level' => 'staff']);
    }
};
