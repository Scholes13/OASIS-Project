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
        Schema::create('employee_sub_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_type_id')
                ->constrained('employee_activity_types')
                ->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Unique constraint: code must be unique within activity type
            $table->unique(['activity_type_id', 'code'], 'unique_sub_activity_code_per_type');

            // Performance Indexes
            $table->index(['activity_type_id', 'is_active', 'sort_order'], 'idx_emp_sub_activities_type_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_sub_activities');
    }
};
