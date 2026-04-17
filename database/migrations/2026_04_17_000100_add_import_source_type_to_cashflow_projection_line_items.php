<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cashflow_projection_line_items') || ! Schema::hasColumn('cashflow_projection_line_items', 'source_type')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->rebuildSqliteTable(['manual', 'api', 'import']);

            return;
        }

        DB::statement(
            "ALTER TABLE cashflow_projection_line_items MODIFY source_type ENUM('manual', 'api', 'import') NOT NULL DEFAULT 'manual'"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('cashflow_projection_line_items') || ! Schema::hasColumn('cashflow_projection_line_items', 'source_type')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->rebuildSqliteTable(['manual', 'api']);

            return;
        }

        DB::statement(
            "ALTER TABLE cashflow_projection_line_items MODIFY source_type ENUM('manual', 'api') NOT NULL DEFAULT 'manual'"
        );
    }

    /**
     * @param  array<int, string>  $sourceTypes
     */
    private function rebuildSqliteTable(array $sourceTypes): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('ALTER TABLE cashflow_projection_line_items RENAME TO cashflow_projection_line_items_old');

        Schema::create('cashflow_projection_line_items', function (Blueprint $table) use ($sourceTypes) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('cashflow_projection_cycles')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->enum('flow_type', ['in', 'out']);
            $table->string('action_code', 100);
            $table->date('transaction_date');
            $table->date('due_date')->nullable();
            $table->boolean('is_estimated_date')->default(false);
            $table->decimal('amount', 18, 2);
            $table->string('description', 255);
            $table->text('notes')->nullable();
            $table->enum('source_type', $sourceTypes)->default('manual');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cycle_id', 'department_id']);
            $table->index(['cycle_id', 'transaction_date']);
            $table->index('action_code');
        });

        DB::statement(
            'INSERT INTO cashflow_projection_line_items (id, cycle_id, department_id, flow_type, action_code, transaction_date, due_date, is_estimated_date, amount, description, notes, source_type, created_by, updated_by, created_at, updated_at)
             SELECT id, cycle_id, department_id, flow_type, action_code, transaction_date, due_date, is_estimated_date, amount, description, notes, source_type, created_by, updated_by, created_at, updated_at
             FROM cashflow_projection_line_items_old'
        );

        Schema::drop('cashflow_projection_line_items_old');
        DB::statement('PRAGMA foreign_keys = ON');
    }
};
