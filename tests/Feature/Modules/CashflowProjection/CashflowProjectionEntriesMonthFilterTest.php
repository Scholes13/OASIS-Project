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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CashflowProjectionEntriesMonthFilterTest extends TestCase
{
    use RefreshDatabase;

    private BusinessUnit $businessUnit;

    private Department $financeDepartment;

    private Position $financePosition;

    private User $financeUser;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);

        $this->businessUnit = BusinessUnit::create([
            'code' => 'WNS',
            'name' => 'Werkudara Nirwana Sakti',
            'is_active' => true,
        ]);

        $this->financeDepartment = Department::create([
            'business_unit_id' => $this->businessUnit->id,
            'code' => 'FIN',
            'name' => 'Finance',
            'is_active' => true,
        ]);

        $this->financePosition = Position::query()
            ->where('department_id', $this->financeDepartment->id)
            ->where('code', 'STAFF_'.strtoupper($this->financeDepartment->code))
            ->firstOrFail();

        $this->financeUser = User::create([
            'name' => 'Finance User',
            'email' => 'finance.entries@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567890',
            'primary_department_id' => $this->financeDepartment->id,
            'primary_position_id' => $this->financePosition->id,
            'global_role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->financeUser->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->financeDepartment->id,
            'position_id' => $this->financePosition->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $cycle = CashflowProjectionCycle::create([
            'business_unit_id' => $this->businessUnit->id,
            'year' => 2026,
            'status' => 'draft',
            'created_by' => $this->financeUser->id,
            'updated_by' => $this->financeUser->id,
        ]);

        $this->createLineItem($cycle, 'in', '2026-01-10', 300, 'January income');
        $this->createLineItem($cycle, 'out', '2026-01-15', 80, 'January expense');
        $this->createLineItem($cycle, 'in', '2026-03-05', 500, 'March revenue');
        $this->createLineItem($cycle, 'out', '2026-03-18', 120, 'March vendor payment', 'DOC-MARCH-001', 'Vendor March', 'OPERASIONAL');
        $this->createLineItem($cycle, 'in', '2026-03-28', 100, 'March top-up');
    }

    public function test_entries_page_shows_all_accessible_line_items_with_pagination(): void
    {
        $response = $this->actingAs($this->financeUser)->withSession([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
            'current_department_id' => $this->financeDepartment->id,
        ])->get(route('cashflow-projection.entries', [
            'year' => 2026,
            'month' => 3,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('CashflowProjection/Entries')
            ->where('year', 2026)
            ->where('selectedMonth', 3)
            ->has('lineItems.data', 5)
            ->where('lineItems.data.0.transaction_date', '2026-03-28')
            ->where('lineItems.data.1.transaction_date', '2026-03-18')
            ->where('lineItems.data.2.transaction_date', '2026-03-05')
            ->where('lineItems.data.3.transaction_date', '2026-01-15')
            ->where('lineItems.data.4.transaction_date', '2026-01-10')
            ->where('lineItems.meta.total', 5)
            ->where('lineItems.meta.per_page', 25)
        );
    }

    public function test_entries_page_searches_excel_document_vendor_description_and_keterangan(): void
    {
        $response = $this->actingAs($this->financeUser)->withSession([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
            'current_department_id' => $this->financeDepartment->id,
        ])->get(route('cashflow-projection.entries', [
            'year' => 2026,
            'month' => 3,
            'search' => 'Vendor March',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('CashflowProjection/Entries')
            ->where('filters.search', 'Vendor March')
            ->has('lineItems.data', 1)
            ->where('lineItems.data.0.description', 'March vendor payment')
            ->where('lineItems.data.0.no_dokumen', 'DOC-MARCH-001')
            ->where('lineItems.data.0.nama_vendor', 'Vendor March')
            ->where('lineItems.data.0.keterangan', 'OPERASIONAL')
            ->where('lineItems.meta.total', 1)
        );
    }

    public function test_bulk_delete_removes_selected_accessible_entries_and_logs_each_delete(): void
    {
        $ids = CashflowProjectionLineItem::query()
            ->whereIn('description', ['March vendor payment', 'March top-up'])
            ->pluck('id')
            ->all();

        $response = $this->actingAs($this->financeUser)->withSession([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
            'current_department_id' => $this->financeDepartment->id,
        ])->delete(route('cashflow-projection.line-items.bulk-destroy'), [
            'line_item_ids' => $ids,
            'year' => 2026,
            'month' => 3,
        ]);

        $response->assertRedirect(route('cashflow-projection.entries', ['year' => 2026, 'month' => 3]));
        $this->assertDatabaseMissing('cashflow_projection_line_items', ['description' => 'March vendor payment']);
        $this->assertDatabaseMissing('cashflow_projection_line_items', ['description' => 'March top-up']);
        $this->assertDatabaseHas('cashflow_projection_line_items', ['description' => 'March revenue']);
        $this->assertSame(2, CashflowProjectionAuditLog::query()->where('action', 'deleted')->count());
    }

    public function test_backfill_migration_restores_document_and_vendor_from_legacy_import_notes(): void
    {
        $legacy = CashflowProjectionLineItem::query()->where('description', 'March revenue')->firstOrFail();
        $legacy->forceFill([
            'no_dokumen' => null,
            'nama_vendor' => null,
            'notes' => "No Dokumen: HR-02/202605/0016\nVendor: KASBON MEIDA",
        ])->save();

        $migration = include database_path('migrations/2026_06_03_000002_backfill_document_vendor_from_cashflow_projection_notes.php');
        $migration->up();

        $legacy->refresh();
        $this->assertSame('HR-02/202605/0016', $legacy->no_dokumen);
        $this->assertSame('KASBON MEIDA', $legacy->nama_vendor);
    }

    private function createLineItem(
        CashflowProjectionCycle $cycle,
        string $flowType,
        string $transactionDate,
        float $amount,
        string $description,
        ?string $noDokumen = null,
        ?string $namaVendor = null,
        ?string $keterangan = null
    ): CashflowProjectionLineItem {
        return CashflowProjectionLineItem::create([
            'cycle_id' => $cycle->id,
            'department_id' => $this->financeDepartment->id,
            'flow_type' => $flowType,
            'action_code' => $flowType === 'in' ? 'finance_income' : 'finance_expense',
            'transaction_date' => $transactionDate,
            'due_date' => $transactionDate,
            'is_estimated_date' => false,
            'amount' => $amount,
            'description' => $description,
            'keterangan' => $keterangan,
            'no_dokumen' => $noDokumen,
            'nama_vendor' => $namaVendor,
            'notes' => null,
            'source_type' => 'manual',
            'created_by' => $this->financeUser->id,
            'updated_by' => $this->financeUser->id,
        ]);
    }
}
