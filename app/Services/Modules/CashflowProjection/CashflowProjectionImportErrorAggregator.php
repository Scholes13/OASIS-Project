<?php

namespace App\Services\Modules\CashflowProjection;

use App\Models\Core\User;
use Illuminate\Support\Facades\Log;

/**
 * Error collection + structured failure payload formatter for the
 * Cashflow Projection import flow.
 *
 * Lifted verbatim from CashflowProjectionEntryImportService:
 *  - failurePayload (success/failure JSON shape preserved)
 *  - logValidationFailure
 *  - logUnexpectedFailure
 */
class CashflowProjectionImportErrorAggregator
{
    public const MAX_UI_ERRORS = 100;

    public const MAX_LOGGED_ERRORS = 20;

    /**
     * @param  array<int, array<string, mixed>>  $errors
     * @return array<string, mixed>
     */
    public function failurePayload(
        string $summary,
        string $fileName,
        int $totalRows,
        array $errors,
        ?int $errorCount = null,
        bool $truncated = false
    ): array {
        $failedRows = collect($errors)
            ->pluck('row')
            ->filter(fn ($row) => is_int($row) && $row > 1)
            ->unique()
            ->count();

        return [
            'status' => 'failed',
            'summary' => $summary,
            'file_name' => $fileName,
            'total_rows' => $totalRows,
            'processed_rows' => $totalRows,
            'created_rows' => 0,
            'updated_rows' => 0,
            'failed_rows' => $failedRows,
            'truncated' => $truncated || (($errorCount ?? count($errors)) > self::MAX_UI_ERRORS),
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function logValidationFailure(User $user, int $activeBusinessUnitId, string $fileName, int $parsedRows, array $payload): void
    {
        Log::warning('cashflow_entries_import_failed', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'active_business_unit_id' => $activeBusinessUnitId,
            'file_name' => $fileName,
            'parsed_rows' => $parsedRows,
            'failed_rows' => $payload['failed_rows'] ?? 0,
            'errors' => array_slice($payload['errors'] ?? [], 0, self::MAX_LOGGED_ERRORS),
        ]);
    }

    public function logUnexpectedFailure(\Throwable $exception, User $user, int $activeBusinessUnitId, string $fileName, int $parsedRows): void
    {
        Log::error('cashflow_entries_import_exception', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'active_business_unit_id' => $activeBusinessUnitId,
            'file_name' => $fileName,
            'parsed_rows' => $parsedRows,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
