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
        Schema::create('pr_number_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number')->unique(); // PR.WNS-BAS/202509/003
            $table->foreignId('business_unit_id')->constrained();
            $table->foreignId('department_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('sequence_id')->constrained('number_sequences');
            $table->string('purpose', 500); // Keperluan
            $table->text('description'); // Deskripsi
            $table->enum('status', ['reserved', 'used', 'voided'])->default('reserved');
            $table->timestamp('reserved_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 500)->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users');
            $table->foreignId('purchase_request_id')->nullable()->constrained('purchase_requests');
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['business_unit_id', 'status']);
            $table->index(['status', 'reserved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pr_number_reservations');
    }
};
