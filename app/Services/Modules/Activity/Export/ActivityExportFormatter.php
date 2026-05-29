<?php

namespace App\Services\Modules\Activity\Export;

use App\Models\Core\User;
use App\Models\Modules\Activity\EmployeeTask;
use App\Services\Modules\Activity\ActivityReportAggregationService;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Build the human-friendly "Detail" sheet and the machine-friendly
 * "Data Mentah" raw sheet for the Activity export.
 *
 * Detail sheet uses Indonesian labels and colour-coded status cells; the
 * raw sheet uses snake_case column ids so other tooling can re-import it
 * without translation.
 */
class ActivityExportFormatter
{
    public function __construct(
        protected ActivityReportAggregationService $aggregationService,
        protected SpreadsheetStyleHelper $styleHelper,
    ) {}

    /**
     * Build the active sheet with the human-readable detail rows.
     *
     * @param  Collection<int, EmployeeTask>  $tasks
     */
    public function buildDetailSheet(Spreadsheet $spreadsheet, Collection $tasks): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail');

        $headers = [
            'No',
            'Tanggal',
            'Judul Aktivitas',
            'Deskripsi',
            'Ringkasan Aktivitas',
            'Kategori',
            'Sub Kategori',
            'Status',
            'Prioritas',
            'Pembuat',
            'Departemen',
            'Jatuh Tempo',
            'Mulai',
            'Selesai',
            'Durasi (menit)',
            'Catatan',
            'Jumlah Participant',
            'Daftar Participant',
            'Participant IDs',
        ];

        $this->styleHelper->writeHeaderRow($sheet, $headers, 'A1:S1');

        $row = 2;
        $no = 1;
        foreach ($tasks as $task) {
            $participantData = $this->formatParticipantData($task);

            $sheet->fromArray([
                $no,
                $task->task_date?->format('Y-m-d') ?? '-',
                $task->task_title ?: 'Aktivitas tanpa judul',
                $task->task_description ?? '-',
                $this->aggregationService->buildTaskSummary($task),
                $this->aggregationService->categoryName($task),
                $this->aggregationService->subCategoryName($task) ?? '-',
                $this->aggregationService->statusLabel((string) $task->status),
                ucfirst((string) ($task->priority ?? 'medium')),
                $task->creator?->name ?? '-',
                $task->department?->name ?? '-',
                $task->due_date?->format('Y-m-d') ?? '-',
                $task->started_at?->format('Y-m-d H:i') ?? '-',
                $task->completed_at?->format('Y-m-d H:i') ?? '-',
                $task->duration_minutes ?? '-',
                $task->notes ?? '-',
                $participantData['jumlah'],
                $participantData['daftar'],
                $participantData['ids'],
            ], null, 'A'.$row);

            $sheet->getStyle('H'.$row)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $this->styleHelper->statusColor((string) $task->status)],
                ],
            ]);

            $row++;
            $no++;
        }

        $this->styleHelper->autoSizeColumns($sheet, 'A', 'S');
        if ($row > 2) {
            $this->styleHelper->applyDataBorders($sheet, 'A2:S'.($row - 1));
        }
    }

    /**
     * Build the snake_case raw sheet used as the canonical export source.
     *
     * @param  Collection<int, EmployeeTask>  $tasks
     */
    public function buildRawDataSheet(Spreadsheet $spreadsheet, Collection $tasks): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Data Mentah');

        $headers = [
            'id_tugas',
            'tanggal_tugas',
            'judul_aktivitas',
            'deskripsi_aktivitas',
            'ringkasan_aktivitas',
            'kategori',
            'sub_kategori',
            'status',
            'prioritas',
            'nama_pembuat',
            'nama_departemen',
            'jatuh_tempo',
            'waktu_mulai',
            'waktu_selesai',
            'durasi_menit',
            'catatan',
            'jumlah_participant',
            'daftar_participant',
            'participant_ids',
        ];

        $this->styleHelper->writeHeaderRow($sheet, $headers, 'A1:S1');

        $row = 2;
        foreach ($tasks as $task) {
            $participantData = $this->formatParticipantData($task);

            $sheet->fromArray([
                $task->id,
                $task->task_date?->format('Y-m-d') ?? '',
                $task->task_title ?: 'Aktivitas tanpa judul',
                $task->task_description ?? '',
                $this->aggregationService->buildTaskSummary($task),
                $this->aggregationService->categoryName($task),
                $this->aggregationService->subCategoryName($task) ?? '',
                (string) $task->status,
                (string) ($task->priority ?? ''),
                $task->creator?->name ?? '',
                $task->department?->name ?? '',
                $task->due_date?->format('Y-m-d') ?? '',
                $task->started_at?->format('Y-m-d H:i') ?? '',
                $task->completed_at?->format('Y-m-d H:i') ?? '',
                $task->duration_minutes ?? '',
                $task->notes ?? '',
                $participantData['jumlah'],
                $participantData['daftar'],
                $participantData['ids'],
            ], null, 'A'.$row);
            $row++;
        }

        $this->styleHelper->autoSizeColumns($sheet, 'A', 'S');
        if ($row > 2) {
            $this->styleHelper->applyDataBorders($sheet, 'A2:S'.($row - 1));
        }
    }

    /**
     * Format participant data for export.
     * Participants are User models through the BelongsToMany relationship.
     *
     * @return array{jumlah: string, daftar: string, ids: string}
     */
    protected function formatParticipantData(EmployeeTask $task): array
    {
        /** @var Collection<int, User> $participants */
        $participants = $task->participants
            ->filter(fn ($user) => $user instanceof User)
            ->sortBy(fn ($user) => $user->name ?? '')
            ->values();

        return [
            'jumlah' => (string) $participants->count(),
            'daftar' => $participants->pluck('name')->join(', '),
            'ids' => $participants->pluck('id')->sort()->join('|'),
        ];
    }
}
