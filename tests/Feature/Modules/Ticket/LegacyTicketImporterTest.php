<?php

namespace Tests\Feature\Modules\Ticket;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Services\Modules\Ticket\LegacyImport\LegacyImportOptions;
use App\Services\Modules\Ticket\LegacyImport\LegacyTicketImporter;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class LegacyTicketImporterTest extends TestCase
{
    use RefreshDatabase;

    private ConnectionInterface $legacy;

    private string $legacyRoot;

    private BusinessUnit $businessUnit;

    private Department $department;

    private User $fallbackUser;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->legacyRoot = storage_path('framework/testing/legacy-ticket-import-'.uniqid());
        File::ensureDirectoryExists($this->legacyRoot);

        config(['database.connections.legacy_import_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        DB::purge('legacy_import_test');
        $this->legacy = DB::connection('legacy_import_test');
        $this->createLegacySchema();

        $this->businessUnit = BusinessUnit::factory()->create(['code' => 'LIT']);
        $this->department = Department::factory()->create([
            'business_unit_id' => $this->businessUnit->id,
            'code' => 'IT',
            'name' => 'Information Technology',
        ]);
        $this->fallbackUser = User::factory()->create(['email' => 'fallback@example.test']);
    }

    protected function tearDown(): void
    {
        DB::purge('legacy_import_test');
        File::deleteDirectory($this->legacyRoot);
        parent::tearDown();
    }

    public function test_import_is_idempotent_and_preserves_standalone_and_comment_attachments(): void
    {
        $requester = User::factory()->create(['email' => 'requester@example.test']);
        $assignee = User::factory()->create(['email' => 'assignee@example.test']);
        $commenter = User::factory()->create(['email' => 'commenter@example.test']);
        $this->seedLegacyTicket();
        $this->legacy->table('staff')->insert(['id' => 9, 'email' => $assignee->email]);
        $this->legacy->table('users')->insert(['id' => 7, 'email' => $commenter->email]);
        $this->legacy->table('comments')->insert([
            'id' => 21,
            'ticket_id' => 11,
            'user_id' => 7,
            'content' => 'Legacy response',
            'is_private' => false,
            'attachment_path' => 'comments/evidence.txt',
            'created_at' => '2026-01-02 00:00:00',
            'updated_at' => '2026-01-02 00:00:00',
        ]);
        $this->legacy->table('attachments')->insert([
            'id' => 31,
            'ticket_id' => 11,
            'filename' => 'report.txt',
            'original_filename' => 'report.txt',
            'file_path' => 'tickets/report.txt',
            'file_type' => 'text/plain',
            'file_size' => 6,
            'created_at' => '2026-01-02 00:00:00',
            'updated_at' => '2026-01-02 00:00:00',
        ]);
        File::ensureDirectoryExists($this->legacyRoot.'/comments');
        File::ensureDirectoryExists($this->legacyRoot.'/tickets');
        File::put($this->legacyRoot.'/comments/evidence.txt', 'evidence');
        File::put($this->legacyRoot.'/tickets/report.txt', 'report');

        $options = $this->importOptions();
        $first = app(LegacyTicketImporter::class)->run($this->legacy, $options, false);
        $repairPath = (string) DB::table('ticket_attachments')->where('import_id', 'attachment:31')->value('file_path');
        Storage::disk('local')->delete($repairPath);
        DB::table('ticket_attachments')->where('import_id', 'comment:21')->update([
            'uploaded_by' => $this->fallbackUser->id,
        ]);
        $second = app(LegacyTicketImporter::class)->run($this->legacy, $options, false);

        $this->assertFalse($first->hasErrors());
        $this->assertFalse($second->hasErrors());
        $this->assertSame(1, $second->stats()['attachments_repaired']);
        $this->assertDatabaseCount('tickets', 1);
        $this->assertDatabaseCount('ticket_comments', 1);
        $this->assertDatabaseCount('ticket_attachments', 2);
        $this->assertDatabaseHas('tickets', [
            'import_source' => 'request.werkudara.com',
            'import_id' => '11',
            'requester_id' => $requester->id,
            'assigned_to' => $assignee->id,
            'department_id' => $this->department->id,
        ]);
        $commentId = (int) DB::table('ticket_comments')->where('import_id', '21')->value('id');
        $this->assertDatabaseHas('ticket_comments', ['id' => $commentId, 'user_id' => $commenter->id]);
        $this->assertDatabaseHas('ticket_attachments', [
            'import_id' => 'comment:21',
            'comment_id' => $commentId,
            'uploaded_by' => $commenter->id,
        ]);
        $this->assertDatabaseHas('ticket_attachments', [
            'import_id' => 'attachment:31',
            'comment_id' => null,
            'uploaded_by' => $this->fallbackUser->id,
        ]);
        foreach (DB::table('ticket_attachments')->pluck('file_path') as $path) {
            Storage::disk('local')->assertExists($path);
        }
    }

    public function test_native_ticket_number_collision_aborts_even_with_update_existing(): void
    {
        $this->seedLegacyTicket();
        DB::table('tickets')->insert($this->nativeTicketPayload('REQ-001', 'Native title'));

        $report = app(LegacyTicketImporter::class)->run(
            $this->legacy,
            $this->importOptions(updateExisting: true),
            false,
        );

        $this->assertTrue($report->hasErrors());
        $this->assertDatabaseCount('tickets', 1);
        $this->assertSame('Native title', DB::table('tickets')->value('title'));
        $this->assertDatabaseCount('ticket_categories', 0);
    }

    public function test_dry_run_inspects_children_and_mapping_gaps_without_writes(): void
    {
        $this->seedLegacyTicket(requesterEmail: 'missing@example.test', department: 'UNKNOWN');
        $this->legacy->table('comments')->insert([
            'id' => 22,
            'ticket_id' => 11,
            'user_id' => 404,
            'content' => 'Fallback comment',
            'is_private' => false,
            'attachment_path' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = app(LegacyTicketImporter::class)->run($this->legacy, $this->importOptions(copyAttachments: false), true);

        $this->assertFalse($report->hasErrors());
        $this->assertSame(1, $report->stats()['tickets_inspected']);
        $this->assertSame(1, $report->stats()['comments_inspected']);
        $this->assertSame(1, $report->stats()['missing_requesters']);
        $this->assertSame(1, $report->stats()['missing_departments']);
        $this->assertSame(1, $report->stats()['missing_comment_users']);
        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('ticket_comments', 0);
        $this->assertDatabaseCount('ticket_categories', 0);
    }

    public function test_force_assignee_overrides_legacy_mapping_on_update(): void
    {
        $forcedAssignee = User::factory()->create(['email' => 'pramuji@example.test']);
        $legacyAssignee = User::factory()->create(['email' => 'legacy-assignee@example.test']);
        $this->seedLegacyTicket();
        $this->legacy->table('staff')->insert(['id' => 9, 'email' => $legacyAssignee->email]);

        app(LegacyTicketImporter::class)->run($this->legacy, $this->importOptions(), false);
        $noUpdate = app(LegacyTicketImporter::class)->run(
            $this->legacy,
            $this->importOptions(forceAssignee: $forcedAssignee),
            false,
        );
        $report = app(LegacyTicketImporter::class)->run(
            $this->legacy,
            $this->importOptions(updateExisting: true, forceAssignee: $forcedAssignee),
            false,
        );

        $this->assertArrayNotHasKey('forced_assignee_tickets', $noUpdate->stats());
        $this->assertFalse($report->hasErrors());
        $this->assertSame(1, $report->stats()['forced_assignee_tickets']);
        $this->assertDatabaseHas('tickets', [
            'import_source' => 'request.werkudara.com',
            'import_id' => '11',
            'assigned_to' => $forcedAssignee->id,
        ]);
    }

    public function test_missing_and_unsafe_files_fail_preflight_without_writes(): void
    {
        $this->seedLegacyTicket();
        $this->legacy->table('comments')->insert([
            'id' => 23,
            'ticket_id' => 11,
            'user_id' => null,
            'content' => 'Unsafe',
            'is_private' => false,
            'attachment_path' => '../outside.txt',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->legacy->table('attachments')->insert([
            'id' => 32,
            'ticket_id' => 11,
            'filename' => 'missing.txt',
            'original_filename' => 'missing.txt',
            'file_path' => 'tickets/missing.txt',
            'file_type' => 'text/plain',
            'file_size' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = app(LegacyTicketImporter::class)->run($this->legacy, $this->importOptions(), false);

        $this->assertTrue($report->hasErrors());
        $this->assertSame(1, $report->stats()['unsafe_files']);
        $this->assertSame(1, $report->stats()['missing_files']);
        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('ticket_attachments', 0);
    }

    public function test_missing_file_is_reported_and_skipped_without_blocking_ticket_import(): void
    {
        $this->seedLegacyTicket();
        $this->legacy->table('attachments')->insert([
            'id' => 34,
            'ticket_id' => 11,
            'filename' => 'gone.txt',
            'original_filename' => 'gone.txt',
            'file_path' => 'tickets/gone.txt',
            'file_type' => 'text/plain',
            'file_size' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = app(LegacyTicketImporter::class)->run($this->legacy, $this->importOptions(), false);

        $this->assertFalse($report->hasErrors());
        $this->assertSame(1, $report->stats()['missing_files']);
        $this->assertSame(1, $report->stats()['attachments_skipped']);
        $this->assertNotEmpty($report->warnings());
        $this->assertTrue(collect($report->warnings())->contains(
            fn (string $warning): bool => str_contains($warning, 'missing_assignees'),
        ));
        $this->assertDatabaseCount('tickets', 1);
        $this->assertDatabaseCount('ticket_attachments', 0);
    }

    public function test_limit_imports_only_categories_referenced_by_selected_tickets(): void
    {
        $this->seedLegacyTicket();
        $this->legacy->table('categories')->insert([
            'id' => 5,
            'name' => 'Unused category',
            'description' => null,
            'color' => '#654321',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $secondTicket = $this->legacy->table('tickets')->where('id', 11)->first();
        $payload = (array) $secondTicket;
        $payload['id'] = 12;
        $payload['ticket_number'] = 'REQ-002';
        $payload['category_id'] = 5;
        $this->legacy->table('tickets')->insert($payload);

        $report = app(LegacyTicketImporter::class)->run(
            $this->legacy,
            $this->importOptions(copyAttachments: false, limit: 1),
            false,
        );

        $this->assertFalse($report->hasErrors());
        $this->assertDatabaseCount('tickets', 1);
        $this->assertDatabaseCount('ticket_categories', 1);
        $this->assertDatabaseHas('ticket_categories', ['name' => 'Support']);
        $this->assertDatabaseMissing('ticket_categories', ['name' => 'Unused category']);
    }

    public function test_transaction_failure_removes_newly_copied_files(): void
    {
        $this->seedLegacyTicket();
        $this->legacy->table('attachments')->insert([
            'id' => 33,
            'ticket_id' => 11,
            'filename' => 'rollback.txt',
            'original_filename' => 'rollback.txt',
            'file_path' => 'tickets/rollback.txt',
            'file_type' => 'text/plain',
            'file_size' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        File::ensureDirectoryExists($this->legacyRoot.'/tickets');
        File::put($this->legacyRoot.'/tickets/rollback.txt', 'rollback');

        DB::listen(function (QueryExecuted $query): void {
            if (str_contains($query->sql, 'insert into "ticket_attachments"')) {
                throw new RuntimeException('Simulated attachment insert failure.');
            }
        });

        try {
            app(LegacyTicketImporter::class)->run($this->legacy, $this->importOptions(), false);
            $this->fail('The simulated database failure was not raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated attachment insert failure.', $exception->getMessage());
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('ticket_attachments', 0);
    }

    private function importOptions(
        bool $copyAttachments = true,
        bool $updateExisting = false,
        ?int $limit = null,
        ?User $forceAssignee = null,
    ): LegacyImportOptions {
        return new LegacyImportOptions(
            businessUnit: $this->businessUnit,
            fallbackUser: $this->fallbackUser,
            forceAssignee: $forceAssignee,
            fallbackDepartment: $this->department,
            source: 'request.werkudara.com',
            legacyStorage: $this->legacyRoot,
            copyAttachments: $copyAttachments,
            updateExisting: $updateExisting,
            limit: $limit,
        );
    }

    private function seedLegacyTicket(
        string $requesterEmail = 'requester@example.test',
        string $department = 'IT',
    ): void {
        $this->legacy->table('categories')->insert([
            'id' => 4,
            'name' => 'Support',
            'description' => 'Legacy support',
            'color' => '#123456',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);
        $this->legacy->table('tickets')->insert([
            'id' => 11,
            'ticket_number' => 'REQ-001',
            'title' => 'Legacy ticket',
            'description' => 'Legacy description',
            'requester_name' => 'Requester',
            'requester_email' => $requesterEmail,
            'requester_phone' => '0800',
            'department' => $department,
            'status' => 'in_progress',
            'priority' => 'high',
            'category_id' => 4,
            'assigned_to' => 9,
            'follow_up_at' => null,
            'resolved_at' => null,
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-02 00:00:00',
        ]);
    }

    /** @return array<string, mixed> */
    private function nativeTicketPayload(string $number, string $title): array
    {
        return [
            'business_unit_id' => $this->businessUnit->id,
            'ticket_number' => $number,
            'title' => $title,
            'description' => 'Native',
            'requester_id' => $this->fallbackUser->id,
            'department_id' => $this->department->id,
            'status' => 'waiting',
            'priority' => 'medium',
            'category_id' => null,
            'assigned_to' => null,
            'created_by' => $this->fallbackUser->id,
            'follow_up_at' => null,
            'resolved_at' => null,
            'form_token' => null,
            'import_source' => null,
            'import_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function createLegacySchema(): void
    {
        $schema = $this->legacy->getSchemaBuilder();
        $schema->create('categories', function ($table) {
            $table->integer('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();
        });
        $schema->create('tickets', function ($table) {
            $table->integer('id')->primary();
            $table->string('ticket_number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('requester_name');
            $table->string('requester_email');
            $table->string('requester_phone')->nullable();
            $table->string('department')->nullable();
            $table->string('status')->nullable();
            $table->string('priority')->nullable();
            $table->integer('category_id')->nullable();
            $table->integer('assigned_to')->nullable();
            $table->timestamp('follow_up_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
        $schema->create('comments', function ($table) {
            $table->integer('id')->primary();
            $table->integer('ticket_id');
            $table->integer('user_id')->nullable();
            $table->text('content');
            $table->boolean('is_private')->default(false);
            $table->string('attachment_path')->nullable();
            $table->timestamps();
        });
        $schema->create('attachments', function ($table) {
            $table->integer('id')->primary();
            $table->integer('ticket_id');
            $table->string('filename');
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamps();
        });
        $schema->create('staff', function ($table) {
            $table->integer('id')->primary();
            $table->string('email')->nullable();
        });
        $schema->create('users', function ($table) {
            $table->integer('id')->primary();
            $table->string('email')->nullable();
        });
    }
}
