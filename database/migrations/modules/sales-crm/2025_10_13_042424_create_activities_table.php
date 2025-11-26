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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('business_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            
            // Activity Details
            $table->date('activity_date');
            $table->enum('activity_type', ['call', 'visit', 'meeting', 'blitz', 'follow_up', 'other']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            
            // Time Tracking
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('duration_minutes')->nullable()->comment('Auto-calculated from start_time and end_time');
            
            // Results
            $table->enum('result', ['success', 'follow_up_needed', 'no_answer', 'rejected', 'other'])->nullable();
            $table->text('notes')->nullable();
            
            // Status
            $table->enum('status', ['planned', 'completed', 'cancelled'])->default('planned');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Performance Indexes (5-index standard from Task 1.1)
            // 1. Sales person's activities filtered by status and date
            $table->index(['user_id', 'status', 'activity_date'], 'idx_activities_user_status_date');
            
            // 2. Business unit activities (reports & analytics)
            $table->index(['business_unit_id', 'status', 'activity_date'], 'idx_activities_bu_status_date');
            
            // 3. Contact activity history
            $table->index(['contact_id', 'activity_date'], 'idx_activities_contact_date');
            
            // 4. Activity type filtering
            $table->index(['activity_type', 'status'], 'idx_activities_type_status');
            
            // 5. Date-based reports (timeline queries)
            $table->index(['activity_date', 'status'], 'idx_activities_date_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
