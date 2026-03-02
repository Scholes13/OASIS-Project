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
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->string('name')->comment('Position title/name');
            $table->string('code', 50)->comment('Position code');
            $table->enum('level', ['c_level', 'hod', 'leader', 'staff'])->comment('Position hierarchy level');
            $table->integer('hierarchy_level')->default(1)->comment('Numeric hierarchy level for sorting');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique constraint for position code per department
            $table->unique(['department_id', 'code'], 'unique_position_per_dept');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
