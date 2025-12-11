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
        Schema::create('stock_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_request_id')->constrained('stock_requests')->onDelete('cascade');
            $table->foreignId('approver_id')->constrained('users')->comment('User who needs to approve');
            $table->integer('step_order')->comment('Order in the approval sequence');
            $table->string('approval_type')->default('approval')->comment('Type: approval, review, acknowledge');

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

            // QR Code for offline approval
            $table->string('qr_code_path')->nullable()->comment('Path to QR code image');

            // Metadata
            $table->json('metadata')->nullable()->comment('Additional approval metadata');
            $table->string('task_type')->default('approval')->comment('Task type for custom workflow');

            $table->timestamps();

            // Indexes
            $table->index(['stock_request_id', 'step_order']);
            $table->index(['approver_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_approvals');
    }
};
