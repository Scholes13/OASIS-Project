<?php

namespace App\Console\Commands;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Services\Modules\Ticket\LegacyImport\LegacyImportOptions;
use App\Services\Modules\Ticket\LegacyImport\LegacyImportReport;
use App\Services\Modules\Ticket\LegacyImport\LegacyTicketImporter;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class ImportLegacyRequestTickets extends Command
{
    protected $signature = 'ticket:import-legacy-request
        {--legacy-host=127.0.0.1 : Legacy MySQL host}
        {--legacy-port=3306 : Legacy MySQL port}
        {--legacy-database= : Legacy database name}
        {--legacy-username= : Legacy database username}
        {--legacy-password= : Legacy database password. Falls back to configured import password}
        {--legacy-password-file= : Read the password from a protected temporary file}
        {--legacy-password-stdin : Read the password from standard input without echoing it}
        {--business-unit= : Target OASIS business unit id or code}
        {--fallback-user= : Target fallback user id or email for unmapped legacy staff}
        {--force-assignee= : Assign every selected imported ticket to this target user id or email}
        {--fallback-department= : Target fallback department id or code for unmapped legacy departments}
        {--legacy-storage= : Legacy storage/app/public path for attachment copy}
        {--copy-attachments : Copy legacy attachment files into OASIS private storage}
        {--update-existing : Update only records previously imported from this source}
        {--limit= : Limit the selected legacy tickets and their children}
        {--dry-run : Preflight the full selected import without writing data or files}';

    protected $description = 'Safely import tickets from request.werkudara.com into OASIS.';

    public function handle(LegacyTicketImporter $importer): int
    {
        try {
            $options = $this->buildImportOptions();
            $legacy = $this->legacyConnection();
            $dryRun = (bool) $this->option('dry-run');

            $this->info(sprintf(
                '%s legacy tickets into %s (%s).',
                $dryRun ? 'Preflighting' : 'Importing',
                $options->businessUnit->code,
                $options->businessUnit->name,
            ));

            $report = $importer->run($legacy, $options, $dryRun);
            $this->renderReport($report);

            if ($report->hasErrors()) {
                $this->error('Preflight failed. No OASIS data or files were written.');

                return self::FAILURE;
            }

            $this->info($dryRun
                ? 'Dry-run complete. No OASIS data or files were written.'
                : 'Legacy ticket import completed.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function buildImportOptions(): LegacyImportOptions
    {
        $businessUnit = $this->resolveBusinessUnit((string) $this->option('business-unit'));
        $fallbackUser = $this->resolveUser((string) $this->option('fallback-user'));
        $forceAssigneeValue = trim((string) $this->option('force-assignee'));
        $forceAssignee = $forceAssigneeValue === '' ? null : $this->resolveUser($forceAssigneeValue);
        $updateExisting = (bool) $this->option('update-existing');
        if ($businessUnit === null || $fallbackUser === null) {
            throw new InvalidArgumentException('Provide a valid --business-unit and --fallback-user.');
        }
        if ($forceAssigneeValue !== '' && $forceAssignee === null) {
            throw new InvalidArgumentException('Provide a valid --force-assignee target user.');
        }
        if ($forceAssignee !== null && ! $updateExisting) {
            throw new InvalidArgumentException('--force-assignee requires --update-existing.');
        }
        if ($forceAssignee !== null && ! UserBusinessUnit::query()
            ->where('user_id', $forceAssignee->id)
            ->where('business_unit_id', $businessUnit->id)
            ->where('is_active', true)
            ->where('is_it_support_admin', true)
            ->exists()) {
            throw new InvalidArgumentException(
                'The --force-assignee user must be an active IT Support admin in the target business unit.'
            );
        }

        $fallbackDepartment = $this->resolveDepartment(
            (string) $this->option('fallback-department'),
            $businessUnit,
        );
        if ($fallbackDepartment === null) {
            throw new InvalidArgumentException('Provide a valid fallback department in the target business unit.');
        }

        $limit = trim((string) $this->option('limit'));
        if ($limit !== '' && (! ctype_digit($limit) || (int) $limit < 1)) {
            throw new InvalidArgumentException('--limit must be a positive integer.');
        }

        return new LegacyImportOptions(
            businessUnit: $businessUnit,
            fallbackUser: $fallbackUser,
            forceAssignee: $forceAssignee,
            fallbackDepartment: $fallbackDepartment,
            source: (string) config('legacy_ticket_import.source', 'request.werkudara.com'),
            legacyStorage: $this->option('legacy-storage') ?: null,
            copyAttachments: (bool) $this->option('copy-attachments'),
            updateExisting: $updateExisting,
            limit: $limit === '' ? null : (int) $limit,
        );
    }

    private function legacyConnection(): ConnectionInterface
    {
        $database = trim((string) $this->option('legacy-database'));
        $username = trim((string) $this->option('legacy-username'));
        if ($database === '' || $username === '') {
            throw new InvalidArgumentException('Provide --legacy-database and --legacy-username.');
        }

        config(['database.connections.legacy_request_tickets' => [
            'driver' => 'mysql',
            'host' => (string) $this->option('legacy-host'),
            'port' => (string) $this->option('legacy-port'),
            'database' => $database,
            'username' => $username,
            'password' => $this->legacyPassword(),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ]]);
        DB::purge('legacy_request_tickets');

        return DB::connection('legacy_request_tickets');
    }

    private function legacyPassword(): string
    {
        if ((bool) $this->option('legacy-password-stdin')) {
            $password = stream_get_contents(STDIN);
            if ($password === false) {
                throw new InvalidArgumentException('Unable to read the legacy password from standard input.');
            }

            return rtrim($password, "\r\n");
        }

        $passwordFile = trim((string) $this->option('legacy-password-file'));
        if ($passwordFile !== '') {
            if (! is_file($passwordFile) || ! is_readable($passwordFile)) {
                throw new InvalidArgumentException('The legacy password file is not readable.');
            }

            return rtrim((string) file_get_contents($passwordFile), "\r\n");
        }

        return (string) ($this->option('legacy-password') ?: config('legacy_ticket_import.password', ''));
    }

    private function renderReport(LegacyImportReport $report): void
    {
        $stats = $report->stats();
        if ($stats !== []) {
            $this->table(['Metric', 'Value'], collect($stats)->map(fn ($value, $key) => [$key, $value])->values()->all());
        }
        foreach ($report->warnings() as $warning) {
            $this->warn($warning);
        }
        foreach ($report->errors() as $error) {
            $this->error($error);
        }
    }

    private function resolveBusinessUnit(string $value): ?BusinessUnit
    {
        return is_numeric($value)
            ? BusinessUnit::query()->find((int) $value)
            : BusinessUnit::query()->where('code', Str::upper($value))->first();
    }

    private function resolveUser(string $value): ?User
    {
        return is_numeric($value)
            ? User::query()->find((int) $value)
            : User::query()->where('email', $value)->first();
    }

    private function resolveDepartment(string $value, BusinessUnit $businessUnit): ?Department
    {
        $query = Department::query()->where('business_unit_id', $businessUnit->id);
        if (is_numeric($value)) {
            return $query->find((int) $value);
        }

        $key = Str::upper($value);

        return $query->where(function ($nested) use ($key) {
            $nested->whereRaw('UPPER(code) = ?', [$key])->orWhereRaw('UPPER(name) = ?', [$key]);
        })->first();
    }
}
