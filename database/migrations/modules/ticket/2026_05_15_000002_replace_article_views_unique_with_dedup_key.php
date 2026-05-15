<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the (article_id, visitor_fingerprint, user_id) unique
     * constraint with a synthetic `dedup_key` that is always non-null.
     *
     * Most database engines treat each NULL as distinct in a multi-column
     * unique index, so anonymous viewers (user_id NULL) bypass the original
     * constraint entirely.  This migration rebuilds the dedup story around
     * a string key the application controls — `user:{id}` for authenticated
     * viewers, `anon:{fingerprint}` for anonymous ones — so duplicates
     * actually fail at the database layer.
     */
    public function up(): void
    {
        Schema::table('ticket_article_views', function (Blueprint $table) {
            $table->string('dedup_key', 128)->nullable()->after('user_id');
        });

        // Backfill existing rows so the new index is safe to add.
        // Use the ANSI `||` concatenation operator so the same statement
        // runs on SQLite (test/local), MySQL with PIPES_AS_CONCAT, and
        // Postgres.  We avoid CONCAT() because SQLite does not ship it.
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("
                UPDATE ticket_article_views
                   SET dedup_key = CASE
                       WHEN user_id IS NOT NULL THEN CONCAT('user:', user_id)
                       ELSE CONCAT('anon:', COALESCE(visitor_fingerprint, ''))
                   END
                 WHERE dedup_key IS NULL
            ");
        } else {
            DB::statement("
                UPDATE ticket_article_views
                   SET dedup_key = CASE
                       WHEN user_id IS NOT NULL THEN ('user:' || user_id)
                       ELSE ('anon:' || COALESCE(visitor_fingerprint, ''))
                   END
                 WHERE dedup_key IS NULL
            ");
        }

        Schema::table('ticket_article_views', function (Blueprint $table) {
            $table->string('dedup_key', 128)->nullable(false)->change();
            $table->dropUnique('ticket_article_views_unique');
            $table->unique(['article_id', 'dedup_key'], 'ticket_article_views_dedup_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_article_views', function (Blueprint $table) {
            $table->dropUnique('ticket_article_views_dedup_unique');
            $table->unique(
                ['article_id', 'visitor_fingerprint', 'user_id'],
                'ticket_article_views_unique'
            );
            $table->dropColumn('dedup_key');
        });
    }
};
