<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add composite indexes to improve query performance for:
     * - Dashboard statistics queries
     * - Approval workflow queries
     * - Activity log queries
     */
    public function up(): void
    {
        // Purchase Requests Table Indexes
        Schema::table('purchase_requests', function (Blueprint $table) {
            // Index for user's PRs filtered by status (Dashboard: My Active PRs, My Drafts)
            $table->index(['user_id', 'status'], 'idx_pr_user_status');

            // Index for business unit reports with date filtering
            $table->index(['business_unit_id', 'status', 'created_at'], 'idx_pr_bu_status_date');

            // Index for status-based queries with date sorting
            $table->index(['status', 'created_at'], 'idx_pr_status_date');
        });

        // PR Approvals Table Indexes
        Schema::table('pr_approvals', function (Blueprint $table) {
            // Index for approval queue (Dashboard: My Pending Approvals)
            $table->index(['approver_id', 'status', 'assigned_at'], 'idx_approval_queue');

            // Index for workflow step lookups
            $table->index(['purchase_request_id', 'step_order'], 'idx_approval_workflow');
        });

        // Activity Log Table Indexes (Spatie Activity Log)
        Schema::table('activity_log', function (Blueprint $table) {
            // Index for user's recent activities
            $table->index(['causer_id', 'created_at'], 'idx_activity_causer');

            // Index for entity-specific activity history
            $table->index(['subject_type', 'subject_id', 'created_at'], 'idx_activity_subject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop Purchase Requests indexes
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropIndex('idx_pr_user_status');
            $table->dropIndex('idx_pr_bu_status_date');
            $table->dropIndex('idx_pr_status_date');
        });

        // Drop PR Approvals indexes
        Schema::table('pr_approvals', function (Blueprint $table) {
            $table->dropIndex('idx_approval_queue');
            $table->dropIndex('idx_approval_workflow');
        });

        // Drop Activity Log indexes
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('idx_activity_causer');
            $table->dropIndex('idx_activity_subject');
        });
    }
};
