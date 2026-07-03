<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE purchase_requests MODIFY status ENUM('draft', 'submitted', 'in_approval', 'approved', 'rejected', 'voided', 'done') DEFAULT 'draft'");
        DB::statement("ALTER TABLE stock_requests MODIFY status ENUM('draft', 'submitted', 'in_approval', 'approved', 'ga_review', 'ga_rejected', 'ready_for_purchasing', 'rejected', 'voided', 'done') DEFAULT 'draft'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE purchase_requests MODIFY status ENUM('draft', 'submitted', 'in_approval', 'approved', 'rejected', 'voided') DEFAULT 'draft'");
        DB::statement("ALTER TABLE stock_requests MODIFY status ENUM('draft', 'submitted', 'in_approval', 'approved', 'ga_review', 'ga_rejected', 'ready_for_purchasing', 'rejected', 'voided') DEFAULT 'draft'");
    }
};
