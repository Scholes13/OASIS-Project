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
        Schema::create('stock_number_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('st_number')->unique()->comment('Reserved stock request number');
            $table->foreignId('business_unit_id')->constrained('business_units')->onDelete('restrict');
            $table->foreignId('department_id')->constrained('departments')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('sequence_id')->constrained('number_sequences')->onDelete('restrict');

            // Reservation Details
            $table->string('purpose')->comment('Purpose for reserving this number');
            $table->text('description')->nullable()->comment('Additional description');

            // Status
            $table->enum('status', ['reserved', 'used', 'voided'])->default('reserved');
            $table->timestamp('reserved_at')->useCurrent();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->onDelete('restrict');

            // Link to actual stock request when used
            $table->foreignId('stock_request_id')->nullable()->constrained('stock_requests')->onDelete('restrict');

            $table->timestamps();

            // Indexes for performance (shortened names to avoid MySQL limit)
            $table->index(['user_id', 'status'], 'idx_stock_num_user_status');
            $table->index(['business_unit_id', 'status'], 'idx_stock_num_bu_status');
            $table->index(['status', 'reserved_at'], 'idx_stock_num_status_date');
            $table->index(['business_unit_id', 'status', 'reserved_at'], 'idx_stock_num_bu_status_date');
            $table->index(['department_id', 'status', 'reserved_at'], 'idx_stock_num_dept_status_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_number_reservations');
    }
};
