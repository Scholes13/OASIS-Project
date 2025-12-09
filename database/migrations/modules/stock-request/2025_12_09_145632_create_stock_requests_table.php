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
        Schema::create('stock_requests', function (Blueprint $table) {
            $table->id();
            $table->string('st_number')->unique()->comment('Auto-generated Stock Request number: ST/{BU}/{YYYYMM}/{XXX}');
            $table->foreignId('business_unit_id')->constrained('business_units')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('Created by user');
            $table->foreignId('sequence_id')->constrained('number_sequences')->onDelete('cascade');

            // Stock Request Details
            $table->text('purpose')->comment('Purpose of stock request');
            $table->date('date_of_request')->comment('Date when stock was requested');
            $table->date('expected_date')->nullable()->comment('Expected delivery/fulfillment date');

            // Status and Workflow
            $table->enum('status', ['draft', 'submitted', 'in_approval', 'approved', 'rejected', 'voided'])
                ->default('draft')->comment('Current stock request status');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('voided_at')->nullable();

            // Approval Settings
            $table->json('approval_workflow')->nullable()->comment('Selected approvers and workflow');
            $table->boolean('is_sequential_approval')->default(true)->comment('Sequential or parallel approval');

            // Additional Notes
            $table->text('notes')->nullable()->comment('Additional notes or remarks');
            $table->text('rejection_notes')->nullable()->comment('Rejection reason from approver');

            // Audit fields
            $table->json('edit_history')->nullable()->comment('Track edits and approval resets');
            $table->foreignId('last_modified_by')->nullable()->constrained('users')->comment('Last user to modify');

            $table->timestamps();

            // Indexes for performance
            $table->index(['business_unit_id', 'department_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['status', 'date_of_request']);
            $table->index('date_of_request');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_requests');
    }
};
