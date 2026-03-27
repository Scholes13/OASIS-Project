<?php

namespace Tests\Feature\Modules\CashflowProjection;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
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
        $this->createLineItem($cycle, 'out', '2026-03-18', 120, 'March vendor payment');
        $this->createLineItem($cycle, 'in', '2026-03-28', 100, 'March top-up');
    }

    public function test_entries_page_only_shows_line_items_for_selected_month(): void
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
            ->has('lineItems', 3)
            ->where('lineItems.0.transaction_date', '2026-03-28')
            ->where('lineItems.1.transaction_date', '2026-03-18')
            ->where('lineItems.2.transaction_date', '2026-03-05')
        );
    }

    private function createLineItem(
        CashflowProjectionCycle $cycle,
        string $flowType,
        string $transactionDate,
        float $amount,
        string $description
    ): void {
        CashflowProjectionLineItem::create([
            'cycle_id' => $cycle->id,
            'department_id' => $this->financeDepartment->id,
            'flow_type' => $flowType,
            'action_code' => $flowType === 'in' ? 'finance_income' : 'finance_expense',
            'transaction_date' => $transactionDate,
            'due_date' => $transactionDate,
            'is_estimated_date' => false,
            'amount' => $amount,
            'description' => $description,
            'notes' => null,
            'source_type' => 'manual',
            'created_by' => $this->financeUser->id,
            'updated_by' => $this->financeUser->id,
        ]);
    }
}
