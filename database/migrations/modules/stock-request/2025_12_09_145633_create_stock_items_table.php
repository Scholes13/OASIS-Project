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
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_request_id')->constrained('stock_requests')->onDelete('cascade');
            $table->integer('item_order')->default(1)->comment('Order of item in the request');

            // Item Details
            $table->string('item_name')->comment('Name/description of item');
            $table->integer('quantity')->comment('Quantity requested');
            $table->string('unit', 50)->comment('Unit of measurement (pcs, box, kg, etc)');
            $table->text('specifications')->nullable()->comment('Item specifications or details');
            $table->string('item_code')->nullable()->comment('Stock/SKU code if available');

            // Image Upload
            $table->string('image_path')->nullable()->comment('Path to item image');

            // Additional Info
            $table->text('notes')->nullable()->comment('Additional notes for this item');

            $table->timestamps();

            // Indexes
            $table->index('stock_request_id');
            $table->index(['stock_request_id', 'item_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
