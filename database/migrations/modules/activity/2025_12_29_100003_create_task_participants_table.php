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
        Schema::create('task_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_task_id')->constrained('employee_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_owner')->default(false);
            $table->timestamp('joined_at');
            $table->timestamps();

            // Unique constraint: user can only be participant once per task
            $table->unique(['employee_task_id', 'user_id'], 'unique_task_participant');

            // Performance Indexes
            // 1. Find all tasks for a user
            $table->index(['user_id', 'joined_at'], 'idx_task_participants_user');

            // 2. Find owner of a task
            $table->index(['employee_task_id', 'is_owner'], 'idx_task_participants_owner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_participants');
    }
};
