<?php

namespace Tests\Feature\Modules\CashflowProjection;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionCycle;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CashflowProjectionImportPreviewTest extends TestCase
{
    use RefreshDatabase;

    private BusinessUnit $businessUnit;

    private Department $financeDepartment;

    private Department $hrDepartment;

    private Department $tepDepartment;

    private Department $rootDepartment;

    private Department $childDepartment;

    private User $financeUser;

    private CashflowProjectionCycle $cycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->businessUnit = BusinessUnit::create(['code' => 'WNS', 'name' => 'Werkudara Nirwana Sakti', 'is_active' => true]);
        $this->financeDepartment = Department::create(['business_unit_id' => $this->businessUnit->id, 'code' => 'CFC', 'name' => 'Core Finance', 'is_active' => true]);
        $this->hrDepartment = Department::create(['business_unit_id' => $this->businessUnit->id, 'code' => 'HR', 'name' => 'Human Resources', 'is_active' => true]);
        $this->tepDepartment = Department::create(['business_unit_id' => $this->businessUnit->id, 'code' => 'TEP', 'name' => 'TEP', 'is_active' => true]);
        $this->rootDepartment = Department::create(['business_unit_id' => $this->businessUnit->id, 'code' => 'SM', 'name' => 'Sales Marketing', 'is_active' => true]);
        $this->childDepartment = Department::create(['business_unit_id' => $this->businessUnit->id, 'parent_department_id' => $this->rootDepartment->id, 'code' => 'BS', 'name' => 'Business Solutions', 'is_active' => true]);

