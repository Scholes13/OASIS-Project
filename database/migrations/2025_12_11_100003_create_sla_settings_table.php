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
        Schema::create('sla_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_unit_id')->nullable(); // null = global settings
            $table->integer('followup_sla_hours')->default(24);
            $table->integer('completion_sla_hours')->default(72);
            $table->boolean('email_alerts_enabled')->default(true);
            $table->timestamps();
            
            // Foreign key
            $table->foreign('business_unit_id')->references('id')->on('business_units')->onDelete('cascade');
            
            // Unique constraint - one setting per business unit (or one global)
            $table->unique('business_unit_id', 'unique_sla_per_bu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sla_settings');
    }
};
