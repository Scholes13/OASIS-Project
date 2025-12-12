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
        Schema::table('departments', function (Blueprint $table) {
            $table->boolean('is_purchasing_department')->default(false)->after('is_active');
            $table->unsignedBigInteger('default_purchasing_admin_id')->nullable()->after('is_purchasing_department');
            
            // Foreign key
            $table->foreign('default_purchasing_admin_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['default_purchasing_admin_id']);
            $table->dropColumn(['is_purchasing_department', 'default_purchasing_admin_id']);
        });
    }
};
