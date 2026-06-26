<?php

namespace App\Console\Commands;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportLegacyRequestTickets extends Command
{
    protected $signature = 'ticket:import-legacy-request
        {--legacy-host=127.0.0.1 : Legacy MySQL host}
        {--legacy-port=3306 : Legacy MySQL port}
        {--legacy-database= : Legacy database name}
        {--legacy-username= : Legacy database username}
        {--legacy-password= : Legacy database password. Falls back to LEGACY_TICKETING_DB_PASSWORD}
        {--business-unit= : Target OASIS business unit id or code}
        {--fallback-user= : Target fallback user id or email for unmapped legacy staff}
        {--fallback-department= : Target fallback department id or code for unmapped legacy departments}
        {--legacy-storage= : Legacy storage/app/public path for attachment copy}
        {--copy-attachments : Copy legacy attachment files into OASIS private storage}
        {--update-existing : Update tickets that already exist by ticket_number}
        {--limit= : Limit number of legacy tickets to import}
        {--dry-run : Show the import plan without writing data}';

    protected $description = 'Import ticket data from the legacy request.werkudara.com database into the OASIS ticket module.';

    /** @var array<int, int> */
    private array $categoryMap = [];

    /** @var array<int, int|null> */
    private array $staffUserMap = [];

    /** @var array<int, int|null> */
    private array $legacyUserMap = [];

    /** @var array<string, int|null> */
    private array $departmentMap = [];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $businessUnit = $this->resolveBusinessUnit((string) $this->option('business-unit'));
        $fallbackUser = $this->resolveUser((string) $this->option('fallback-user'));
        $fallbackDepartment = $this->resolveDepartment((string) $this->option('fallback-department'), $businessUnit);

        if (! $businessUnit || ! $fallbackUser || ! $fallbackDepartment) {
            $this->error('Missing mapping. Provide --business-unit, --fallback-user, and --fallback-department.');

            return self::FAILURE;
        }

        if (! Schema::hasTable('ticket_categories') || ! Schema::hasTable('tickets')) {
            $this->error('OASIS ticket tables are not available. Run migrations first.');

            return self::FAILURE;
        }

        $legacy = $this->legacyConnection();
        $this->assertLegacyTables($legacy);

        $this->info(sprintf(
            '%s legacy request tickets into BU %s (%s), fallback user %s, fallback department %s.',
            $dryRun ? 'Planning import of' : 'Importing',
            $businessUnit->code,
            $businessUnit->name,
            $fallbackUser->email,
            $fallbackDepartment->code,
        ));

        $stats = [
            'categories_created' => 0,
            'categories_existing' => 0,
            'tickets_created' => 0,
            'tickets_updated' => 0,
            'tickets_skipped' => 0,
            'comments_created' => 0,
            'comments_skipped' => 0,
            'attachments_created' => 0,
            'attachments_skipped' => 0,
            'missing_requesters' => 0,
            'missing_assignees' => 0,
            'missing_departments' => 0,
            'missing_files' => 0,
        ];

        DB::transaction(function () use ($legacy, $businessUnit, $fallbackUser, $fallbackDepartment, $dryRun, &$stats) {
            $this->importCategories($legacy, $businessUnit, $dryRun, $stats);
            $this->preloadLegacyUserMaps($legacy);

            $query = $legacy->table('tickets')->orderBy('id');
            if ($this->option('limit')) {
                $query->limit((int) $this->option('limit'));
            }

            foreach ($query->cursor() as $legacyTicket) {
                $ticketId = $this->importTicket(
                    $legacy,
                    $legacyTicket,
                    $businessUnit,
                    $fallbackUser,
                    $fallbackDepartment,
                    $dryRun,
                    $stats
                );

                if ($ticketId === null || $dryRun) {
                    continue;
                }

                $this->importComments($legacy, (int) $legacyTicket->id, $ticketId, $fallbackUser, $stats);
                $this->importAttachments($legacy, (int) $legacyTicket->id, $ticketId, $fallbackUser, $stats);
            }

        });

        $this->table(['Metric', 'Value'], collect($stats)->map(fn ($value, $key) => [$key, $value])->all());

