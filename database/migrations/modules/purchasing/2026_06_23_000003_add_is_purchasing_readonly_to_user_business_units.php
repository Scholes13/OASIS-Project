<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_business_units', function (Blueprint $table) {
            $table->boolean('is_purchasing_readonly')->default(false)->after('is_purchasing_admin');
        });
    }

    public function down(): void
    {
        Schema::table('user_business_units', function (Blueprint $table) {
            $table->dropColumn('is_purchasing_readonly');
        });
    }
};
