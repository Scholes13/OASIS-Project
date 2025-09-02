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
        Schema::table('number_sequences', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['department_id']);
            
            // Modify department_id to be nullable
            $table->unsignedBigInteger('department_id')->nullable()->change();
            
            // Add back the foreign key constraint with nullable support
            $table->foreign('department_id')
                  ->references('id')
                  ->on('departments')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('number_sequences', function (Blueprint $table) {
            // Drop the nullable foreign key constraint
            $table->dropForeign(['department_id']);
            
            // Modify department_id to be non-nullable again
            $table->unsignedBigInteger('department_id')->nullable(false)->change();
            
            // Add back the original foreign key constraint
            $table->foreign('department_id')
                  ->references('id')
                  ->on('departments')
                  ->onDelete('cascade');
        });
    }
};
