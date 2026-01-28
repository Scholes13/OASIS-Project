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
            // Add task_date column - the actual date when the task was performed
            // This is different from created_at (when record was created) and due_date (deadline)
            $table->date('task_date')->after('task_title')->default(now()->toDateString());
            
            // Add index for task_date queries
            $table->index(['task_date', 'status'], 'idx_emp_tasks_task_date_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_tasks', function (Blueprint $table) {
            $table->dropIndex('idx_emp_tasks_task_date_status');
            $table->dropColumn('task_date');
        });
    }
};
