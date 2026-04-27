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
        Schema::create('ticket_article_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('ticket_knowledge_articles')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('visitor_fingerprint')->nullable()->index();
            $table->string('session_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();
            $table->unique(['article_id', 'visitor_fingerprint', 'user_id'], 'ticket_article_views_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_article_views');
    }
};
