<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add supplementary indexes to complete the performance optimization
     * and establish standard index patterns for future modules.
     *
     * These indexes address:
     * 1. Department-based reporting (missing from initial optimization)
     * 2. Multi-business-unit user context filtering
     * 3. Number reservation management
     *
     * Future modules should follow this same pattern:
     * - user_id + status (ownership queries)
     * - business_unit_id + status + created_at (BU reports)
     * - department_id + status + created_at (dept reports)
     * - user_id + business_unit_id + status (multi-BU context)
     * - status + created_at (timeline queries)
     */
    public function up(): void
    {
        // Purchase Requests - Department & Multi-BU Context
        Schema::table('purchase_requests', function (Blueprint $table) {
            // Department-based reporting and filtering
            // Example: Show all PRs from IT department that are pending
            $table->index(['department_id', 'status', 'created_at'], 'idx_pr_dept_status_date');

            // Multi-BU user context filtering
            // Example: User's PRs in specific business unit (for users assigned to multiple BUs)
            $table->index(['user_id', 'business_unit_id', 'status'], 'idx_pr_user_bu_status');
        });

        // PR Number Reservations - Complete Index Coverage
        Schema::table('pr_number_reservations', function (Blueprint $table) {
            // User's number reservations by status
            // Example: Show my reserved/used/voided numbers
            $table->index(['user_id', 'status'], 'idx_pr_num_user_status');

            // Business unit number management
            // Example: All reserved numbers for WNS, sorted by date
            $table->index(['business_unit_id', 'status', 'reserved_at'], 'idx_pr_num_bu_status_date');

            // Department number reservations
            // Example: IT department's number usage tracking
            $table->index(['department_id', 'status', 'reserved_at'], 'idx_pr_num_dept_status_date');

            // Number status timeline
            // Example: All voided numbers in date order
            $table->index(['status', 'reserved_at'], 'idx_pr_num_status_date');
        });

        // PR Items - Query Optimization
        Schema::table('pr_items', function (Blueprint $table) {
            // Items by PR (already has FK, but explicit index for sorting)
            // Example: Get all items for a PR ordered by sequence
            $table->index(['purchase_request_id', 'id'], 'idx_pr_items_pr_order');

            // Department expense tracking
            // Example: Total expenses by department across all PRs
            $table->index(['expense_department_id', 'purchase_request_id'], 'idx_pr_items_dept_expense');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop Purchase Requests supplementary indexes
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropIndex('idx_pr_dept_status_date');
            $table->dropIndex('idx_pr_user_bu_status');
        });

        // Drop PR Number Reservations indexes
        Schema::table('pr_number_reservations', function (Blueprint $table) {
            $table->dropIndex('idx_pr_num_user_status');
            $table->dropIndex('idx_pr_num_bu_status_date');
            $table->dropIndex('idx_pr_num_dept_status_date');
            $table->dropIndex('idx_pr_num_status_date');
        });

        // Drop PR Items indexes
        Schema::table('pr_items', function (Blueprint $table) {
            $table->dropIndex('idx_pr_items_pr_order');
            $table->dropIndex('idx_pr_items_dept_expense');
        });
    }
};
