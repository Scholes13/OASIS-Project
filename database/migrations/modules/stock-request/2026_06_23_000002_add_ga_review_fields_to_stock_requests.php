<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_requests MODIFY status ENUM('draft', 'submitted', 'in_approval', 'approved', 'ga_review', 'ga_rejected', 'ready_for_purchasing', 'rejected', 'voided') DEFAULT 'draft'");
        }

        Schema::table('stock_requests', function (Blueprint $table) {
            $table->timestamp('ga_review_started_at')->nullable()->after('rejected_at');
            $table->timestamp('ga_reviewed_at')->nullable()->after('ga_review_started_at');
            $table->foreignId('ga_reviewed_by')->nullable()->after('ga_reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('ga_review_notes')->nullable()->after('ga_reviewed_by');
            $table->text('ga_rejected_reason')->nullable()->after('ga_review_notes');
            $table->index(['business_unit_id', 'status', 'ga_reviewed_by']);
        });

        Schema::table('stock_items', function (Blueprint $table) {
            $table->enum('ga_review_result', ['pending_review', 'warehouse_stock', 'need_procurement'])
                ->default('pending_review')
                ->after('image_path');
            $table->text('ga_review_note')->nullable()->after('ga_review_result');
            $table->unsignedInteger('warehouse_available_qty')->nullable()->after('ga_review_note');
            $table->index(['stock_request_id', 'ga_review_result']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropIndex(['stock_request_id', 'ga_review_result']);
            $table->dropColumn(['ga_review_result', 'ga_review_note', 'warehouse_available_qty']);
        });

        Schema::table('stock_requests', function (Blueprint $table) {
            $table->dropIndex(['business_unit_id', 'status', 'ga_reviewed_by']);
            $table->dropConstrainedForeignId('ga_reviewed_by');
            $table->dropColumn([
                'ga_review_started_at',
                'ga_reviewed_at',
                'ga_review_notes',
                'ga_rejected_reason',
            ]);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_requests MODIFY status ENUM('draft', 'submitted', 'in_approval', 'approved', 'rejected', 'voided') DEFAULT 'draft'");
        }
    }
};
