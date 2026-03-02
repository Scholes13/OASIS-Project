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
        Schema::table('employee_tasks', function (Blueprint $table) {
            $table->text('task_description')->nullable()->after('task_title');
        });

        // Copy existing notes to task_description
        DB::statement('UPDATE employee_tasks SET task_description = notes WHERE notes IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_tasks', function (Blueprint $table) {
            $table->dropColumn('task_description');
        });
    }
};
