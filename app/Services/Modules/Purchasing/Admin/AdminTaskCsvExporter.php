<?php

namespace App\Services\Modules\Purchasing\Admin;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the personal task history CSV/Excel export.
 *
 * Extracted from
 * {@see \App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::exportTaskHistory()}.
 * Behavior preserved verbatim (CSV with UTF-8 BOM, header row, status humanization).
 */
class AdminTaskCsvExporter
{
    private const HEADER = [
        'Document',
        'Type',
        'Business Unit',
        'Status',
        'Entered At',
        'Follow-up Time (min)',
        'Completion Time (min)',
        'Estimated Price',
        'Realized Price',
        'Savings Amount',
        'Savings %',
    ];

    /**
     * @param  array{format?: string|null, date_from?: string|null, date_to?: string|null, status?: string|null, type?: string|null}  $params
     */
    public function streamPersonalTaskHistory(User $user, array $params): StreamedResponse
    {
        $format = $params['format'] ?? 'csv';

        $tasks = $this->buildExportQuery($user, $params)->get();

        $filename = 'task-history-'.$user->name.'-'.now()->format('Y-m-d').'.'.($format === 'excel' ? 'xls' : 'csv');

        return response()->streamDownload(function () use ($tasks) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, self::HEADER);

            foreach ($tasks as $task) {
                fputcsv($handle, $this->formatRow($task));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => $format === 'excel' ? 'application/vnd.ms-excel' : 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<string, string|null>  $params
     */
    private function buildExportQuery(User $user, array $params): Builder
    {
        $query = AdminTask::with(['taskable', 'businessUnit', 'department'])
            ->where('assigned_admin_id', $user->id)
            ->orderBy('entered_at', 'desc');

        if (! empty($params['date_from'])) {
            $query->whereDate('entered_at', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('entered_at', '<=', $params['date_to']);
        }

        $status = $params['status'] ?? 'all';
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $type = $params['type'] ?? 'all';
        if ($type === 'purchase_request') {
            $query->where('taskable_type', 'like', '%PurchaseRequest%');
        } elseif ($type === 'stock_request') {
            $query->where('taskable_type', 'like', '%StockRequest%');
        }

        return $query;
    }

    private function formatRow(AdminTask $task): array
    {
        $docNumber = $task->taskable->pr_number ?? $task->taskable->st_number ?? 'N/A';
        $type = str_contains((string) $task->taskable_type, 'PurchaseRequest') ? 'PR' : 'ST';
        $status = match ($task->status) {
            'pending_followup' => 'Pending',
            'in_progress' => 'In Progress',
            'done' => 'Completed',
            default => $task->status,
        };

        return [
            $docNumber,
            $type,
            $task->businessUnit->name ?? 'N/A',
            $status,
            $task->entered_at?->format('Y-m-d H:i'),
            $task->followup_time_minutes,
            $task->completion_time_minutes,
            $task->estimated_total_price,
            $task->realized_total_price,
            $task->savings_amount,
            $task->savings_percentage,
        ];
    }
}
