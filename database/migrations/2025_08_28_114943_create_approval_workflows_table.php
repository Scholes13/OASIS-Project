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
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Workflow name');
            $table->text('description')->nullable()->comment('Workflow description');
            $table->foreignId('business_unit_id')->constrained('business_units')->onDelete('cascade');
            $table->string('module_type')->comment('Module this workflow applies to (e.g., purchase_request)');

            // Workflow Configuration
            $table->json('approval_steps')->comment('Array of approval steps with approver roles/users');
            $table->boolean('is_sequential')->default(true)->comment('Sequential or parallel approval');
            $table->boolean('is_default')->default(false)->comment('Is this the default workflow');
            $table->boolean('is_active')->default(true);

            // Conditions for auto-assignment
            $table->json('conditions')->nullable()->comment('Conditions for when this workflow applies');

            $table->timestamps();

            // Indexes
            $table->index(['business_unit_id', 'module_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_workflows');
    }
};
