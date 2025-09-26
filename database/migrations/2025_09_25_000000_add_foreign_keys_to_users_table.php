<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('primary_department_id')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();

            $table->foreign('primary_position_id')
                ->references('id')
                ->on('positions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['primary_department_id']);
            $table->dropForeign(['primary_position_id']);
        });
    }
};
