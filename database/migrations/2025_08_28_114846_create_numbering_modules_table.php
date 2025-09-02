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
        Schema::create('numbering_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->onDelete('cascade');
            $table->string('module_code', 10)->comment('Module code: PR, RT, etc');
            $table->string('module_name')->comment('Module full name');
            $table->string('format_pattern')->comment('Numbering format pattern');
            $table->json('config')->nullable()->comment('Additional module configuration');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Unique constraint for module code per business unit
            $table->unique(['business_unit_id', 'module_code'], 'unique_module_per_bu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('numbering_modules');
    }
};
