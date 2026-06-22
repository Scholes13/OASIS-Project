<?php

namespace App\Services\Modules\Activity;

use App\Models\Modules\Activity\EmployeeTask;
use App\Services\Modules\Activity\Export\ActivityCategoryBreakdownBuilder;
use App\Services\Modules\Activity\Export\ActivityExportFormatter;
use App\Services\Modules\Activity\Export\ActivitySummarySheetBuilder;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Orchestrate the Activity report Excel export.
 *
 * The service applies the request-level filters (BU, department, member
 * focus, date range, status, activity type), then delegates to focused
 * sheet builders under {@see \App\Services\Modules\Activity\Export} so
 * each sheet keeps its own formatting concerns.
 */
class ActivityExportService
{
    public function __construct(
        protected ActivityMemberFocusService $memberFocusService,
        protected ActivityExportFormatter $exportFormatter,
        protected ActivitySummarySheetBuilder $summarySheetBuilder,
        protected ActivityCategoryBreakdownBuilder $categoryBreakdownBuilder,
    ) {}

    /**
     * Export activities to XLSX.
     *
     * When userId is provided (scope=my), uses member focus logic:
     * - created_by = userId OR participant = userId (OR semantics)
     * When focusedMemberUserId is provided (member focus in department scope):
     * - created_by = memberUserId OR participant = memberUserId (OR semantics)
     */
    public function exportToXlsx(
        int $businessUnitId,
        ?int $departmentId = null,
        ?int $userId = null,
        ?int $focusedMemberUserId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $status = null,
        ?int $activityTypeId = null
    ): StreamedResponse {
        $tasks = $this->getFilteredTasks(
            $businessUnitId,
            $departmentId,
            $userId,
            $focusedMemberUserId,
            $dateFrom,
            $dateTo,
            $status,
            $activityTypeId
        );

        $spreadsheet = new Spreadsheet;

        $this->exportFormatter->buildDetailSheet($spreadsheet, $tasks);
        $this->summarySheetBuilder->build($spreadsheet, $tasks);
        $this->categoryBreakdownBuilder->build($spreadsheet, $tasks);
        $this->exportFormatter->buildRawDataSheet($spreadsheet, $tasks);

        $filename = 'activity_report_'.now()->format('Y-m-d_His').'.xlsx';

        return new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Apply the request-level filters and member focus, then return the
     * eager-loaded task collection used by every sheet builder.
     *
     * @return Collection<int, EmployeeTask>
     */
    protected function getFilteredTasks(
        int $businessUnitId,
        ?int $departmentId,
        ?int $userId,
        ?int $focusedMemberUserId,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $status,
        ?int $activityTypeId
    ): Collection {
        $query = EmployeeTask::query()
            ->where('business_unit_id', $businessUnitId)
            ->when(
                $departmentId && ! ($userId !== null && $focusedMemberUserId === null),
                fn ($query) => $query->where('department_id', $departmentId)
            )
            ->when($dateFrom, fn ($query) => $query->whereDate('task_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('task_date', '<=', $dateTo))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($activityTypeId, fn ($query) => $query->where('activity_type_id', $activityTypeId))
            ->with(['activityType', 'subActivity', 'creator', 'department', 'participants'])
            ->orderBy('task_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Apply member focus when userId or focusedMemberUserId is provided.
        // This applies creator OR participant semantics (same as task screen).
        $memberUserId = $focusedMemberUserId ?? $userId;
        $this->memberFocusService->applyMemberFocus($query, $memberUserId);

        return $query->get();
    }
}
