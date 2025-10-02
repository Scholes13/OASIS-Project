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
        Schema::create('pr_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->onDelete('cascade');
            $table->foreignId('approver_id')->constrained('users')->comment('User who needs to approve');
            $table->integer('step_order')->comment('Order in the approval sequence');

            // Approval Status
            $table->enum('status', ['pending', 'approved', 'rejected', 'skipped'])->default('pending');
            $table->text('notes')->nullable()->comment('Approval/rejection notes');

            // Timestamps
            $table->timestamp('assigned_at')->useCurrent()->comment('When approval was assigned');
            $table->timestamp('responded_at')->nullable()->comment('When approver responded');
            $table->timestamp('due_date')->nullable()->comment('Due date for approval');

            // Email tracking
            $table->boolean('email_sent')->default(false)->comment('Email notification sent');
            $table->timestamp('email_sent_at')->nullable();

            // Metadata
            $table->json('metadata')->nullable()->comment('Additional approval metadata');

            $table->timestamps();

            // Indexes
            $table->index(['purchase_request_id', 'step_order']);
            $table->index(['approver_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pr_approvals');
    }
};
