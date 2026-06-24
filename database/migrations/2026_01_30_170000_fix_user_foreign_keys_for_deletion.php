<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix remaining FK constraints to allow user deletion.
     * Most FKs are already SET NULL from previous partial migrations.
     */
    public function up(): void
    {
        $dropForeignIfExists = function (string $tableName, string $columnName): void {
            if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                return;
            }

            $constraint = DB::selectOne(
                "SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    AND COLUMN_NAME = ?
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1",
                [$tableName, $columnName]
            );

            if ($constraint?->CONSTRAINT_NAME) {
                Schema::table($tableName, function (Blueprint $table) use ($constraint) {
                    $table->dropForeign($constraint->CONSTRAINT_NAME);
                });
            }
        };

        // 1. contacts.created_by - FK was dropped, need to make nullable and recreate
        if (Schema::hasTable('contacts')) {
            $dropForeignIfExists('contacts', 'created_by');
            Schema::table('contacts', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->change();
            });
            Schema::table('contacts', function (Blueprint $table) {
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // 2. company_visit_history.user_id - CASCADE -> SET NULL
        if (Schema::hasTable('company_visit_history')) {
            $dropForeignIfExists('company_visit_history', 'user_id');
            Schema::table('company_visit_history', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });
            Schema::table('company_visit_history', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        // 3. pr_number_reservations - RESTRICT -> SET NULL
        if (Schema::hasTable('pr_number_reservations')) {
            $dropForeignIfExists('pr_number_reservations', 'user_id');
            $dropForeignIfExists('pr_number_reservations', 'voided_by');
            Schema::table('pr_number_reservations', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });
            Schema::table('pr_number_reservations', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('voided_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // 4. stock_number_reservations - RESTRICT -> SET NULL
        if (Schema::hasTable('stock_number_reservations')) {
            $dropForeignIfExists('stock_number_reservations', 'user_id');
            $dropForeignIfExists('stock_number_reservations', 'voided_by');
            Schema::table('stock_number_reservations', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });
            Schema::table('stock_number_reservations', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('voided_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // 5. task_attachments.uploaded_by - CASCADE -> SET NULL
        if (Schema::hasTable('task_attachments')) {
            $dropForeignIfExists('task_attachments', 'uploaded_by');
            Schema::table('task_attachments', function (Blueprint $table) {
                $table->unsignedBigInteger('uploaded_by')->nullable()->change();
            });
            Schema::table('task_attachments', function (Blueprint $table) {
                $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // 6. Add missing name columns for tables that don't have them yet
        if (Schema::hasTable('stock_requests') && ! Schema::hasColumn('stock_requests', 'requester_name')) {
            Schema::table('stock_requests', function (Blueprint $table) {
                $table->string('requester_name')->nullable()->after('user_id');
                $table->string('last_modified_by_name')->nullable()->after('last_modified_by');
                $table->string('offline_approved_by_name')->nullable()->after('offline_approved_by');
            });
            DB::table('stock_requests')
                ->whereNotNull('user_id')
                ->update([
                    'requester_name' => DB::raw('(SELECT name FROM users WHERE users.id = stock_requests.user_id)'),
                ]);
        }

        if (Schema::hasTable('task_participants') && ! Schema::hasColumn('task_participants', 'participant_name')) {
            Schema::table('task_participants', function (Blueprint $table) {
                $table->string('participant_name')->nullable()->after('user_id');
            });
            DB::table('task_participants')
                ->whereNotNull('user_id')
                ->update([
                    'participant_name' => DB::raw('(SELECT name FROM users WHERE users.id = task_participants.user_id)'),
                ]);
        }

        if (Schema::hasTable('backdate_permissions') && ! Schema::hasColumn('backdate_permissions', 'user_name')) {
            Schema::table('backdate_permissions', function (Blueprint $table) {
                $table->string('user_name')->nullable()->after('user_id');
                $table->string('approved_by_name')->nullable()->after('approved_by');
                $table->string('rejected_by_name')->nullable()->after('rejected_by');
            });
            DB::table('backdate_permissions')
                ->whereNotNull('user_id')
                ->update([
                    'user_name' => DB::raw('(SELECT name FROM users WHERE users.id = backdate_permissions.user_id)'),
                ]);
        }

        if (Schema::hasTable('employee_tasks') && ! Schema::hasColumn('employee_tasks', 'created_by_name')) {
            Schema::table('employee_tasks', function (Blueprint $table) {
                $table->string('created_by_name')->nullable()->after('created_by');
                $table->string('completed_by_name')->nullable()->after('completed_by');
            });
            DB::table('employee_tasks')
                ->whereNotNull('created_by')
                ->update([
                    'created_by_name' => DB::raw('(SELECT name FROM users WHERE users.id = employee_tasks.created_by)'),
                ]);
        }
    }

    public function down(): void
    {
        // Rollback is complex - just note that this migration was run
    }
};
