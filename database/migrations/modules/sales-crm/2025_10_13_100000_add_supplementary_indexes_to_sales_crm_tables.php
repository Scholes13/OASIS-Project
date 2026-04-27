<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Performance optimization indexes for Sales CRM module.
     * Following best practices from v.2.2 optimization.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // Composite index for filtered pagination (common query pattern)
            // Covers: WHERE business_unit_id AND status ORDER BY activity_date DESC
            $table->index(
                ['business_unit_id', 'status', 'activity_date', 'created_at'],
                'idx_activities_bu_status_dates'
            );

            // Index for user-scoped queries (sales person view)
            $table->index(
                ['user_id', 'business_unit_id', 'status', 'activity_date'],
                'idx_activities_user_bu_filters'
            );

            // Full-text search optimization for title and description
            // Note: MySQL 5.7+ required for fulltext on InnoDB
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE activities ADD FULLTEXT INDEX idx_activities_search (title, description)');
            }
        });

        Schema::table('contacts', function (Blueprint $table) {
            // Composite index for filtered pagination
            $table->index(
                ['business_unit_id', 'assigned_to', 'status', 'created_at'],
                'idx_contacts_bu_assigned_filters'
            );

            // Index for category filtering with pagination
            $table->index(
                ['business_unit_id', 'category', 'status', 'created_at'],
                'idx_contacts_bu_category_filters'
            );

            // Full-text search optimization for common search fields
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE contacts ADD FULLTEXT INDEX idx_contacts_search (name, company, email, phone, mobile, position)');
            }

            // Index for company duplicate detection (case-insensitive lookups)
            $table->index(['business_unit_id', 'company'], 'idx_contacts_bu_company');
        });

        // Note: contact_sources already has idx_contact_sources_contact_type from create migration
        // No additional indexes needed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('idx_activities_bu_status_dates');
            $table->dropIndex('idx_activities_user_bu_filters');

            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE activities DROP INDEX idx_activities_search');
            }
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex('idx_contacts_bu_assigned_filters');
            $table->dropIndex('idx_contacts_bu_category_filters');
            $table->dropIndex('idx_contacts_bu_company');

            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE contacts DROP INDEX idx_contacts_search');
            }
        });

        // Note: contact_sources indexes managed by create migration
    }
};
