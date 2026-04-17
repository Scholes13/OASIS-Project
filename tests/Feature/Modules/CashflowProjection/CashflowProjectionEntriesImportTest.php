<?php

namespace Tests\Feature\Modules\CashflowProjection;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionAuditLog;
use App\Models\Modules\CashflowProjection\CashflowProjectionCycle;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use App\Models\Modules\CashflowProjection\CashflowProjectionLinkedUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CashflowProjectionEntriesImportTest extends TestCase
{
    use RefreshDatabase;

    private const TEMPLATE_HEADERS = [
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
        'notes',
    ];

    private BusinessUnit $hostBusinessUnit;

    private BusinessUnit $linkedBusinessUnit;

    private Department $financeDepartment;

    private Department $hrDepartment;

    private Department $linkedOpsDepartment;

    private Position $financePosition;

    private User $financeUser;

    private CashflowProjectionLineItem $existingHostLineItem;

    private CashflowProjectionLineItem $existingLinkedLineItem;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);
        config(['debugbar.enabled' => false]);

        $this->hostBusinessUnit = BusinessUnit::create([
            'code' => 'WNS',
            'name' => 'Werkudara Nirwana Sakti',
            'is_active' => true,
        ]);

        $this->linkedBusinessUnit = BusinessUnit::create([
            'code' => 'MRP',
            'name' => 'Mutiara Raya Prima',
            'is_active' => true,
        ]);

        $this->financeDepartment = Department::create([
            'business_unit_id' => $this->hostBusinessUnit->id,
            'code' => 'CFC',
            'name' => 'Core Finance',
            'is_active' => true,
        ]);

        $this->hrDepartment = Department::create([
            'business_unit_id' => $this->hostBusinessUnit->id,
            'code' => 'HR',
            'name' => 'Human Resources',
            'is_active' => true,
        ]);

        $this->linkedOpsDepartment = Department::create([
            'business_unit_id' => $this->linkedBusinessUnit->id,
            'code' => 'OPS',
            'name' => 'Operations',
            'is_active' => true,
        ]);

        $this->financePosition = Position::query()
            ->where('department_id', $this->financeDepartment->id)
            ->where('code', 'STAFF_'.strtoupper($this->financeDepartment->code))
            ->firstOrFail();

        $this->financeUser = User::create([
            'name' => 'Finance Import User',
            'email' => 'finance.import@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567801',
            'primary_department_id' => $this->financeDepartment->id,
            'primary_position_id' => $this->financePosition->id,
            'global_role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->financeUser->businessUnits()->create([
            'business_unit_id' => $this->hostBusinessUnit->id,
            'department_id' => $this->financeDepartment->id,
            'position_id' => $this->financePosition->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        CashflowProjectionLinkedUnit::query()->create([
            'host_business_unit_id' => $this->hostBusinessUnit->id,
            'linked_business_unit_id' => $this->linkedBusinessUnit->id,
            'created_by' => $this->financeUser->id,
        ]);

        $hostCycle = CashflowProjectionCycle::create([
            'business_unit_id' => $this->hostBusinessUnit->id,
            'year' => 2026,
            'status' => 'draft',
            'created_by' => $this->financeUser->id,
            'updated_by' => $this->financeUser->id,
        ]);

        $linkedCycle = CashflowProjectionCycle::create([
            'business_unit_id' => $this->linkedBusinessUnit->id,
            'year' => 2026,
            'status' => 'draft',
            'created_by' => $this->financeUser->id,
            'updated_by' => $this->financeUser->id,
        ]);

        $this->existingHostLineItem = CashflowProjectionLineItem::query()->create([
            'cycle_id' => $hostCycle->id,
            'department_id' => $this->hrDepartment->id,
            'flow_type' => 'out',
            'action_code' => 'OUT_HR_OPS',
            'transaction_date' => '2026-04-09',
            'due_date' => '2026-04-09',
            'is_estimated_date' => false,
            'amount' => 1250000,
            'description' => 'Existing host row',
            'notes' => 'Before import',
            'source_type' => 'manual',
            'created_by' => $this->financeUser->id,
            'updated_by' => $this->financeUser->id,
        ]);

        $this->existingLinkedLineItem = CashflowProjectionLineItem::query()->create([
            'cycle_id' => $linkedCycle->id,
            'department_id' => $this->linkedOpsDepartment->id,
            'flow_type' => 'out',
            'action_code' => 'OUT_OPS_OPS',
            'transaction_date' => '2026-04-11',
            'due_date' => '2026-04-11',
            'is_estimated_date' => true,
            'amount' => 990000,
            'description' => 'Existing linked row',
            'notes' => 'Linked note',
            'source_type' => 'manual',
            'created_by' => $this->financeUser->id,
            'updated_by' => $this->financeUser->id,
        ]);
    }

    public function test_download_import_template_returns_strict_workbook_with_reference_and_existing_entries(): void
    {
        $response = $this->actingAsFinanceUser()->get(route('cashflow-projection.entries.import-template', [
            'year' => 2026,
            'month' => 4,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('content-disposition');

        $spreadsheet = $this->loadSpreadsheetFromBinary($response->streamedContent());

        $this->assertSame(['Template', 'Reference', 'Existing Entries'], $spreadsheet->getSheetNames());
        $this->assertSame(
            self::TEMPLATE_HEADERS,
            $this->sheetRowValues($spreadsheet->getSheetByName('Template')?->toArray() ?? [], 1)
        );

        $existingRows = $spreadsheet->getSheetByName('Existing Entries')?->toArray() ?? [];
        $this->assertTrue(collect($existingRows)->contains(fn (array $row): bool => (string) ($row[0] ?? '') === (string) $this->existingHostLineItem->id));
        $this->assertTrue(collect($existingRows)->contains(fn (array $row): bool => (string) ($row[0] ?? '') === (string) $this->existingLinkedLineItem->id));
    }

    public function test_import_accepts_mixed_create_and_update_rows_and_writes_audit_summary(): void
    {
        Log::spy();

        $file = $this->makeWorkbookUpload([
            [
                '',
                2026,
                'WNS',
                'HR',
                'OUT_HR_GAJI_BENEFIT',
                '2026-04-18',
                '2026-04-18',
                'FALSE',
                2750000,
                'Imported create row',
                'Imported note',
            ],
            [
                $this->existingHostLineItem->id,
                2026,
                'WNS',
                'HR',
                'OUT_HR_OPS',
                '2026-04-19',
                '2026-04-19',
                'TRUE',
                1800000,
                'Existing host row updated',
                'Updated by import',
            ],
        ]);

        $response = $this->actingAsFinanceUser()->post(route('cashflow-projection.entries.import'), [
            'file' => $file,
            'context_year' => 2026,
            'context_month' => 4,
        ]);

        $response->assertRedirect(route('cashflow-projection.entries', [
            'year' => 2026,
            'month' => 4,
        ]));

        /** @var array<string, mixed> $payload */
        $payload = session('cashflow_import', []);
        $this->assertSame([
            'summary' => 'Import berhasil diproses.',
            'file_name' => 'cashflow_entries_import.xlsx',
            'processed_rows' => 2,
            'created_rows' => 1,
            'updated_rows' => 1,
            'failed_rows' => 0,
        ], array_intersect_key($payload, array_flip([
            'summary',
            'file_name',
            'processed_rows',
            'created_rows',
            'updated_rows',
            'failed_rows',
        ])));

        $this->assertDatabaseHas('cashflow_projection_line_items', [
            'department_id' => $this->hrDepartment->id,
            'action_code' => 'OUT_HR_GAJI_BENEFIT',
            'description' => 'Imported create row',
            'source_type' => 'import',
            'created_by' => $this->financeUser->id,
            'updated_by' => $this->financeUser->id,
        ]);

        $this->assertDatabaseHas('cashflow_projection_line_items', [
            'id' => $this->existingHostLineItem->id,
            'description' => 'Existing host row updated',
            'amount' => 1800000,
            'is_estimated_date' => 1,
            'source_type' => 'import',
        ]);

        $this->assertSame(2, CashflowProjectionAuditLog::query()->count());
        $this->assertDatabaseHas('cashflow_projection_audit_logs', [
            'auditable_type' => 'line_item',
            'action' => 'created',
        ]);
        $this->assertDatabaseHas('cashflow_projection_audit_logs', [
            'auditable_type' => 'line_item',
            'action' => 'updated',
        ]);

        Log::shouldHaveReceived('info')->once();
    }

    public function test_import_rejects_duplicate_line_item_ids_before_mutation(): void
    {
        Log::spy();

        $file = $this->makeWorkbookUpload([
            [
                $this->existingHostLineItem->id,
                2026,
                'WNS',
                'HR',
                'OUT_HR_OPS',
                '2026-04-20',
                '2026-04-20',
                'FALSE',
                1900000,
                'First duplicate update',
                '',
            ],
            [
                $this->existingHostLineItem->id,
                2026,
                'WNS',
                'HR',
                'OUT_HR_OPS',
                '2026-04-21',
                '2026-04-21',
                'FALSE',
                1950000,
                'Second duplicate update',
                '',
            ],
        ]);

        $response = $this->actingAsFinanceUser()->post(route('cashflow-projection.entries.import'), [
            'file' => $file,
            'context_year' => 2026,
            'context_month' => 4,
        ]);

        $response->assertRedirect(route('cashflow-projection.entries', [
            'year' => 2026,
            'month' => 4,
        ]));

        $response->assertSessionHas('cashflow_import', function (array $payload): bool {
            return ($payload['failed_rows'] ?? null) === 1
                && collect($payload['errors'] ?? [])->contains(fn (array $error): bool => ($error['column'] ?? null) === 'line_item_id');
        });

        $this->assertDatabaseHas('cashflow_projection_line_items', [
            'id' => $this->existingHostLineItem->id,
            'description' => 'Existing host row',
            'amount' => 1250000,
            'source_type' => 'manual',
        ]);

        $this->assertSame(0, CashflowProjectionAuditLog::query()->count());
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_import_rolls_back_all_rows_when_any_row_is_invalid_and_returns_structured_errors(): void
    {
        Log::spy();

        $file = $this->makeWorkbookUpload([
            [
                '',
                2026,
                'MRP',
                'OPS',
                'OUT_OPS_OPS',
                '2026-04-22',
                '2026-04-22',
                'TRUE',
                2300000,
                'Would be created',
                'Must rollback',
            ],
            [
                '',
                2026,
                'WNS',
                'HR',
                'OUT_HR_UNKNOWN',
                '2026-04-23',
                '2026-04-23',
                'FALSE',
                500000,
                'Invalid action',
                '',
            ],
        ]);

        $response = $this->actingAsFinanceUser()->post(route('cashflow-projection.entries.import'), [
            'file' => $file,
            'context_year' => 2026,
            'context_month' => 4,
        ]);

        $response->assertRedirect(route('cashflow-projection.entries', [
            'year' => 2026,
            'month' => 4,
        ]));

        $response->assertSessionHas('cashflow_import', function (array $payload): bool {
            return ($payload['summary'] ?? null) === 'Import gagal. Perbaiki file lalu coba lagi.'
                && ($payload['file_name'] ?? null) === 'cashflow_entries_import.xlsx'
                && ($payload['total_rows'] ?? null) === 2
                && ($payload['failed_rows'] ?? null) === 1
                && collect($payload['errors'] ?? [])->contains(function (array $error): bool {
                    return ($error['row'] ?? null) === 3
                        && ($error['column'] ?? null) === 'action_code'
                        && str_contains((string) ($error['message'] ?? ''), 'tidak valid');
                });
        });

        $this->assertDatabaseMissing('cashflow_projection_line_items', [
            'department_id' => $this->linkedOpsDepartment->id,
            'description' => 'Would be created',
        ]);
        $this->assertDatabaseHas('cashflow_projection_line_items', [
            'id' => $this->existingLinkedLineItem->id,
            'description' => 'Existing linked row',
            'source_type' => 'manual',
        ]);
        $this->assertSame(0, CashflowProjectionAuditLog::query()->count());
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_import_rejects_workbook_with_modified_header_order_before_processing_rows(): void
    {
        $headers = self::TEMPLATE_HEADERS;
        [$headers[1], $headers[2]] = [$headers[2], $headers[1]];

        $file = $this->makeWorkbookUpload([
            [
                '',
                2026,
                'WNS',
                'HR',
                'OUT_HR_OPS',
                '2026-04-18',
                '2026-04-18',
                'FALSE',
                2750000,
                'Invalid header workbook',
                '',
            ],
        ], $headers);

        $response = $this->actingAsFinanceUser()->post(route('cashflow-projection.entries.import'), [
            'file' => $file,
            'context_year' => 2026,
            'context_month' => 4,
        ]);

        $response->assertRedirect(route('cashflow-projection.entries', [
            'year' => 2026,
            'month' => 4,
        ]));

        $response->assertSessionHas('cashflow_import', function (array $payload): bool {
            return ($payload['failed_rows'] ?? null) === 0
                && collect($payload['errors'] ?? [])->contains(fn (array $error): bool => ($error['column'] ?? null) === 'template');
        });
    }

    private function actingAsFinanceUser(): self
    {
        return $this->actingAs($this->financeUser)->withSession([
            'current_business_unit_id' => $this->hostBusinessUnit->id,
            'current_business_unit_code' => $this->hostBusinessUnit->code,
            'current_business_unit_name' => $this->hostBusinessUnit->name,
            'current_department_id' => $this->financeDepartment->id,
        ]);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, string>|null  $headers
     */
    private function makeWorkbookUpload(array $rows, ?array $headers = null): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $templateSheet = $spreadsheet->getActiveSheet();
        $templateSheet->setTitle('Template');
        $templateSheet->fromArray($headers ?? self::TEMPLATE_HEADERS, null, 'A1');
        $templateSheet->fromArray($rows, null, 'A2');

        $referenceSheet = $spreadsheet->createSheet();
        $referenceSheet->setTitle('Reference');
        $referenceSheet->setCellValue('A1', 'Do not edit this sheet.');

        $existingEntriesSheet = $spreadsheet->createSheet();
        $existingEntriesSheet->setTitle('Existing Entries');
        $existingEntriesSheet->fromArray(self::TEMPLATE_HEADERS, null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'cashflow-import-test');
        if ($path === false) {
            throw new \RuntimeException('Failed to create temp workbook path.');
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return new UploadedFile(
            $path,
            'cashflow_entries_import.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    private function loadSpreadsheetFromBinary(string $binary): Spreadsheet
    {
        $path = tempnam(sys_get_temp_dir(), 'cashflow-import-response');
        if ($path === false) {
            throw new \RuntimeException('Failed to create temp response path.');
        }

        file_put_contents($path, $binary);

        /** @var Spreadsheet $spreadsheet */
        $spreadsheet = IOFactory::load($path);

        return $spreadsheet;
    }

    /**
     * @param  array<int, array<int, mixed>>  $sheetRows
     * @return array<int, string>
     */
    private function sheetRowValues(array $sheetRows, int $rowNumber): array
    {
        $row = $sheetRows[$rowNumber - 1] ?? [];

        return array_map(fn ($value): string => (string) $value, array_slice($row, 0, count(self::TEMPLATE_HEADERS)));
    }
}
