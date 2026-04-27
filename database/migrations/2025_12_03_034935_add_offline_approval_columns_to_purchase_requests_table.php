<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds columns to track offline/manual approval:
     * - offline_approved_at: Timestamp when PR was marked as offline approved
     * - offline_approved_by: User ID who marked it as offline approved
     * - offline_approval_notes: Optional notes explaining why it was approved offline
     */
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->timestamp('offline_approved_at')->nullable()->after('voided_at');
            $table->foreignId('offline_approved_by')->nullable()->after('offline_approved_at')
                ->constrained('users')->nullOnDelete();
            $table->text('offline_approval_notes')->nullable()->after('offline_approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeign(['offline_approved_by']);
            $table->dropColumn(['offline_approved_at', 'offline_approved_by', 'offline_approval_notes']);
        });
    }
};
