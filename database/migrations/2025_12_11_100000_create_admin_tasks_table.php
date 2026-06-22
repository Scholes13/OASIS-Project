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
        Schema::create('admin_tasks', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship to PR/ST
            $table->string('taskable_type'); // App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest or StockRequest
            $table->unsignedBigInteger('taskable_id');

            // Business context
            $table->unsignedBigInteger('business_unit_id');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('assigned_admin_id')->nullable();

            // Status tracking
            $table->enum('status', ['pending_followup', 'in_progress', 'done'])->default('pending_followup');

            // Timestamps
            $table->timestamp('entered_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Price tracking
            $table->decimal('estimated_total_price', 15, 2);
            $table->decimal('realized_total_price', 15, 2)->nullable();
            $table->decimal('savings_amount', 15, 2)->nullable();
            $table->decimal('savings_percentage', 5, 2)->nullable();

            // Time metrics (in minutes)
            $table->integer('followup_time_minutes')->nullable();
            $table->integer('completion_time_minutes')->nullable();

            // Additional info
            $table->text('notes')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('business_unit_id')->references('id')->on('business_units')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('assigned_admin_id')->references('id')->on('users')->onDelete('set null');

            // Polymorphic index
            $table->index(['taskable_type', 'taskable_id'], 'idx_admin_task_taskable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_tasks');
    }
};
