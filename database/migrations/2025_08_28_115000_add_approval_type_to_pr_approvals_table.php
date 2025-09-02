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
        Schema::table('pr_approvals', function (Blueprint $table) {
            $table->string('approval_type')->nullable()->after('step_order')->comment('Type of approval: department_head, finance_manager, general_manager, director, special_category, etc.');
            $table->index(['approval_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pr_approvals', function (Blueprint $table) {
            $table->dropIndex(['approval_type']);
            $table->dropColumn('approval_type');
        });
    }
};