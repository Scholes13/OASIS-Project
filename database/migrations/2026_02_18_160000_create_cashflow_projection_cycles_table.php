<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashflow_projection_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['business_unit_id', 'year'], 'uniq_cashflow_cycle_bu_year');
            $table->index(['business_unit_id', 'status'], 'idx_cashflow_cycle_bu_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashflow_projection_cycles');
    }
};
