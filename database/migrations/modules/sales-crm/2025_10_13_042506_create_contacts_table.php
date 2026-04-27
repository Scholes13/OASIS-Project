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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('business_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            // Auto-generated Code
            $table->string('code')->unique()->comment('Format: CONT-WNS-25-00001');

            // Personal Information
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->date('birth_date')->nullable();

            // Professional Information
            $table->string('company')->nullable();
            $table->string('department')->nullable();
            $table->string('position')->nullable()->comment('Jabatan');

            // Social Media (JSON: linkedin, instagram, facebook, etc)
            $table->json('social_media')->nullable();

            // Status & Category
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->enum('category', ['lead', 'prospect', 'customer', 'partner'])->default('lead');

            // Additional Data
            $table->text('address')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Performance Indexes (5-index standard from Task 1.1)
            // 1. Business unit contacts (multi-BU scope)
            $table->index(['business_unit_id', 'status', 'created_at'], 'idx_contacts_bu_status_created');

            // 2. Sales person's assigned contacts
            $table->index(['assigned_to', 'status'], 'idx_contacts_assigned_status');

            // 3. Company lookup (quick search)
            $table->index(['company', 'status'], 'idx_contacts_company_status');

            // 4. Category filtering (lead/prospect/customer/partner)
            $table->index(['category', 'status'], 'idx_contacts_category_status');

            // 5. Code is already unique index by default
            // No need to add separate index for 'code'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
