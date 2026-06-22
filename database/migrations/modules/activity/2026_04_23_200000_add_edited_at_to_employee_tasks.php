<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_tasks', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('completed_at');
            $table->foreignId('edited_by')->nullable()->after('edited_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_tasks', function (Blueprint $table) {
            $table->dropForeign(['edited_by']);
            $table->dropColumn(['edited_at', 'edited_by']);
        });
    }
};
