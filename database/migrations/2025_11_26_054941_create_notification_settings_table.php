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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            
            // SMTP Configuration
            $table->string('smtp_host')->default('smtp.gmail.com');
            $table->integer('smtp_port')->default(587);
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable()->comment('Encrypted password');
            $table->enum('smtp_encryption', ['tls', 'ssl', 'none'])->default('tls');
            
            // Email Settings
            $table->string('mail_from_address')->default('noreply@werkudara.com');
            $table->string('mail_from_name')->default('WNS Purchase Request System');
            
            // Notification Options
            $table->boolean('email_enabled')->default(false)->comment('Enable/disable email notifications');
            $table->boolean('fallback_to_database')->default(true)->comment('Always save to database as fallback');
            $table->integer('link_expiry_days')->default(3)->comment('Public approval link expiry in days');
            $table->boolean('retry_failed_emails')->default(false)->comment('Auto-retry failed emails');
            
            // Monitoring
            $table->integer('total_sent')->default(0)->comment('Total emails sent successfully');
            $table->integer('total_failed')->default(0)->comment('Total emails failed');
            $table->timestamp('last_email_sent_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
