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
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->onDelete('cascade');
            $table->foreignId('numbering_module_id')->constrained('numbering_modules')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->integer('year')->comment('Year for sequence');
            $table->integer('month')->comment('Month for sequence');
            $table->integer('current_number')->default(0)->comment('Current sequential number');
            $table->integer('max_number')->default(999)->comment('Maximum allowed number');
            $table->json('void_numbers')->nullable()->comment('Array of voided numbers for resequencing');
            $table->timestamps();

            // Unique constraint for sequence per business unit, module, department, year, month
            $table->unique(
                ['business_unit_id', 'numbering_module_id', 'department_id', 'year', 'month'],
                'unique_sequence_per_period'
            );

            // Indexes for performance
            $table->index(
                ['business_unit_id', 'numbering_module_id', 'year', 'month'],
                'number_sequences_period_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
