<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->boolean('is_ga_stock_review_department')
                ->default(false)
                ->after('is_purchasing_department')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex(['is_ga_stock_review_department']);
            $table->dropColumn('is_ga_stock_review_department');
        });
    }
};
