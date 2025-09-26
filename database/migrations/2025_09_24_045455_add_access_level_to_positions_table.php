<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->enum('access_level', [
                'executive',
                'general_manager',
                'department_head',
                'team_leader',
                'staff',
            ])->default('staff')->after('level');
        });

        // Backfill existing records using legacy level values
        DB::table('positions')->where('level', 'hod')->update(['access_level' => 'department_head']);
        DB::table('positions')->where('level', 'leader')->update(['access_level' => 'team_leader']);
        DB::table('positions')->where('level', 'staff')->update(['access_level' => 'staff']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn('access_level');
        });
    }
};
