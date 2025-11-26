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
        Schema::create('company_visit_history', function (Blueprint $table) {
            $table->id();
            
            // Business Unit Scope
            $table->foreignId('business_unit_id')->constrained()->cascadeOnDelete();
            
            // Company Information
            $table->string('company_name');
            $table->string('department')->nullable();
            
            // Last Visit Information
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('last_visit_at');
            
            // Visit Statistics
            $table->integer('total_visits')->default(1);
            
            $table->timestamps();
            
            // Performance Indexes
            // 1. Company & department lookup
            $table->index(['company_name', 'department'], 'idx_cvh_company_dept');
            
            // 2. Business unit analytics (last visit timeline)
            $table->index(['business_unit_id', 'last_visit_at'], 'idx_cvh_bu_last_visit');
            
            // 3. Unique constraint: one record per BU + company + department
            $table->unique(['business_unit_id', 'company_name', 'department'], 'idx_cvh_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_visit_history');
    }
};
