<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add `parent_department_id` to `departments` for parent-child hierarchy.
 *
 * Context: PRD `docs/specs/2026-05-25-wns-restructure-prd/01-schema-changes.md`.
 * Enables sub-departments (Division) such as WNS / Sales & Marketing / BSD.
 *
 * Constraints (enforced at model level, see Department::saving()):
 * - parent.business_unit_id must equal child.business_unit_id
 * - max 1 level nesting (sub-of-sub forbidden)
 * - no self-reference
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('parent_department_id')
                ->nullable()
                ->after('business_unit_id')
                ->constrained('departments')
                ->nullOnDelete();

            $table->index('parent_department_id');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['parent_department_id']);
            $table->dropIndex(['parent_department_id']);
            $table->dropColumn('parent_department_id');
        });
    }
};
