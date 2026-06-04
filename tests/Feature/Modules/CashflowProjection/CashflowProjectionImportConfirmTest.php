<?php

namespace Tests\Feature\Modules\CashflowProjection;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionAuditLog;
use App\Models\Modules\CashflowProjection\CashflowProjectionCycle;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashflowProjectionImportConfirmTest extends TestCase
{
    use RefreshDatabase;

    private BusinessUnit $businessUnit;

    private Department $financeDepartment;

    private Department $hrDepartment;

    private User $financeUser;

    private CashflowProjectionCycle $cycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->businessUnit = BusinessUnit::create(['code' => 'WNS', 'name' => 'Werkudara Nirwana Sakti', 'is_active' => true]);
        $this->financeDepartment = Department::create(['business_unit_id' => $this->businessUnit->id, 'code' => 'CFC', 'name' => 'Core Finance', 'is_active' => true]);
        $this->hrDepartment = Department::create(['business_unit_id' => $this->businessUnit->id, 'code' => 'HR', 'name' => 'Human Resources', 'is_active' => true]);

        $position = Position::query()->where('department_id', $this->financeDepartment->id)->where('code', 'STAFF_CFC')->firstOrFail();
        $this->financeUser = User::create([
            'name' => 'Finance Confirm User',
            'email' => 'finance.confirm@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567892',
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

    public function test_confirm_creates_updates_skips_no_change_and_logs_audit(): void
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
            'description' => 'Existing HR row',
            'keterangan' => 'OPERASIONAL',
            'notes' => 'Existing note',
            'source_type' => 'manual',
            'created_by' => $this->financeUser->id,
            'updated_by' => $this->financeUser->id,
        ]);

        $response = $this->actingAsFinanceUser()->postJson(route('cashflow-projection.entries.import-confirm'), [
            'context_year' => 2026,
            'context_month' => 5,
            'rows' => [
                $this->row('new', 'New HR row', 500000),
                array_merge($this->row('update', 'Existing HR row', 800000), [
                    'match' => ['line_item_id' => $existing->id],
                ]),
                array_merge($this->row('no_change', 'Existing HR row', 750000), [
                    'match' => ['line_item_id' => $existing->id],
                ]),
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('summary.created_rows', 1)
            ->assertJsonPath('summary.updated_rows', 1)
            ->assertJsonPath('summary.skipped_rows', 1);

        $this->assertDatabaseHas('cashflow_projection_line_items', [
            'description' => 'New HR row',
            'keterangan' => 'OPERASIONAL',
            'no_dokumen' => 'HR-02/202605/0016',
            'nama_vendor' => 'KASBON MEIDA',
            'source_type' => 'import',
            'created_by' => $this->financeUser->id,
        ]);
        $this->assertDatabaseHas('cashflow_projection_line_items', [
            'id' => $existing->id,
            'amount' => 800000,
            'updated_by' => $this->financeUser->id,
        ]);
        $this->assertSame(2, CashflowProjectionAuditLog::query()->count());
        $this->assertDatabaseHas('cashflow_projection_audit_logs', ['action' => 'created']);
        $this->assertDatabaseHas('cashflow_projection_audit_logs', ['action' => 'updated']);
    }

    public function test_confirm_rejects_unresolved_rows_without_mutating(): void
    {
        $response = $this->actingAsFinanceUser()->postJson(route('cashflow-projection.entries.import-confirm'), [
            'context_year' => 2026,
            'context_month' => 5,
            'rows' => [
                $this->row('need_review', 'Needs review', 100000),
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Import masih memiliki row yang perlu review atau invalid.');

        $this->assertDatabaseCount('cashflow_projection_line_items', 0);
    }

    public function test_confirm_rejects_action_code_not_allowed_for_department(): void
    {
        $response = $this->actingAsFinanceUser()->postJson(route('cashflow-projection.entries.import-confirm'), [
            'context_year' => 2026,
            'context_month' => 5,
            'rows' => [
                array_merge($this->row('new', 'Tampered action', 500000), [
                    'action_code' => 'OUT_ACC_PAJAK',
                ]),
            ],
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseCount('cashflow_projection_line_items', 0);
    }

    public function test_confirm_rejects_root_department_with_active_children(): void
    {
        $root = Department::create(['business_unit_id' => $this->businessUnit->id, 'code' => 'SM', 'name' => 'Sales Marketing', 'is_active' => true]);
        Department::create(['business_unit_id' => $this->businessUnit->id, 'parent_department_id' => $root->id, 'code' => 'BS', 'name' => 'Business Solutions', 'is_active' => true]);

        $response = $this->actingAsFinanceUser()->postJson(route('cashflow-projection.entries.import-confirm'), [
            'context_year' => 2026,
            'context_month' => 5,
            'rows' => [
                array_merge($this->row('new', 'Root row', 500000), [
                    'department_code' => 'SM',
                    'action_code' => 'OUT_SM_OPS',
                ]),
            ],
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseCount('cashflow_projection_line_items', 0);
    }

    public function test_confirm_allows_rows_outside_selected_context_month_because_payment_date_drives_period(): void
    {
        $response = $this->actingAsFinanceUser()->postJson(route('cashflow-projection.entries.import-confirm'), [
            'context_year' => 2026,
            'context_month' => 6,
            'rows' => [
                $this->row('new', 'Wrong month row', 500000),
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('summary.created_rows', 1);
        $this->assertDatabaseHas('cashflow_projection_line_items', [
            'description' => 'Wrong month row',
        ]);
    }

    public function test_confirm_accepts_reviewed_sample_rows_from_different_page_month(): void
    {
        Department::create(['business_unit_id' => $this->businessUnit->id, 'code' => 'ACC', 'name' => 'Accounting', 'is_active' => true]);
        Department::create(['business_unit_id' => $this->businessUnit->id, 'code' => 'TEP', 'name' => 'Tour & Event Planning', 'is_active' => true]);
        Department::create(['business_unit_id' => $this->businessUnit->id, 'code' => 'BAS', 'name' => 'Business Administrative Services', 'is_active' => true]);

        $rows = [
            $this->row('new', 'PENGAJUAN KASBON MOVIEDAY IN TGL 19 MEI 26', 750000),
            array_merge($this->row('new', 'TOPUP RESERVASI MG HOLIDAY', 4135000), ['department_code' => 'ACC', 'action_code' => 'OUT_ACC_PAJAK']),
            array_merge($this->row('new', 'WNS - IT - BIAYA PEMB TAGIHAN INTERNET IFORTE BULAN MEI', 4995000), ['department_code' => 'ACC', 'action_code' => 'OUT_ACC_PAJAK']),
            array_merge($this->row('new', 'WNS - TEP - FAMTRIP JCWF - BIAYA PEMB SERVICE FEE TIKET', 1000000), ['department_code' => 'TEP', 'action_code' => 'OUT_TEP_OPS']),
            array_merge($this->row('new', 'WNS - AG - BIAYA PEMB SERVICE FEE TIKET PESAWAT', 1000000), ['department_code' => 'ACC', 'action_code' => 'OUT_ACC_PAJAK']),
            array_merge($this->row('new', 'WNS - HRD - AUDIT TAKSHAKA - BIAYA PEMB TIKET PESAWAT', 1000000), ['department_code' => 'ACC', 'action_code' => 'OUT_ACC_PAJAK']),
            array_merge($this->row('new', 'TEE; 2026; JUNE 2026; 003-01-TEE-MAY-2026', 1000000), ['department_code' => 'BAS', 'action_code' => 'OUT_BAS_OPS']),
            array_merge($this->row('new', 'TEE - GA - BIAYA PEMB UNTUK DONASI KAMBING QURBAN', 1000000), ['department_code' => 'BAS', 'action_code' => 'OUT_BAS_OPS']),
            array_merge($this->row('new', 'TEE; 2026; JUNE 2026; 003-01-TEE-MAY-2026 - SECOND', 1000000), ['department_code' => 'BAS', 'action_code' => 'OUT_BAS_OPS']),
        ];

        $response = $this->actingAsFinanceUser()->postJson(route('cashflow-projection.entries.import-confirm'), [
            'context_year' => 2026,
            'context_month' => 6,
            'rows' => $rows,
        ]);

        $response->assertOk()
            ->assertJsonPath('summary.created_rows', 9);
        $this->assertDatabaseCount('cashflow_projection_line_items', 9);
    }

    private function actingAsFinanceUser(): self
    {
        return $this->actingAs($this->financeUser)->withSession([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_department_id' => $this->financeDepartment->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(string $status, string $description, float $amount): array
    {
        return [
            'status' => $status,
            'business_unit_code' => 'WNS',
            'department_code' => 'HR',
            'action_code' => 'OUT_HR_OPS',
            'flow_type' => 'out',
            'transaction_date' => '2026-05-26',
            'due_date' => '2026-05-19',
            'amount' => $amount,
            'description' => $description,
            'keterangan' => 'OPERASIONAL',
            'no_dokumen' => 'HR-02/202605/0016',
            'nama_vendor' => 'KASBON MEIDA',
            'notes' => 'Import note',
        ];
    }
}
