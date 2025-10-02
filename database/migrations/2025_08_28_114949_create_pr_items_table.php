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
        Schema::create('pr_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->onDelete('cascade');
            $table->integer('item_order')->comment('Order/sequence of item in the PR');

            // Item Details
            $table->string('item_name')->comment('Name of the item');
            $table->string('brand_name')->nullable()->comment('Brand name of the item');
            $table->foreignId('expense_department_id')->constrained('departments')->comment('Department to expense this item');
            $table->text('item_description')->nullable()->comment('Detailed description of the item');
            $table->string('supplier_name')->nullable()->comment('Preferred supplier');

            // Quantity and Pricing
            $table->decimal('quantity', 10, 2)->comment('Quantity requested');
            $table->string('unit', 20)->comment('Unit of measurement');
            $table->decimal('unit_price', 12, 2)->comment('Unit price');
            $table->string('currency', 3)->default('IDR')->comment('Currency code');
            $table->decimal('total_price', 15, 2)->comment('Total price for this item');

            $table->timestamps();

            // Indexes
            $table->index(['purchase_request_id', 'item_order']);
            $table->index('expense_department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pr_items');
    }
};