        if ($dryRun) {
            $this->warn('Dry-run complete. No OASIS data was written.');
        }

        return self::SUCCESS;
    }

    private function legacyConnection(): \Illuminate\Database\ConnectionInterface
    {
        $database = (string) $this->option('legacy-database');
        $username = (string) $this->option('legacy-username');
        $password = (string) ($this->option('legacy-password') ?: env('LEGACY_TICKETING_DB_PASSWORD', ''));

        if ($database === '' || $username === '') {
            throw new \InvalidArgumentException('Provide --legacy-database and --legacy-username.');
        }

        config([
            'database.connections.legacy_request_tickets' => [
                'driver' => 'mysql',
                'host' => (string) $this->option('legacy-host'),
                'port' => (string) $this->option('legacy-port'),
                'database' => $database,
                'username' => $username,
                'password' => $password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
            ],
        ]);

        DB::purge('legacy_request_tickets');

        return DB::connection('legacy_request_tickets');
    }

    private function assertLegacyTables(\Illuminate\Database\ConnectionInterface $legacy): void
    {
        foreach (['categories', 'tickets', 'comments', 'attachments', 'staff', 'users'] as $table) {
            if (! $legacy->getSchemaBuilder()->hasTable($table)) {
                throw new \RuntimeException("Legacy table '{$table}' was not found.");
            }
        }
    }

    private function importCategories(
        \Illuminate\Database\ConnectionInterface $legacy,
        BusinessUnit $businessUnit,
        bool $dryRun,
        array &$stats
    ): void {
        foreach ($legacy->table('categories')->orderBy('id')->get() as $legacyCategory) {
            $existing = DB::table('ticket_categories')
                ->where('business_unit_id', $businessUnit->id)
                ->where('name', $legacyCategory->name)
                ->first();

            if ($existing) {
                $this->categoryMap[(int) $legacyCategory->id] = (int) $existing->id;
                $stats['categories_existing']++;
                continue;
            }

            $stats['categories_created']++;
            if ($dryRun) {
                $this->categoryMap[(int) $legacyCategory->id] = 0;
                continue;
            }

            $this->categoryMap[(int) $legacyCategory->id] = (int) DB::table('ticket_categories')->insertGetId([
                'business_unit_id' => $businessUnit->id,
                'name' => $legacyCategory->name,
                'description' => $legacyCategory->description,
                'color' => $legacyCategory->color ?: '#6366f1',
                'is_active' => true,
                'created_at' => $legacyCategory->created_at ?? now(),
                'updated_at' => $legacyCategory->updated_at ?? now(),
            ]);
        }
    }

    private function importTicket(
        \Illuminate\Database\ConnectionInterface $legacy,
        object $legacyTicket,
        BusinessUnit $businessUnit,
        User $fallbackUser,
        Department $fallbackDepartment,
        bool $dryRun,
        array &$stats
    ): ?int {
        $existing = DB::table('tickets')->where('ticket_number', $legacyTicket->ticket_number)->first();
        if ($existing && ! $this->option('update-existing')) {
            $stats['tickets_skipped']++;

            return null;
        }

        $requester = $this->resolveLegacyRequester($legacyTicket, $fallbackUser, $stats);
        $assignee = $this->resolveLegacyAssignee($legacyTicket, $fallbackUser, $stats);
        $department = $this->resolveLegacyDepartment($legacyTicket->department, $businessUnit, $fallbackDepartment, $stats);

        $payload = [
            'business_unit_id' => $businessUnit->id,
            'ticket_number' => $legacyTicket->ticket_number,
            'title' => $legacyTicket->title,
            'description' => $this->buildDescription($legacyTicket),
            'requester_id' => $requester->id,
            'department_id' => $department->id,
            'status' => $this->normalizeStatus($legacyTicket->status),
            'priority' => $this->normalizePriority($legacyTicket->priority),
            'category_id' => $this->categoryMap[(int) $legacyTicket->category_id] ?? null,
            'assigned_to' => $assignee?->id,
            'created_by' => $requester->id,
            'follow_up_at' => $legacyTicket->follow_up_at,
            'resolved_at' => $legacyTicket->resolved_at,
            'updated_at' => $legacyTicket->updated_at ?? now(),
        ];

        if ($existing) {
            $stats['tickets_updated']++;
            if ($dryRun) {
                return (int) $existing->id;
            }

            DB::table('tickets')->where('id', $existing->id)->update($payload);

            return (int) $existing->id;
        }

        $stats['tickets_created']++;
        if ($dryRun) {
            return null;
        }

        $payload['created_at'] = $legacyTicket->created_at ?? now();

        return (int) DB::table('tickets')->insertGetId($payload);
    }

    private function importComments(
        \Illuminate\Database\ConnectionInterface $legacy,
        int $legacyTicketId,
        int $ticketId,
        User $fallbackUser,
        array &$stats
    ): void {
        foreach ($legacy->table('comments')->where('ticket_id', $legacyTicketId)->orderBy('id')->get() as $legacyComment) {
            $exists = DB::table('ticket_comments')
                ->where('ticket_id', $ticketId)
                ->where('content', $legacyComment->content)
                ->where('created_at', $legacyComment->created_at)
                ->exists();

            if ($exists) {
                $stats['comments_skipped']++;
                continue;
            }

            DB::table('ticket_comments')->insert([
                'ticket_id' => $ticketId,
                'user_id' => $this->legacyUserMap[(int) $legacyComment->user_id] ?? $fallbackUser->id,
                'content' => $legacyComment->content,
                'is_private' => (bool) $legacyComment->is_private,
                'created_at' => $legacyComment->created_at ?? now(),
                'updated_at' => $legacyComment->updated_at ?? now(),
                'deleted_at' => null,
            ]);
            $stats['comments_created']++;
        }
    }

    private function importAttachments(
        \Illuminate\Database\ConnectionInterface $legacy,
        int $legacyTicketId,
        int $ticketId,
        User $fallbackUser,
        array &$stats
    ): void {
        if (! $this->option('copy-attachments')) {
            return;
        }

        $legacyStorage = rtrim((string) $this->option('legacy-storage'), DIRECTORY_SEPARATOR);
        if ($legacyStorage === '') {
            throw new \InvalidArgumentException('Provide --legacy-storage when using --copy-attachments.');
        }

        foreach ($legacy->table('attachments')->where('ticket_id', $legacyTicketId)->orderBy('id')->get() as $legacyAttachment) {
            $exists = DB::table('ticket_attachments')
                ->where('ticket_id', $ticketId)
                ->where('original_filename', $legacyAttachment->original_filename)
                ->where('file_size', (int) $legacyAttachment->file_size)
                ->exists();

            if ($exists) {
                $stats['attachments_skipped']++;
                continue;
            }

            $source = $legacyStorage.DIRECTORY_SEPARATOR.ltrim($legacyAttachment->file_path, '/\\');
            if (! File::exists($source)) {
                $stats['missing_files']++;
                $stats['attachments_skipped']++;
                continue;
            }

            $targetPath = 'ticket-attachments/'.$ticketId.'/legacy-'.$legacyAttachment->filename;
            $target = storage_path('app/'.$targetPath);
            File::ensureDirectoryExists(dirname($target));
            File::copy($source, $target);

            DB::table('ticket_attachments')->insert([
                'ticket_id' => $ticketId,
                'comment_id' => null,
                'filename' => basename($targetPath),
                'original_filename' => $legacyAttachment->original_filename,
                'file_path' => $targetPath,
                'disk' => 'local',
                'file_type' => $legacyAttachment->file_type,
                'file_size' => (int) $legacyAttachment->file_size,
                'uploaded_by' => $fallbackUser->id,
                'created_at' => $legacyAttachment->created_at ?? now(),
                'updated_at' => $legacyAttachment->updated_at ?? now(),
            ]);
            $stats['attachments_created']++;
        }
    }

    private function preloadLegacyUserMaps(\Illuminate\Database\ConnectionInterface $legacy): void
    {
        foreach ($legacy->table('staff')->select('id', 'email')->get() as $staff) {
            $this->staffUserMap[(int) $staff->id] = $this->findUserIdByEmail($staff->email);
        }

        foreach ($legacy->table('users')->select('id', 'email')->get() as $user) {
            $this->legacyUserMap[(int) $user->id] = $this->findUserIdByEmail($user->email);
        }
    }

    private function resolveLegacyRequester(object $legacyTicket, User $fallbackUser, array &$stats): User
    {
        $user = User::where('email', $legacyTicket->requester_email)->first();
        if ($user) {
            return $user;
        }

        $stats['missing_requesters']++;

        return $fallbackUser;
    }

    private function resolveLegacyAssignee(object $legacyTicket, User $fallbackUser, array &$stats): ?User
    {
        if (! $legacyTicket->assigned_to) {
            return null;
        }

        $userId = $this->staffUserMap[(int) $legacyTicket->assigned_to] ?? null;
        if ($userId) {
            return User::find($userId);
        }

        $stats['missing_assignees']++;

        return $fallbackUser;
    }

    private function resolveLegacyDepartment(
        ?string $legacyDepartment,
        BusinessUnit $businessUnit,
        Department $fallbackDepartment,
        array &$stats
    ): Department {
        $key = Str::upper(trim((string) $legacyDepartment));
        if ($key === '') {
            $stats['missing_departments']++;

            return $fallbackDepartment;
        }

        if (array_key_exists($key, $this->departmentMap)) {
            return Department::find($this->departmentMap[$key]) ?? $fallbackDepartment;
        }

        $department = Department::where('business_unit_id', $businessUnit->id)
            ->where(function ($query) use ($key) {
                $query->whereRaw('UPPER(code) = ?', [$key])
                    ->orWhereRaw('UPPER(name) = ?', [$key]);
            })
            ->first();

        if (! $department) {
            $stats['missing_departments']++;
            $this->departmentMap[$key] = $fallbackDepartment->id;

            return $fallbackDepartment;
        }

        $this->departmentMap[$key] = $department->id;

        return $department;
    }

    private function buildDescription(object $legacyTicket): string
    {
        $lines = [
            $legacyTicket->description,
            '',
            '---',
            'Legacy request.werkudara.com import',
            'Legacy requester: '.$legacyTicket->requester_name.' <'.$legacyTicket->requester_email.'>',
        ];

        if (! empty($legacyTicket->requester_phone)) {
            $lines[] = 'Legacy phone: '.$legacyTicket->requester_phone;
        }

        if (! empty($legacyTicket->department)) {
            $lines[] = 'Legacy department: '.$legacyTicket->department;
        }

        return implode(PHP_EOL, array_filter($lines, fn ($line) => $line !== null));
    }

    private function normalizeStatus(?string $status): string
    {
        return in_array($status, ['waiting', 'in_progress', 'done', 'cancelled'], true)
            ? $status
            : 'waiting';
    }

    private function normalizePriority(?string $priority): string
    {
        return in_array($priority, ['low', 'medium', 'high', 'critical'], true)
            ? $priority
            : 'medium';
    }

    private function resolveBusinessUnit(string $value): ?BusinessUnit
    {
        if ($value === '') {
            return null;
        }

        return is_numeric($value)
            ? BusinessUnit::find((int) $value)
            : BusinessUnit::where('code', Str::upper($value))->first();
    }

    private function resolveUser(string $value): ?User
    {
        if ($value === '') {
            return null;
        }

        return is_numeric($value)
            ? User::find((int) $value)
            : User::where('email', $value)->first();
    }

    private function resolveDepartment(string $value, BusinessUnit $businessUnit): ?Department
    {
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Department::where('business_unit_id', $businessUnit->id)->find((int) $value);
        }

        $key = Str::upper($value);

        return Department::where('business_unit_id', $businessUnit->id)
            ->where(function ($query) use ($key) {
                $query->whereRaw('UPPER(code) = ?', [$key])
                    ->orWhereRaw('UPPER(name) = ?', [$key]);
            })
            ->first();
    }

    private function findUserIdByEmail(?string $email): ?int
    {
        if (! $email) {
            return null;
        }

        return User::where('email', $email)->value('id');
    }
}
