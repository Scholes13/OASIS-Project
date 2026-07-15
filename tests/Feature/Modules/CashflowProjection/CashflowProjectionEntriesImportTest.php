<?php

namespace Tests\Feature\Modules\CashflowProjection;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionCycle;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use App\Models\Modules\CashflowProjection\CashflowProjectionLinkedUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
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
        'keterangan',
        'notes',
    ];

    private BusinessUnit $hostBusinessUnit;

    private BusinessUnit $linkedBusinessUnit;

    private Department $financeDepartment;

    private Department $hrDepartment;

    private Department $rootDepartment;

    private Department $childDepartment;

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

        $this->rootDepartment = Department::create([
            'business_unit_id' => $this->hostBusinessUnit->id,
            'code' => 'SM',
            'name' => 'Sales Marketing',
            'is_active' => true,
        ]);

        $this->childDepartment = Department::create([
            'business_unit_id' => $this->hostBusinessUnit->id,
            'parent_department_id' => $this->rootDepartment->id,
            'code' => 'BSD',
            'name' => 'Business Development',
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

        $this->financeUser->businessUnits()->create([
            'business_unit_id' => $this->linkedBusinessUnit->id,
            'department_id' => $this->linkedOpsDepartment->id,
            'position_id' => $this->financePosition->id,
            'is_primary' => false,
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
        $mayLineItem = CashflowProjectionLineItem::query()->create([
            'cycle_id' => $this->existingHostLineItem->cycle_id,
            'department_id' => $this->hrDepartment->id,
            'flow_type' => 'out',
            'action_code' => 'OUT_HR_OPS',
            'transaction_date' => '2026-05-12',
            'due_date' => '2026-05-12',
            'is_estimated_date' => false,
            'amount' => 875000,
            'description' => 'Existing May row',
            'source_type' => 'manual',
            'created_by' => $this->financeUser->id,
            'updated_by' => $this->financeUser->id,
        ]);

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

        $templateRows = $spreadsheet->getSheetByName('Template')?->toArray() ?? [];
        $this->assertTrue(collect($templateRows)->contains(fn (array $row): bool => (string) ($row[0] ?? '') === (string) $this->existingHostLineItem->id));
        $this->assertTrue(collect($templateRows)->contains(fn (array $row): bool => (string) ($row[0] ?? '') === (string) $mayLineItem->id));

        $existingRows = $spreadsheet->getSheetByName('Existing Entries')?->toArray() ?? [];
        $this->assertTrue(collect($existingRows)->contains(fn (array $row): bool => (string) ($row[0] ?? '') === (string) $this->existingHostLineItem->id));
        $this->assertTrue(collect($existingRows)->contains(fn (array $row): bool => (string) ($row[0] ?? '') === (string) $this->existingLinkedLineItem->id));
        $this->assertTrue(collect($existingRows)->contains(fn (array $row): bool => (string) ($row[0] ?? '') === (string) $mayLineItem->id));
    }

    public function test_download_import_template_excludes_root_departments_with_active_children(): void
    {
        $response = $this->actingAsFinanceUser()->get(route('cashflow-projection.entries.import-template', [
            'year' => 2026,
            'month' => 4,
        ]));

        $response->assertOk();

        $spreadsheet = $this->loadSpreadsheetFromBinary($response->streamedContent());
        $referenceRows = $spreadsheet->getSheetByName('Reference')?->toArray() ?? [];

        $this->assertFalse(collect($referenceRows)->contains(fn (array $row): bool => ($row[1] ?? null) === 'SM'));
        $this->assertTrue(collect($referenceRows)->contains(fn (array $row): bool => ($row[1] ?? null) === 'BSD'));
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
