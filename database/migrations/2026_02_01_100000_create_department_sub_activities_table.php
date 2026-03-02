<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_sub_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('sub_activity_id')
                ->constrained('employee_sub_activities')
                ->cascadeOnDelete();
            $table->boolean('is_default')->default(false)
                ->comment('If true, this sub-activity is pre-selected when creating tasks');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Unique constraint to prevent duplicates
            $table->unique(['department_id', 'sub_activity_id'], 'dept_sub_activity_unique');

            // Index for faster lookups
            $table->index(['department_id', 'is_default'], 'idx_dept_sub_activity_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_sub_activities');
    }
};
