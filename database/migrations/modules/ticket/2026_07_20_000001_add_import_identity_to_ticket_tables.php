<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('import_source')->nullable()->after('form_token');
            $table->string('import_id')->nullable()->after('import_source');
            $table->unique(['import_source', 'import_id'], 'tickets_import_identity_unique');
        });

        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->string('import_source')->nullable()->after('is_private');
            $table->string('import_id')->nullable()->after('import_source');
            $table->unique(['import_source', 'import_id'], 'ticket_comments_import_identity_unique');
        });

        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->string('import_source')->nullable()->after('uploaded_by');
            $table->string('import_id')->nullable()->after('import_source');
            $table->unique(['import_source', 'import_id'], 'ticket_attachments_import_identity_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->dropUnique('ticket_attachments_import_identity_unique');
            $table->dropColumn(['import_source', 'import_id']);
        });

        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->dropUnique('ticket_comments_import_identity_unique');
            $table->dropColumn(['import_source', 'import_id']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropUnique('tickets_import_identity_unique');
            $table->dropColumn(['import_source', 'import_id']);
        });
    }
};
