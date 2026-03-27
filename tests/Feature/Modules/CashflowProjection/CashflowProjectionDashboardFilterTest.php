<?php

namespace Tests\Feature\Modules\CashflowProjection;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionCycle;
use App\Models\Modules\CashflowProjection\CashflowProjectionFinanceInput;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CashflowProjectionDashboardFilterTest extends TestCase
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

        $this->financePosition = Position::where('department_id', $this->financeDepartment->id)
            ->where('code', 'STAFF_'.strtoupper($this->financeDepartment->code))
            ->firstOrFail();

        $this->financeUser = User::create([
            'name' => 'Finance User',
            'email' => 'finance.dashboard@example.com',
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

        CashflowProjectionFinanceInput::create([
            'cycle_id' => $cycle->id,
            'month' => 1,
            'cash_on_hand' => 1000,
            'receivable_estimate' => 100,
            'upcoming_event_revenue_estimate' => 0,
            'capital_injection_estimate' => 0,
            'other_income' => 50,
            'created_by' => $this->financeUser->id,
            'updated_by' => $this->financeUser->id,
        ]);

        CashflowProjectionFinanceInput::create([
            'cycle_id' => $cycle->id,
            'month' => 3,
            'cash_on_hand' => 900,
            'receivable_estimate' => 0,
            'upcoming_event_revenue_estimate' => 0,
            'capital_injection_estimate' => 200,
            'other_income' => 0,
            'created_by' => $this->financeUser->id,
            'updated_by' => $this->financeUser->id,
        ]);

        $this->createLineItem($cycle, 'in', '2026-01-10', 300, 'January income');
        $this->createLineItem($cycle, 'out', '2026-01-15', 80, 'January expense');
        $this->createLineItem($cycle, 'in', '2026-03-05', 500, 'March revenue');
        $this->createLineItem($cycle, 'out', '2026-03-18', 120, 'March vendor payment');
        $this->createLineItem($cycle, 'in', '2026-03-28', 100, 'March top-up');
    }

    public function test_dashboard_month_filter_returns_month_scoped_metrics(): void
    {
        $response = $this->actingAsFinanceUser()->get(route('cashflow-projection.index', [
            'filter' => 'month',
            'year' => 2026,
            'month' => 3,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('CashflowProjection/Index')
            ->where('filters.mode', 'month')
            ->where('filters.year', 2026)
            ->where('filters.month', 3)
            ->where('filters.start_date', '2026-03-01')
            ->where('filters.end_date', '2026-03-31')
            ->where('summary.inflow', 600)
            ->where('summary.outflow', 120)
            ->where('summary.finance_income', 200)
            ->where('summary.net_cashflow', 680)
            ->where('summary.total_balance', 1580)
            ->has('lineItems', 3)
        );
    }

    public function test_dashboard_year_filter_returns_year_scoped_metrics(): void
    {
        $response = $this->actingAsFinanceUser()->get(route('cashflow-projection.index', [
            'filter' => 'year',
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('CashflowProjection/Index')
            ->where('filters.mode', 'year')
            ->where('filters.year', 2026)
            ->where('filters.start_date', '2026-01-01')
            ->where('filters.end_date', '2026-12-31')
            ->where('summary.inflow', 900)
            ->where('summary.outflow', 200)
            ->where('summary.finance_income', 350)
            ->where('summary.net_cashflow', 1050)
            ->where('summary.total_balance', 1580)
            ->has('lineItems', 5)
        );
    }

    public function test_dashboard_custom_range_filter_returns_range_scoped_metrics(): void
    {
        $response = $this->actingAsFinanceUser()->get(route('cashflow-projection.index', [
            'filter' => 'range',
            'year' => 2026,
            'start_date' => '2026-03-05',
            'end_date' => '2026-03-20',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('CashflowProjection/Index')
            ->where('filters.mode', 'range')
            ->where('filters.year', 2026)
            ->where('filters.start_date', '2026-03-05')
            ->where('filters.end_date', '2026-03-20')
            ->where('summary.inflow', 500)
            ->where('summary.outflow', 120)
            ->where('summary.finance_income', 200)
            ->where('summary.net_cashflow', 580)
            ->where('summary.total_balance', 1580)
            ->has('lineItems', 2)
            ->where('lineItems.0.transaction_date', '2026-03-18')
            ->where('lineItems.1.transaction_date', '2026-03-05')
        );
    }

    public function test_dashboard_export_keeps_filtered_summary_but_includes_unfiltered_raw_entries(): void
    {
        $response = $this->actingAsFinanceUser()->get(route('cashflow-projection.export', [
            'filter' => 'month',
            'year' => 2026,
            'month' => 3,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $response->assertHeader('content-disposition', 'attachment; filename=cashflow-projection-bu-only-2026-03.xls');

        $content = $response->streamedContent();

        $this->assertStringContainsString('<Worksheet ss:Name="Summary">', $content);
        $this->assertStringContainsString('<Worksheet ss:Name="Daily Movement">', $content);
        $this->assertStringContainsString('<Worksheet ss:Name="Raw Entries">', $content);
        $this->assertStringContainsString('<Worksheet ss:Name="Finance Inputs">', $content);
        $this->assertStringContainsString('Selected Period', $content);
        $this->assertStringContainsString('Mar 2026', $content);
        $this->assertStringContainsString('2026-03-05', $content);
        $this->assertStringContainsString('2026-01-10', $content);
        $this->assertStringContainsString('January income', $content);
        $this->assertStringContainsString('March revenue', $content);
    }

    private function actingAsFinanceUser(): self
    {
        return $this->actingAs($this->financeUser)->withSession([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
            'current_department_id' => $this->financeDepartment->id,
        ]);
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
