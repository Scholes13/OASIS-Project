<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashflow_projection_finance_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('cashflow_projection_cycles')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->decimal('cash_on_hand', 18, 2)->default(0);
            $table->decimal('receivable_estimate', 18, 2)->default(0);
            $table->decimal('upcoming_event_revenue_estimate', 18, 2)->default(0);
            $table->decimal('capital_injection_estimate', 18, 2)->default(0);
            $table->decimal('other_income', 18, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['cycle_id', 'month'], 'uniq_cashflow_finance_cycle_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashflow_projection_finance_inputs');
    }
};
