<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashflow_projection_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type', 50);
            $table->unsignedBigInteger('auditable_id');
            $table->string('action', 20);
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_user_name', 255)->nullable();
            $table->foreignId('actor_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('actor_department_label', 255)->nullable();
            $table->string('summary', 255)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id'], 'idx_cashflow_audit_target');
            $table->index('actor_user_id', 'idx_cashflow_audit_actor');
            $table->index(['business_unit_id', 'department_id'], 'idx_cashflow_audit_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashflow_projection_audit_logs');
    }
};
