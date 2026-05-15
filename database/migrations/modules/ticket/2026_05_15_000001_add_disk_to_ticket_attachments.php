<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a `disk` column to ticket_attachments so we can transition new
     * uploads to the private `local` disk while preserving the ability to
     * stream historical rows that may still live on the public disk.
     */
    public function up(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->string('disk', 32)->default('public')->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->dropColumn('disk');
        });
    }
};
