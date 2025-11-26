<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('department')->nullable()->after('title');
            $table->string('pic_name')->nullable()->after('department');
            $table->string('pic_phone', 50)->nullable()->after('pic_name');
            $table->string('office_address', 500)->nullable()->after('pic_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['department', 'pic_name', 'pic_phone', 'office_address']);
        });
    }
};
