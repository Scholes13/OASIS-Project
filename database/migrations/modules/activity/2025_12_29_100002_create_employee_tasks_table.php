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
        Schema::create('employee_tasks', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('business_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('activity_type_id')->constrained('employee_activity_types')->restrictOnDelete();
            $table->foreignId('sub_activity_id')->nullable()->constrained('employee_sub_activities')->nullOnDelete();

            // Task Details
            $table->string('task_title', 255);
            $table->date('due_date');
            $table->text('notes')->nullable();

            // Status & Timestamps
            $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('duration_minutes')->nullable();

            // Cancellation
            $table->string('cancellation_reason', 255)->nullable();

            $table->timestamps();

            // Performance Indexes
            // 1. Business unit + status + date (for BU-level reports)
            $table->index(['business_unit_id', 'status', 'created_at'], 'idx_emp_tasks_bu_status_date');

            // 2. Department + status + date (for department-level reports)
            $table->index(['department_id', 'status', 'created_at'], 'idx_emp_tasks_dept_status_date');

            // 3. Creator + status (for personal task list)
            $table->index(['created_by', 'status'], 'idx_emp_tasks_creator_status');

            // 4. Activity type filtering
            $table->index(['activity_type_id', 'status'], 'idx_emp_tasks_type_status');

            // 5. Due date + status (for overdue detection)
            $table->index(['due_date', 'status'], 'idx_emp_tasks_due_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_tasks');
    }
};
