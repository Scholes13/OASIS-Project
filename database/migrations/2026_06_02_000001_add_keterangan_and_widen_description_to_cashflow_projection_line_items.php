<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashflow_projection_line_items', function (Blueprint $table) {
            $table->text('description')->change();
        });

        if (! Schema::hasColumn('cashflow_projection_line_items', 'keterangan')) {
            Schema::table('cashflow_projection_line_items', function (Blueprint $table) {
                $table->string('keterangan')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cashflow_projection_line_items', 'keterangan')) {
            Schema::table('cashflow_projection_line_items', function (Blueprint $table) {
                $table->dropColumn('keterangan');
            });
        }

        Schema::table('cashflow_projection_line_items', function (Blueprint $table) {
            $table->string('description', 255)->change();
        });
    }
};
