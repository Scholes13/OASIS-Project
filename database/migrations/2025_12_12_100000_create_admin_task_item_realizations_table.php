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
        Schema::create('admin_task_item_realizations', function (Blueprint $table) {
            $table->id();

            // Foreign key to admin_tasks
            $table->unsignedBigInteger('admin_task_id');

            // Polymorphic reference to PR/ST item
            $table->string('item_type', 50); // 'pr_item' or 'st_item'
            $table->unsignedBigInteger('item_id');

            // Denormalized item data for history
            $table->string('item_name', 255);
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 50);

            // Estimated prices (from original PR/ST)
            $table->decimal('estimated_unit_price', 15, 2);
            $table->decimal('estimated_total_price', 15, 2);

            // Realized prices (admin input)
            $table->decimal('realized_unit_price', 15, 2);
            $table->decimal('realized_total_price', 15, 2);

            // Savings calculations
            $table->decimal('savings_amount', 15, 2);
            $table->decimal('savings_percentage', 8, 2);

            // Supplier tracking
            $table->string('original_supplier', 255)->nullable();
            $table->string('realized_supplier', 255)->nullable();

            $table->timestamps();

            // Foreign key with cascade delete
            $table->foreign('admin_task_id')
                ->references('id')
                ->on('admin_tasks')
                ->onDelete('cascade');

            // Indexes for performance
            $table->index('admin_task_id', 'idx_atir_admin_task_id');
            $table->index(['item_type', 'item_id'], 'idx_atir_item_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_task_item_realizations');
    }
};
