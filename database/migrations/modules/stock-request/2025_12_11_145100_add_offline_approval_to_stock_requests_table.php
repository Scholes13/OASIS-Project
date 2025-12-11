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
        Schema::table('stock_requests', function (Blueprint $table) {
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
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->dropForeign(['offline_approved_by']);
            $table->dropColumn(['offline_approved_at', 'offline_approved_by', 'offline_approval_notes']);
        });
    }
};
