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

class CashflowProjectionAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private BusinessUnit $businessUnit;

    private Department $financeDepartment;

    private Department $hrDepartment;

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
            'code' => 'CFC',
            'name' => 'Core Finance',
            'is_active' => true,
        ]);

        $this->hrDepartment = Department::create([
            'business_unit_id' => $this->businessUnit->id,
            'code' => 'HR',
            'name' => 'Human Resources',
            'is_active' => true,
        ]);

        $this->financePosition = Position::query()
            ->where('department_id', $this->financeDepartment->id)
            ->where('code', 'STAFF_'.strtoupper($this->financeDepartment->code))
            ->firstOrFail();

        $this->financeUser = User::create([
            'name' => 'Finance User',
            'email' => 'finance.audit@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '083333333333',
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
    }

    public function test_creating_line_item_writes_audit_log_and_attribution_payload(): void
    {
        $this->actingAsFinanceUser()->post(route('cashflow-projection.line-items.store'), [
            'year' => 2026,
            'department_id' => $this->hrDepartment->id,
            'action_code' => 'OUT_HR_OPS',
            'transaction_date' => '2026-03-12',
            'due_date' => '2026-03-12',
            'is_estimated_date' => false,
            'amount' => 1000000,
            'description' => 'Audit create',
            'notes' => 'Created by finance',
        ])->assertRedirect();

        $lineItem = CashflowProjectionLineItem::query()->firstOrFail();

        $this->assertDatabaseHas('cashflow_projection_audit_logs', [
            'auditable_type' => 'line_item',
            'auditable_id' => $lineItem->id,
            'action' => 'created',
            'actor_user_id' => $this->financeUser->id,
            'actor_department_id' => $this->financeDepartment->id,
            'department_id' => $this->hrDepartment->id,
        ]);

        $this->actingAsFinanceUser()->get(route('cashflow-projection.entries', [
            'year' => 2026,
            'month' => 3,
        ]))->assertInertia(fn (Assert $page) => $page
            ->component('CashflowProjection/Entries')
            ->where('lineItems.0.creator_name', 'Finance User')
            ->where('lineItems.0.creator_department_label', 'Core Finance')
            ->where('lineItems.0.has_edit_history', false)
            ->where('lineItems.0.updater_name', 'Finance User')
            ->where('lineItems.0.updater_department_label', 'Core Finance')
        );
    }

    public function test_updating_line_item_writes_second_append_only_audit_log(): void
    {
        $cycle = CashflowProjectionCycle::create([
            'business_unit_id' => $this->businessUnit->id,
            'year' => 2026,
            'status' => 'draft',
            'created_by' => $this->financeUser->id,
            'updated_by' => $this->financeUser->id,
        ]);

        $lineItem = CashflowProjectionLineItem::create([
            'cycle_id' => $cycle->id,
            'department_id' => $this->hrDepartment->id,
            'flow_type' => 'out',
            'action_code' => 'OUT_HR_OPS',
            'transaction_date' => '2026-03-12',
            'due_date' => '2026-03-12',
            'is_estimated_date' => false,
            'amount' => 1000000,
            'description' => 'Original value',
            'notes' => 'Initial note',
            'source_type' => 'manual',
            'created_by' => $this->financeUser->id,
            'updated_by' => $this->financeUser->id,
        ]);

        CashflowProjectionAuditLog::query()->create([
            'auditable_type' => 'line_item',
            'auditable_id' => $lineItem->id,
            'action' => 'created',
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->hrDepartment->id,
            'actor_user_id' => $this->financeUser->id,
            'actor_user_name' => $this->financeUser->name,
            'actor_department_id' => $this->financeDepartment->id,
            'actor_department_label' => $this->financeDepartment->name,
            'summary' => 'CREATED line item HR OUT_HR_OPS',
            'old_values' => null,
            'new_values' => [
                'department_id' => $this->hrDepartment->id,
                'action_code' => 'OUT_HR_OPS',
                'amount' => 1000000,
            ],
            'created_at' => now()->subMinute(),
        ]);

        $this->actingAsFinanceUser()->patch(route('cashflow-projection.line-items.update', [
            'lineItem' => $lineItem->id,
        ]), [
            'year' => 2026,
            'department_id' => $this->hrDepartment->id,
            'action_code' => 'OUT_HR_OPS',
            'transaction_date' => '2026-03-15',
            'due_date' => '2026-03-15',
            'is_estimated_date' => true,
            'amount' => 1750000,
            'description' => 'Updated value',
            'notes' => 'Updated note',
        ])->assertRedirect();

        $this->assertSame(2, \App\Models\Modules\CashflowProjection\CashflowProjectionAuditLog::query()->count());
        $this->assertDatabaseHas('cashflow_projection_audit_logs', [
            'auditable_type' => 'line_item',
            'auditable_id' => $lineItem->id,
            'action' => 'updated',
        ]);

        $this->actingAsFinanceUser()->get(route('cashflow-projection.entries', [
            'year' => 2026,
            'month' => 3,
        ]))->assertInertia(fn (Assert $page) => $page
            ->component('CashflowProjection/Entries')
            ->where('lineItems.0.creator_name', 'Finance User')
            ->where('lineItems.0.updater_name', 'Finance User')
            ->where('lineItems.0.has_edit_history', true)
        );
    }

    public function test_finance_input_create_and_update_write_audit_logs(): void
    {
        $this->actingAsFinanceUser()->post(route('cashflow-projection.finance-inputs.upsert'), [
            'year' => 2026,
            'month' => 3,
            'cash_on_hand' => 1000000,
            'receivable_estimate' => 500000,
            'upcoming_event_revenue_estimate' => 100000,
            'capital_injection_estimate' => 200000,
            'other_income' => 150000,
        ])->assertRedirect();

        $this->actingAsFinanceUser()->post(route('cashflow-projection.finance-inputs.upsert'), [
            'year' => 2026,
            'month' => 3,
            'cash_on_hand' => 1200000,
            'receivable_estimate' => 500000,
            'upcoming_event_revenue_estimate' => 150000,
            'capital_injection_estimate' => 300000,
            'other_income' => 150000,
        ])->assertRedirect();

        $this->assertDatabaseHas('cashflow_projection_audit_logs', [
            'auditable_type' => 'finance_input',
            'action' => 'created',
            'actor_user_id' => $this->financeUser->id,
        ]);

        $this->assertDatabaseHas('cashflow_projection_audit_logs', [
            'auditable_type' => 'finance_input',
            'action' => 'updated',
            'actor_user_id' => $this->financeUser->id,
        ]);

        $this->actingAsFinanceUser()->get(route('cashflow-projection.settings', [
            'year' => 2026,
            'month' => 3,
        ]))->assertInertia(fn (Assert $page) => $page
            ->component('CashflowProjection/Settings')
            ->where('financeInputs.0.creator_name', 'Finance User')
            ->where('financeInputs.0.updater_name', 'Finance User')
            ->where('financeInputs.0.updater_department_label', 'Core Finance')
        );
    }

    public function test_invalid_action_department_pair_returns_validation_error(): void
    {
        $response = $this->actingAsFinanceUser()
            ->from(route('cashflow-projection.entries'))
            ->post(route('cashflow-projection.line-items.store'), [
                'year' => 2026,
                'department_id' => $this->hrDepartment->id,
                'action_code' => 'OUT_CFC_OPS',
                'transaction_date' => '2026-03-12',
                'due_date' => '2026-03-12',
                'is_estimated_date' => false,
                'amount' => 800000,
                'description' => 'Invalid pair',
                'notes' => null,
            ]);

        $response->assertRedirect(route('cashflow-projection.entries'));
        $response->assertSessionHasErrors('action_code');
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
}
