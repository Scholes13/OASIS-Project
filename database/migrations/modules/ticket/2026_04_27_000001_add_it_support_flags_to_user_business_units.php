<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_business_units', function (Blueprint $table) {
            $table->boolean('is_it_support_admin')->default(false)->after('is_activity_report_access');
            $table->boolean('is_it_support_report_access')->default(false)->after('is_it_support_admin');
        });
    }

    public function down(): void
    {
        Schema::table('user_business_units', function (Blueprint $table) {
            $table->dropColumn(['is_it_support_admin', 'is_it_support_report_access']);
        });
    }
};
