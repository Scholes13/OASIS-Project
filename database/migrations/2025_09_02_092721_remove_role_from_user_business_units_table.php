<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_business_units', function (Blueprint $table) {
            // Remove the role column as we'll use position-based access instead
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_business_units', function (Blueprint $table) {
            // Add back the role column if needed to rollback
            $table->enum('role', ['admin', 'bod', 'hod', 'leader', 'staff'])->default('staff')->after('position_id');
        });
    }
};