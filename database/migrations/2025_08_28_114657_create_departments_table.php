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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->onDelete('cascade');
            $table->string('code', 10)->comment('Department code: GA, IT, HR, etc');
            $table->string('name')->comment('Department full name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Unique constraint for department code per business unit
            $table->unique(['business_unit_id', 'code'], 'unique_dept_per_bu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