        $position = Position::query()->where('department_id', $this->financeDepartment->id)->where('code', 'STAFF_CFC')->firstOrFail();
        $this->financeUser = User::create([
            'name' => 'Finance Preview User',
            'email' => 'finance.preview@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567891',
            'primary_department_id' => $this->financeDepartment->id,
            'primary_position_id' => $position->id,
            'global_role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->financeUser->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->financeDepartment->id,
            'position_id' => $position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->cycle = CashflowProjectionCycle::create([
            'business_unit_id' => $this->businessUnit->id,
            'year' => 2026,
            'status' => 'draft',
            'created_by' => $this->financeUser->id,
            'updated_by' => $this->financeUser->id,
        ]);
    }

    public function test_preview_returns_new_ready_row_from_data_cfc_sheet(): void
    {
        $response = $this->actingAsFinanceUser()->postJson(route('cashflow-projection.entries.import-preview'), [
            'file' => $this->makeWorkbookUpload([
                ['MAY', '26-May-26', 'HR-02/202605/0016', 'KASBON MEIDA', 'Pengajuan kasbon movieday', '750,000', '19-May-26', 'KAS BON OPERASIONAL', 'WNS'],
            ]),
        ]);

        $response->assertOk()
            ->assertJsonPath('summary.ready_rows', 1)
            ->assertJsonPath('rows.0.status', 'new')
            ->assertJsonPath('rows.0.department_code', 'HR')
            ->assertJsonPath('rows.0.action_code', 'OUT_HR_OPS')
            ->assertJsonPath('rows.0.flow_type', 'out')
            ->assertJsonPath('rows.0.amount', 750000)
            ->assertJsonPath('rows.0.keterangan', 'KAS BON OPERASIONAL');
    }

    public function test_preview_marks_update_no_change_need_review_and_invalid_rows(): void
    {
        CashflowProjectionLineItem::create([
            'cycle_id' => $this->cycle->id,
            'department_id' => $this->hrDepartment->id,
            'flow_type' => 'out',
            'action_code' => 'OUT_HR_OPS',
            'transaction_date' => '2026-05-26',
            'due_date' => '2026-05-19',
            'is_estimated_date' => false,
            'amount' => 750000,
            'description' => 'Existing HR row',
            'keterangan' => 'OPERASIONAL',
            'notes' => "No Dokumen: HR-02/202605/0016\nVendor: KASBON MEIDA",
            'source_type' => 'manual',
            'created_by' => $this->financeUser->id,
            'updated_by' => $this->financeUser->id,
        ]);

        $response = $this->actingAsFinanceUser()->postJson(route('cashflow-projection.entries.import-preview'), [
            'file' => $this->makeWorkbookUpload([
                ['MAY', '26-May-26', 'HR-02/202605/0016', 'KASBON MEIDA', 'Existing HR row', '800,000', '19-May-26', 'OPERASIONAL', 'WNS'],
                ['MAY', '26-May-26', 'HR-02/202605/0016', 'KASBON MEIDA', 'Existing HR row', '750,000', '19-May-26', 'OPERASIONAL', 'WNS'],
                ['MAY', '26-May-26', '', 'UNKNOWN', 'Biaya tanpa kode', '100,000', '19-May-26', 'OPERASIONAL', 'WNS'],
                ['MAY', '26-May-26', '', 'ROOT', 'WNS - SM - Root row', '100,000', '19-May-26', 'OPERASIONAL', 'WNS'],
            ]),
        ]);

        $response->assertOk()
            ->assertJsonPath('rows.0.status', 'update')
            ->assertJsonPath('rows.0.changes.0.field', 'amount')
            ->assertJsonPath('rows.1.status', 'no_change')
            ->assertJsonPath('rows.2.status', 'need_review')
            ->assertJsonPath('rows.2.errors.0.field', 'department_code')
            ->assertJsonPath('rows.3.status', 'invalid')
            ->assertJsonPath('rows.3.errors.0.field', 'department_code');
    }

    public function test_preview_marks_multiple_description_matches_as_need_review(): void
    {
        foreach (['Duplicate HR row', 'Duplicate HR row'] as $description) {
            CashflowProjectionLineItem::create([
                'cycle_id' => $this->cycle->id,
                'department_id' => $this->hrDepartment->id,
                'flow_type' => 'out',
                'action_code' => 'OUT_HR_OPS',
                'transaction_date' => '2026-05-26',
                'due_date' => '2026-05-19',
                'is_estimated_date' => false,
                'amount' => 750000,
                'description' => $description,
                'keterangan' => 'OPERASIONAL',
                'source_type' => 'manual',
                'created_by' => $this->financeUser->id,
                'updated_by' => $this->financeUser->id,
            ]);
        }

        $response = $this->actingAsFinanceUser()->postJson(route('cashflow-projection.entries.import-preview'), [
            'file' => $this->makeWorkbookUpload([
                ['MAY', '26-May-26', 'HR-02/202605/0016', 'KASBON MEIDA', 'Duplicate HR row', '900,000', '19-May-26', 'OPERASIONAL', 'WNS'],
            ]),
        ]);

        $response->assertOk()
            ->assertJsonPath('rows.0.status', 'need_review')
            ->assertJsonPath('rows.0.errors.0.field', 'description');
    }

    public function test_preview_accepts_official_template_workbook(): void
    {
        $existing = CashflowProjectionLineItem::create([
            'cycle_id' => $this->cycle->id,
            'department_id' => $this->hrDepartment->id,
            'flow_type' => 'out',
            'action_code' => 'OUT_HR_OPS',
            'transaction_date' => '2026-05-26',
            'due_date' => '2026-05-19',
            'is_estimated_date' => false,
            'amount' => 750000,
            'description' => 'Existing official row',
            'keterangan' => 'OPERASIONAL',
            'source_type' => 'manual',
            'created_by' => $this->financeUser->id,
            'updated_by' => $this->financeUser->id,
        ]);

        $response = $this->actingAsFinanceUser()->postJson(route('cashflow-projection.entries.import-preview'), [
            'file' => $this->makeTemplateWorkbookUpload([
                [$existing->id, 2026, 'WNS', 'HR', 'OUT_HR_OPS', '2026-05-26', '2026-05-19', 'FALSE', 800000, 'Existing official row', 'OPERASIONAL', 'Updated note'],
                ['', 2026, 'WNS', 'HR', 'OUT_HR_OPS', '2026-05-28', '2026-05-28', 'FALSE', 120000, 'New official row', 'OPERASIONAL', 'New note'],
            ]),
        ]);

        $response->assertOk()
            ->assertJsonPath('summary.ready_rows', 2)
            ->assertJsonPath('rows.0.status', 'update')
            ->assertJsonPath('rows.0.match.line_item_id', $existing->id)
            ->assertJsonPath('rows.1.status', 'new')
            ->assertJsonPath('rows.1.department_code', 'HR');
    }

    public function test_preview_accepts_one_sheet_official_template_workbook(): void
    {
        $response = $this->actingAsFinanceUser()->postJson(route('cashflow-projection.entries.import-preview'), [
            'file' => $this->makeTemplateWorkbookUpload([
                ['', 2026, 'WNS', 'HR', 'OUT_HR_OPS', '2026-05-28', '2026-05-28', 'FALSE', 120000, 'New one sheet row', 'OPERASIONAL', 'New note'],
            ], false),
        ]);

        $response->assertOk()
            ->assertJsonPath('summary.ready_rows', 1)
            ->assertJsonPath('rows.0.status', 'new')
            ->assertJsonPath('rows.0.description', 'New one sheet row');
    }

    private function actingAsFinanceUser(): self
    {
        return $this->actingAs($this->financeUser)->withSession([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_department_id' => $this->financeDepartment->id,
        ]);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function makeWorkbookUpload(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data CFC');
        $sheet->fromArray(['BULAN', 'TGL BAYAR', 'NO DOKUMEN', 'NAMA VENDOR', 'DESKRIPSI', 'NOMINAL', 'DUE DATE', 'KETERANGAN', 'ENTITAS'], null, 'A3');
        $sheet->fromArray($rows, null, 'A4');

        $path = tempnam(sys_get_temp_dir(), 'cashflow-preview-test');
        if ($path === false) {
            throw new \RuntimeException('Failed to create temp workbook path.');
        }

        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'cashflow_preview.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function makeTemplateWorkbookUpload(array $rows, bool $withReferenceSheets = true): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $templateSheet = $spreadsheet->getActiveSheet();
        $templateSheet->setTitle('Template');
        $templateSheet->fromArray([
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
        ], null, 'A1');
        $templateSheet->fromArray($rows, null, 'A2');

        if ($withReferenceSheets) {
            $spreadsheet->createSheet()->setTitle('Reference');
            $spreadsheet->createSheet()->setTitle('Existing Entries');
        }

        $path = tempnam(sys_get_temp_dir(), 'cashflow-template-preview-test');
        if ($path === false) {
            throw new \RuntimeException('Failed to create temp workbook path.');
        }

        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'cashflow_template_preview.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
