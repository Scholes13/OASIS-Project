<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Pivot table untuk mapping activity types ke departments.
     * Memungkinkan admin mengatur activity types mana yang relevan untuk setiap department.
     */
    public function up(): void
    {
        Schema::create('department_activity_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')
                ->constrained('departments')
                ->cascadeOnDelete();
            $table->foreignId('activity_type_id')
                ->constrained('employee_activity_types')
                ->cascadeOnDelete();
            $table->boolean('is_default')->default(false)
                ->comment('Jika true, activity type ini akan auto-selected untuk department');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // Unique constraint: satu activity type hanya bisa di-assign sekali per department
            $table->unique(['department_id', 'activity_type_id'], 'unique_dept_activity_type');

            // Index untuk query by department (most common use case)
            $table->index(['department_id', 'sort_order'], 'idx_dept_activity_sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_activity_types');
    }
};
