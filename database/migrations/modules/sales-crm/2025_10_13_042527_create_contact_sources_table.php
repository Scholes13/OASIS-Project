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
        Schema::create('contact_sources', function (Blueprint $table) {
            $table->id();

            // Contact FK
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();

            // Source Type
            $table->enum('source_type', [
                'activity',  // From sales activity (call/visit/blitz)
                'manual',    // Direct input by user
                'import',    // Bulk import (future feature)
                'referral',  // Referred by someone
                'website',   // From website form
                'event',     // From event/exhibition
            ]);

            // Activity Source (if source_type = 'activity')
            $table->foreignId('source_activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->string('activity_type')->nullable()->comment('Denormalized for quick lookup: call, visit, blitz, etc');

            // Manual Entry Source (if source_type = 'manual')
            $table->foreignId('source_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Additional Context
            $table->string('source_notes')->nullable()->comment('Additional context: Met at event, Referred by John, etc');
            $table->date('source_date')->comment('When contact was acquired');

            $table->timestamps();

            // Performance Indexes
            // 1. Contact source lookup
            $table->index(['contact_id', 'source_type'], 'idx_contact_sources_contact_type');

            // 2. Activity source tracking
            $table->index(['source_activity_id'], 'idx_contact_sources_activity');

            // 3. Manual entry tracking (who added)
            $table->index(['source_user_id'], 'idx_contact_sources_user');

            // 4. Source type reports by date
            $table->index(['source_type', 'source_date'], 'idx_contact_sources_type_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_sources');
    }
};
