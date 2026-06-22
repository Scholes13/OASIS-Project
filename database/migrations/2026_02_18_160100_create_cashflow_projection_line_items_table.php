<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashflow_projection_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('cashflow_projection_cycles')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->enum('flow_type', ['in', 'out']);
            $table->string('action_code', 100);
            $table->date('transaction_date');
            $table->date('due_date')->nullable();
            $table->boolean('is_estimated_date')->default(false);
            $table->decimal('amount', 18, 2);
            $table->text('description');
            $table->string('keterangan')->nullable();
            $table->string('no_dokumen')->nullable();
            $table->string('nama_vendor')->nullable();
            $table->text('notes')->nullable();
            $table->enum('source_type', ['manual', 'api'])->default('manual');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cycle_id', 'department_id'], 'idx_cashflow_line_cycle_dept');
            $table->index(['cycle_id', 'transaction_date'], 'idx_cashflow_line_cycle_date');
            $table->index('action_code', 'idx_cashflow_line_action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashflow_projection_line_items');
    }
};
