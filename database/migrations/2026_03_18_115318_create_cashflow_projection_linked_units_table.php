<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashflow_projection_linked_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_business_unit_id')
                ->constrained('business_units')
                ->cascadeOnDelete();
            $table->foreignId('linked_business_unit_id')
                ->constrained('business_units')
                ->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['host_business_unit_id', 'linked_business_unit_id'],
                'uniq_cashflow_linked_host_linked'
            );
            $table->index('host_business_unit_id', 'idx_cashflow_linked_host');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashflow_projection_linked_units');
    }
};
