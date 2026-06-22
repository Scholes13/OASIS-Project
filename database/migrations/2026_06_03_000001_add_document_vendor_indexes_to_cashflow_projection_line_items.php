<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cashflow_projection_line_items', 'no_dokumen')) {
            Schema::table('cashflow_projection_line_items', function (Blueprint $table): void {
                $table->string('no_dokumen')->nullable()->after('keterangan');
            });
        }

        if (! Schema::hasColumn('cashflow_projection_line_items', 'nama_vendor')) {
            Schema::table('cashflow_projection_line_items', function (Blueprint $table): void {
                $table->string('nama_vendor')->nullable()->after('no_dokumen');
            });
        }

        Schema::table('cashflow_projection_line_items', function (Blueprint $table): void {
            $table->index(['transaction_date', 'id'], 'cfp_line_items_payment_date_id_idx');
            $table->index(['department_id', 'transaction_date'], 'cfp_line_items_department_date_idx');
            $table->index('no_dokumen', 'cfp_line_items_no_dokumen_idx');
            $table->index('nama_vendor', 'cfp_line_items_nama_vendor_idx');
        });
    }

    public function down(): void
    {
        Schema::table('cashflow_projection_line_items', function (Blueprint $table): void {
            $table->dropIndex('cfp_line_items_payment_date_id_idx');
            $table->dropIndex('cfp_line_items_department_date_idx');
            $table->dropIndex('cfp_line_items_no_dokumen_idx');
            $table->dropIndex('cfp_line_items_nama_vendor_idx');
            $table->dropColumn(['no_dokumen', 'nama_vendor']);
        });
    }
};
