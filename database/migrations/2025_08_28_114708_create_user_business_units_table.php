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
        Schema::create('user_business_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('business_unit_id')->constrained('business_units')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->foreignId('position_id')->constrained('positions')->onDelete('cascade');
            $table->enum('role', ['admin', 'bod', 'hod', 'leader', 'staff'])->default('staff')->comment('Role in this business unit');
            $table->boolean('is_primary')->default(false)->comment('Is this the user primary business unit');
            $table->boolean('is_active')->default(true);
            $table->json('permissions')->nullable()->comment('Specific permissions for this business unit');
            $table->timestamps();

            // Unique constraint for user-business unit-department combination
            $table->unique(['user_id', 'business_unit_id', 'department_id'], 'unique_user_bu_dept');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_business_units');
    }
};
