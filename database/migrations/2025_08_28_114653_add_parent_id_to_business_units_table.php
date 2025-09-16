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
        Schema::table('business_units', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('business_units')->onDelete('cascade');
            $table->text('description')->nullable()->after('name');
            $table->string('address')->nullable()->after('description');
            $table->string('phone')->nullable()->after('address');
            $table->string('email')->nullable()->after('phone');
            $table->foreignId('manager_id')->nullable()->after('email')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_units', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['manager_id']);
            $table->dropColumn(['parent_id', 'description', 'address', 'phone', 'email', 'manager_id']);
        });
    }
};