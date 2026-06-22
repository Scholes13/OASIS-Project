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

    private Position $hrHeadPosition;

    private User $financeUser;

    private User $hrHeadUser;

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

        $this->hrHeadPosition = Position::query()
            ->where('department_id', $this->hrDepartment->id)
            ->where(function ($query) {
                $query->where('level', 'hod')
                    ->orWhere('access_level', 'department_head');
            })
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

        $this->hrHeadUser = User::create([
            'name' => 'HR Head',
            'email' => 'hr.head.audit@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '084444444444',
            'primary_department_id' => $this->hrDepartment->id,
            'primary_position_id' => $this->hrHeadPosition->id,
            'global_role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->hrHeadUser->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->hrDepartment->id,
            'position_id' => $this->hrHeadPosition->id,
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
            'keterangan' => 'OPERASIONAL',
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

        $this->assertSame(
            'OPERASIONAL',
            CashflowProjectionAuditLog::query()->where('action', 'created')->first()?->new_values['keterangan'] ?? null
        );

        $this->actingAsFinanceUser()->get(route('cashflow-projection.entries', [
            'year' => 2026,
            'month' => 3,
        ]))->assertInertia(fn (Assert $page) => $page
            ->component('CashflowProjection/Entries')
            ->where('lineItems.data.0.creator_name', 'Finance User')
            ->where('lineItems.data.0.creator_department_label', 'Core Finance')
            ->where('lineItems.data.0.has_edit_history', false)
            ->where('lineItems.data.0.updater_name', 'Finance User')
            ->where('lineItems.data.0.updater_department_label', 'Core Finance')
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
            'keterangan' => 'ORIGINAL KETERANGAN',
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
                'keterangan' => 'ORIGINAL KETERANGAN',
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
            'keterangan' => 'UPDATED KETERANGAN',
            'notes' => 'Updated note',
        ])->assertRedirect();

        $this->assertSame(2, \App\Models\Modules\CashflowProjection\CashflowProjectionAuditLog::query()->count());
        $this->assertDatabaseHas('cashflow_projection_audit_logs', [
            'auditable_type' => 'line_item',
            'auditable_id' => $lineItem->id,
            'action' => 'updated',
        ]);

        $updatedLog = CashflowProjectionAuditLog::query()->where('action', 'updated')->firstOrFail();
        $this->assertSame('ORIGINAL KETERANGAN', $updatedLog->old_values['keterangan'] ?? null);
        $this->assertSame('UPDATED KETERANGAN', $updatedLog->new_values['keterangan'] ?? null);

        $this->actingAsFinanceUser()->get(route('cashflow-projection.entries', [
            'year' => 2026,
            'month' => 3,
        ]))->assertInertia(fn (Assert $page) => $page
            ->component('CashflowProjection/Entries')
            ->where('lineItems.data.0.creator_name', 'Finance User')
            ->where('lineItems.data.0.updater_name', 'Finance User')
            ->where('lineItems.data.0.has_edit_history', true)
        );
    }

    public function test_deleting_visible_line_item_writes_deleted_audit_log_and_redirects_to_entries_context(): void
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
            'amount' => 1250000,
            'description' => 'Delete me',
            'notes' => 'Delete audit test',
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
                'amount' => 1250000,
            ],
            'created_at' => now()->subMinute(),
        ]);

        $this->actingAsFinanceUser()
            ->from(route('cashflow-projection.entries', [
                'year' => 2026,
                'month' => 3,
            ]))
            ->delete(route('cashflow-projection.line-items.destroy', [
                'lineItem' => $lineItem->id,
            ]), [
                'year' => 2026,
                'month' => 3,
            ])
            ->assertRedirect(route('cashflow-projection.entries', [
                'year' => 2026,
                'month' => 3,
            ]));

        $this->assertDatabaseMissing('cashflow_projection_line_items', [
            'id' => $lineItem->id,
        ]);

        $this->assertSame(2, CashflowProjectionAuditLog::query()->count());
        $this->assertDatabaseHas('cashflow_projection_audit_logs', [
            'auditable_type' => 'line_item',
            'auditable_id' => $lineItem->id,
            'action' => 'deleted',
            'actor_user_id' => $this->financeUser->id,
            'actor_department_id' => $this->financeDepartment->id,
            'department_id' => $this->hrDepartment->id,
        ]);
    }

    public function test_deleting_line_item_outside_visible_scope_is_forbidden(): void
    {
        $otherBusinessUnit = BusinessUnit::create([
            'code' => 'EXT',
            'name' => 'External Unit',
            'is_active' => true,
        ]);

        $otherDepartment = Department::create([
            'business_unit_id' => $otherBusinessUnit->id,
            'code' => 'EXTFIN',
            'name' => 'External Finance',
            'is_active' => true,
        ]);

        $otherPosition = Position::query()
            ->where('department_id', $otherDepartment->id)
            ->where('code', 'STAFF_'.strtoupper($otherDepartment->code))
            ->firstOrFail();

        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other.audit@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '083333333334',
            'primary_department_id' => $otherDepartment->id,
            'primary_position_id' => $otherPosition->id,
            'global_role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $otherUser->businessUnits()->create([
            'business_unit_id' => $otherBusinessUnit->id,
            'department_id' => $otherDepartment->id,
            'position_id' => $otherPosition->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $cycle = CashflowProjectionCycle::create([
            'business_unit_id' => $otherBusinessUnit->id,
            'year' => 2026,
            'status' => 'draft',
            'created_by' => $otherUser->id,
            'updated_by' => $otherUser->id,
        ]);

        $lineItem = CashflowProjectionLineItem::create([
            'cycle_id' => $cycle->id,
            'department_id' => $otherDepartment->id,
            'flow_type' => 'out',
            'action_code' => 'OUT_EXT_OPS',
            'transaction_date' => '2026-03-12',
            'due_date' => '2026-03-12',
            'is_estimated_date' => false,
            'amount' => 500000,
            'description' => 'Hidden line item',
            'notes' => null,
            'source_type' => 'manual',
            'created_by' => $otherUser->id,
            'updated_by' => $otherUser->id,
        ]);

        $this->actingAsFinanceUser()
            ->delete(route('cashflow-projection.line-items.destroy', [
                'lineItem' => $lineItem->id,
            ]), [
                'year' => 2026,
                'month' => 3,
            ])
            ->assertForbidden();
    }

    public function test_department_head_can_delete_visible_entry_from_own_department(): void
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
            'transaction_date' => '2026-03-20',
            'due_date' => '2026-03-20',
            'is_estimated_date' => false,
            'amount' => 880000,
            'description' => 'HR visible line item',
            'notes' => null,
            'source_type' => 'manual',
            'created_by' => $this->financeUser->id,
            'updated_by' => $this->financeUser->id,
        ]);

        $this->actingAsDepartmentHead()
            ->delete(route('cashflow-projection.line-items.destroy', [
                'lineItem' => $lineItem->id,
            ]), [
                'year' => 2026,
                'month' => 3,
            ])
            ->assertRedirect(route('cashflow-projection.entries', [
                'year' => 2026,
                'month' => 3,
            ]));

        $this->assertDatabaseMissing('cashflow_projection_line_items', [
            'id' => $lineItem->id,
        ]);

        $this->assertDatabaseHas('cashflow_projection_audit_logs', [
            'auditable_type' => 'line_item',
            'auditable_id' => $lineItem->id,
            'action' => 'deleted',
            'actor_user_id' => $this->hrHeadUser->id,
            'actor_department_id' => $this->hrDepartment->id,
        ]);
    }

    public function test_department_head_cannot_delete_entry_from_other_department_in_same_business_unit(): void
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
            'department_id' => $this->financeDepartment->id,
            'flow_type' => 'out',
            'action_code' => 'OUT_CFC_OPS',
            'transaction_date' => '2026-03-20',
            'due_date' => '2026-03-20',
            'is_estimated_date' => false,
            'amount' => 990000,
            'description' => 'Finance only line item',
            'notes' => null,
            'source_type' => 'manual',
            'created_by' => $this->financeUser->id,
            'updated_by' => $this->financeUser->id,
        ]);

        $this->actingAsDepartmentHead()
            ->delete(route('cashflow-projection.line-items.destroy', [
                'lineItem' => $lineItem->id,
            ]), [
                'year' => 2026,
                'month' => 3,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('cashflow_projection_line_items', [
            'id' => $lineItem->id,
        ]);
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

    private function actingAsDepartmentHead(): self
    {
        return $this->actingAs($this->hrHeadUser)->withSession([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
            'current_department_id' => $this->hrDepartment->id,
        ]);
    }
}
