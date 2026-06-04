<?php

namespace App\Services\Modules\CashflowProjection;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashflowProjectionEntryImportTemplateService
{
    /**
     * @var array<int, string>
     */
    public const TEMPLATE_HEADERS = [
        'line_item_id',
        'year',
        'business_unit_code',
        'department_code',
        'action_code',
        'transaction_date',
        'due_date',
        'is_estimated_date',
        'amount',
        'description',
        'keterangan',
        'notes',
    ];

    public function __construct(
        protected CashflowProjectionScopeService $scopeService,
        protected CashflowProjectionTemplateService $templateService
    ) {}

    public function generateTemplate(int $activeBusinessUnitId, User $user, int $year, int $month): StreamedResponse
    {
        $allowedBusinessUnitIds = $this->scopeService->allowedBusinessUnitIds($user, $activeBusinessUnitId);
        $businessUnits = BusinessUnit::query()
            ->whereIn('id', $allowedBusinessUnitIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $departments = $this->scopeService->allowedDepartments($user, $activeBusinessUnitId)
            ->filter(fn (Department $department): bool => ! $department->activeChildren()->exists())
            ->values();

        $spreadsheet = new Spreadsheet;
        $this->buildTemplateSheet($spreadsheet);
        $this->buildReferenceSheet($spreadsheet, $businessUnits, $departments);
        $this->buildExistingEntriesSheet($spreadsheet, $departments, $year, $month);

        $filename = 'cashflow_entries_import_template.xlsx';

        return new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    protected function buildTemplateSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');

        $sheet->fromArray(self::TEMPLATE_HEADERS, null, 'A1');
        $this->styleHeader($sheet, 'A1:L1');

        $sheet->freezePane('A2');

        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * @param  Collection<int, BusinessUnit>  $businessUnits
     * @param  Collection<int, Department>  $departments
     */
    protected function buildReferenceSheet(Spreadsheet $spreadsheet, Collection $businessUnits, Collection $departments): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Reference');

        $row = 1;
        $sheet->setCellValue('A'.$row, 'Strict Import Rules');
        $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(14);
        $row += 2;

        $rules = [
            'Isi hanya sheet Template. Sheet Reference dan Existing Entries bersifat baca-saja.',
            'Header Template wajib tetap urut persis seperti file download.',
            'Tanggal hanya menerima sel Excel date atau string YYYY-MM-DD.',
            'is_estimated_date hanya boleh TRUE atau FALSE.',
            'line_item_id kosong = create baru. line_item_id terisi = update row existing.',
            'Import bersifat all-or-nothing. Satu baris gagal akan membatalkan seluruh file.',
        ];

        foreach ($rules as $rule) {
            $sheet->setCellValue('A'.$row, $rule);
            $row++;
        }

        $row += 2;
        $sheet->setCellValue('A'.$row, 'Required Columns');
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        $row++;

        foreach (self::TEMPLATE_HEADERS as $header) {
            $sheet->setCellValue('A'.$row, $header);
            $row++;
        }

        $row += 2;
        $sheet->setCellValue('A'.$row, 'Allowed Business Units');
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        $row++;

        foreach ($businessUnits as $businessUnit) {
            $sheet->setCellValue('A'.$row, $businessUnit->code);
            $sheet->setCellValue('B'.$row, $businessUnit->name);
            $row++;
        }

        $row += 2;
        $sheet->setCellValue('A'.$row, 'Allowed Departments By Business Unit');
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        $row++;
        $sheet->fromArray(['business_unit_code', 'department_code', 'department_name'], null, 'A'.$row);
        $this->styleHeader($sheet, 'A'.$row.':C'.$row);
        $row++;

        foreach ($departments as $department) {
            $sheet->setCellValue('A'.$row, $department->businessUnit?->code);
            $sheet->setCellValue('B'.$row, $department->code);
            $sheet->setCellValue('C'.$row, $department->name);
            $row++;
        }

        $row += 2;
        $sheet->setCellValue('A'.$row, 'Allowed Action Codes By Department');
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        $row++;
        $sheet->fromArray(['business_unit_code', 'department_code', 'action_code', 'action_label', 'flow_type'], null, 'A'.$row);
        $this->styleHeader($sheet, 'A'.$row.':E'.$row);
        $row++;

        foreach ($departments as $department) {
            foreach ($this->templateService->actionOptionsForDepartment($department) as $action) {
                $sheet->setCellValue('A'.$row, $department->businessUnit?->code);
                $sheet->setCellValue('B'.$row, $department->code);
                $sheet->setCellValue('C'.$row, $action['code']);
                $sheet->setCellValue('D'.$row, $action['label']);
                $sheet->setCellValue('E'.$row, $action['flow_type']);
                $row++;
            }
        }

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * @param  Collection<int, Department>  $departments
     */
    protected function buildExistingEntriesSheet(Spreadsheet $spreadsheet, Collection $departments, int $year, int $month): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Existing Entries');
        $sheet->fromArray(self::TEMPLATE_HEADERS, null, 'A1');
        $this->styleHeader($sheet, 'A1:L1');
        $sheet->freezePane('A2');

        $departmentIds = $departments->pluck('id')->all();
        $lineItems = CashflowProjectionLineItem::query()
            ->with(['cycle', 'department.businessUnit'])
            ->whereIn('department_id', $departmentIds)
            ->whereHas('cycle', fn ($query) => $query->where('year', $year))
            ->whereMonth('transaction_date', $month)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(CashflowProjectionEntryImportService::MAX_ROWS)
            ->get();

        $row = 2;
        foreach ($lineItems as $lineItem) {
            $sheet->setCellValueExplicit('A'.$row, (string) $lineItem->id, DataType::TYPE_STRING);
            $sheet->setCellValue('B'.$row, $lineItem->cycle?->year);
            $sheet->setCellValue('C'.$row, $lineItem->department?->businessUnit?->code);
            $sheet->setCellValue('D'.$row, $lineItem->department?->code);
            $sheet->setCellValue('E'.$row, $lineItem->action_code);
            $sheet->setCellValue('F'.$row, optional($lineItem->transaction_date)->format('Y-m-d'));
            $sheet->setCellValue('G'.$row, optional($lineItem->due_date)->format('Y-m-d'));
            $sheet->setCellValue('H'.$row, $lineItem->is_estimated_date ? 'TRUE' : 'FALSE');
            $sheet->setCellValue('I'.$row, (float) $lineItem->amount);
            $sheet->setCellValue('J'.$row, $lineItem->description);
            $sheet->setCellValue('K'.$row, $lineItem->keterangan);
            $sheet->setCellValue('L'.$row, $lineItem->notes);
            $row++;
        }

        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    protected function styleHeader(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1D4ED8'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
    }
}
