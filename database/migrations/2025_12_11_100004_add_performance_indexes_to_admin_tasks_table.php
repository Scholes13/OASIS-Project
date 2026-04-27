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
        Schema::table('admin_tasks', function (Blueprint $table) {
            // Standard 5-index pattern for admin_tasks module

            // 1. Admin ownership queries (assigned admin's tasks by status)
            $table->index(['assigned_admin_id', 'status'], 'idx_admin_task_admin_status');

            // 2. Business unit reports (BU tasks by status and date)
            $table->index(['business_unit_id', 'status', 'entered_at'], 'idx_admin_task_bu_status_date');

            // 3. Department reports (department tasks by status and date)
            $table->index(['department_id', 'status', 'entered_at'], 'idx_admin_task_dept_status_date');

            // 4. Multi-BU admin context (admin's tasks in specific BU)
            $table->index(['assigned_admin_id', 'business_unit_id', 'status'], 'idx_admin_task_admin_bu_status');

            // 5. Status timeline (all tasks by status and date)
            $table->index(['status', 'entered_at'], 'idx_admin_task_status_date');

            // Additional indexes for specific use cases

            // 6. Unassigned tasks lookup (for claiming)
            $table->index(['business_unit_id', 'status', 'assigned_admin_id'], 'idx_admin_task_unassigned');

            // 7. SLA monitoring (tasks by timestamps for violation detection)
            $table->index(['status', 'entered_at', 'started_at'], 'idx_admin_task_sla_followup');
            $table->index(['status', 'started_at', 'completed_at'], 'idx_admin_task_sla_completion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_tasks', function (Blueprint $table) {
            // Drop standard indexes
            $table->dropIndex('idx_admin_task_admin_status');
            $table->dropIndex('idx_admin_task_bu_status_date');
            $table->dropIndex('idx_admin_task_dept_status_date');
            $table->dropIndex('idx_admin_task_admin_bu_status');
            $table->dropIndex('idx_admin_task_status_date');

            // Drop additional indexes
            $table->dropIndex('idx_admin_task_unassigned');
            $table->dropIndex('idx_admin_task_sla_followup');
            $table->dropIndex('idx_admin_task_sla_completion');
        });
    }
};
