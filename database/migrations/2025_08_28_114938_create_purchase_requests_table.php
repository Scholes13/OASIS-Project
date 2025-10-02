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
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number')->unique()->comment('Auto-generated PR number');
            $table->foreignId('business_unit_id')->constrained('business_units')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('Created by user');
            $table->foreignId('sequence_id')->constrained('number_sequences')->onDelete('cascade');

            // PR Details
            $table->text('used_for')->comment('What the PR is used for');
            $table->date('date_of_request')->comment('Date when PR was requested');

            // Status and Workflow
            $table->enum('status', ['draft', 'submitted', 'in_approval', 'approved', 'rejected', 'voided'])
                ->default('draft')->comment('Current PR status');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('voided_at')->nullable();

            // Approval Settings
            $table->json('approval_workflow')->nullable()->comment('Selected approvers and workflow');
            $table->boolean('is_sequential_approval')->default(true)->comment('Sequential or parallel approval');

            // Totals
            $table->decimal('total_amount', 15, 2)->default(0)->comment('Total PR amount');
            $table->string('currency', 3)->default('IDR')->comment('Currency code');

            // Audit fields
            $table->json('edit_history')->nullable()->comment('Track edits and approval resets');
            $table->foreignId('last_modified_by')->nullable()->constrained('users')->comment('Last user to modify');

            $table->timestamps();

            // Indexes
            $table->index(['business_unit_id', 'department_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('date_of_request');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
