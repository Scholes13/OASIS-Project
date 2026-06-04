<?php

namespace Tests\Feature\Modules\CashflowProjection;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionCycle;
use App\Models\Modules\CashflowProjection\CashflowProjectionLinkedUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CashflowProjectionCrossUnitEntryTest extends TestCase
{
    use RefreshDatabase;

    private BusinessUnit $hostBusinessUnit;

    private BusinessUnit $linkedBusinessUnit;

    private Department $financeDepartment;

    private Department $hrDepartment;

    private Department $linkedOpsDepartment;

    private Position $financePosition;

    private Position $hrHeadPosition;

    private User $financeUser;

    private User $hrHeadUser;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);

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

        $this->hrHeadPosition = Position::query()
            ->where('department_id', $this->hrDepartment->id)
            ->where(function ($query) {
                $query->where('level', 'hod')
                    ->orWhere('access_level', 'department_head');
            })
            ->firstOrFail();

        $this->financeUser = User::create([
            'name' => 'Finance User',
            'email' => 'finance.cross-unit@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '081111111111',
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

        $this->hrHeadUser = User::create([
            'name' => 'HR Head',
            'email' => 'hr.head@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '082222222222',
            'primary_department_id' => $this->hrDepartment->id,
            'primary_position_id' => $this->hrHeadPosition->id,
            'global_role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->hrHeadUser->businessUnits()->create([
            'business_unit_id' => $this->hostBusinessUnit->id,
            'department_id' => $this->hrDepartment->id,
            'position_id' => $this->hrHeadPosition->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        CashflowProjectionLinkedUnit::query()->create([
            'host_business_unit_id' => $this->hostBusinessUnit->id,
            'linked_business_unit_id' => $this->linkedBusinessUnit->id,
            'created_by' => $this->financeUser->id,
        ]);
    }

    public function test_finance_entries_page_exposes_departments_from_active_and_linked_business_units(): void
    {
        $response = $this->actingAsFinanceUser()->get(route('cashflow-projection.entries', [
            'year' => 2026,
            'month' => 3,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('CashflowProjection/Entries')
            ->has('departments', 3)
            ->where('departments.0.business_unit_id', $this->hostBusinessUnit->id)
            ->where('departments.1.business_unit_id', $this->hostBusinessUnit->id)
            ->where('departments.2.business_unit_id', $this->linkedBusinessUnit->id)
        );
    }

    public function test_finance_user_can_create_line_item_for_another_department_in_active_business_unit(): void
    {
        $response = $this->actingAsFinanceUser()->post(route('cashflow-projection.line-items.store'), [
            'year' => 2026,
            'department_id' => $this->hrDepartment->id,
            'action_code' => 'OUT_HR_OPS',
            'transaction_date' => '2026-03-12',
            'due_date' => '2026-03-12',
            'is_estimated_date' => false,
            'amount' => 1500000,
            'description' => 'HR operational support',
            'keterangan' => 'KAS BON OPERASIONAL',
            'notes' => 'Manual input by finance',
        ]);

        $response->assertRedirect(route('cashflow-projection.entries', [
            'year' => 2026,
            'month' => 3,
        ]));

        $cycle = CashflowProjectionCycle::query()
            ->where('business_unit_id', $this->hostBusinessUnit->id)
            ->where('year', 2026)
            ->first();

        $this->assertNotNull($cycle);
        $this->assertDatabaseHas('cashflow_projection_line_items', [
            'cycle_id' => $cycle?->id,
            'department_id' => $this->hrDepartment->id,
            'action_code' => 'OUT_HR_OPS',
            'description' => 'HR operational support',
            'keterangan' => 'KAS BON OPERASIONAL',
        ]);
    }

    public function test_finance_user_can_create_line_item_with_long_description_and_keterangan(): void
    {
        $longDescription = trim(str_repeat('WNS - HR - Biaya operasional panjang. ', 40));

        $response = $this->actingAsFinanceUser()->post(route('cashflow-projection.line-items.store'), [
            'year' => 2026,
            'department_id' => $this->hrDepartment->id,
            'action_code' => 'OUT_HR_OPS',
            'transaction_date' => '2026-03-12',
            'due_date' => '2026-03-12',
            'is_estimated_date' => false,
            'amount' => 1500000,
            'description' => $longDescription,
            'keterangan' => 'OPERASIONAL',
            'notes' => 'Manual input by finance',
        ]);

        $response->assertRedirect(route('cashflow-projection.entries', [
            'year' => 2026,
            'month' => 3,
        ]));

        $this->assertDatabaseHas('cashflow_projection_line_items', [
            'department_id' => $this->hrDepartment->id,
            'description' => $longDescription,
            'keterangan' => 'OPERASIONAL',
        ]);

        $this->actingAsFinanceUser()->get(route('cashflow-projection.entries', [
            'year' => 2026,
            'month' => 3,
        ]))->assertInertia(fn (Assert $page) => $page
            ->component('CashflowProjection/Entries')
            ->where('lineItems.data.0.description', $longDescription)
            ->where('lineItems.data.0.keterangan', 'OPERASIONAL')
        );
    }

    public function test_finance_user_can_create_line_item_for_linked_business_unit_department_and_it_uses_target_cycle(): void
    {
        $response = $this->actingAsFinanceUser()->post(route('cashflow-projection.line-items.store'), [
            'year' => 2026,
            'department_id' => $this->linkedOpsDepartment->id,
            'action_code' => 'OUT_OPS_OPS',
            'transaction_date' => '2026-03-21',
            'due_date' => '2026-03-21',
            'is_estimated_date' => true,
            'amount' => 2250000,
            'description' => 'Linked BU operational support',
            'notes' => null,
        ]);

        $response->assertRedirect(route('cashflow-projection.entries', [
            'year' => 2026,
            'month' => 3,
        ]));

        $linkedCycle = CashflowProjectionCycle::query()
            ->where('business_unit_id', $this->linkedBusinessUnit->id)
            ->where('year', 2026)
            ->first();

        $hostCycle = CashflowProjectionCycle::query()
            ->where('business_unit_id', $this->hostBusinessUnit->id)
            ->where('year', 2026)
            ->first();

        $this->assertNotNull($linkedCycle);
        $this->assertDatabaseHas('cashflow_projection_line_items', [
            'cycle_id' => $linkedCycle?->id,
            'department_id' => $this->linkedOpsDepartment->id,
            'action_code' => 'OUT_OPS_OPS',
            'description' => 'Linked BU operational support',
        ]);

        if ($hostCycle) {
            $this->assertDatabaseMissing('cashflow_projection_line_items', [
                'cycle_id' => $hostCycle->id,
                'department_id' => $this->linkedOpsDepartment->id,
                'description' => 'Linked BU operational support',
            ]);
        }
    }

    public function test_non_finance_user_cannot_create_line_item_for_another_department(): void
    {
        $response = $this->actingAsHrHeadUser()->post(route('cashflow-projection.line-items.store'), [
            'year' => 2026,
            'department_id' => $this->financeDepartment->id,
            'action_code' => 'OUT_CFC_OPS',
            'transaction_date' => '2026-03-12',
            'due_date' => '2026-03-12',
            'is_estimated_date' => false,
            'amount' => 500000,
            'description' => 'Unauthorized finance edit',
            'notes' => null,
        ]);

        $response->assertForbidden();
    }

    public function test_finance_user_receives_validation_error_for_department_outside_allowed_scope(): void
    {
        $outsideBusinessUnit = BusinessUnit::create([
            'code' => 'TEE',
            'name' => 'Takshaka Event',
            'is_active' => true,
        ]);

        $outsideDepartment = Department::create([
            'business_unit_id' => $outsideBusinessUnit->id,
            'code' => 'OPS2',
            'name' => 'Operations 2',
            'is_active' => true,
        ]);

        $response = $this->actingAsFinanceUser()->from(route('cashflow-projection.entries'))->post(route('cashflow-projection.line-items.store'), [
            'year' => 2026,
            'department_id' => $outsideDepartment->id,
            'action_code' => 'OUT_OPS2_OPS',
            'transaction_date' => '2026-03-12',
            'due_date' => '2026-03-12',
            'is_estimated_date' => false,
            'amount' => 500000,
            'description' => 'Out of scope department',
            'notes' => null,
        ]);

        $response->assertRedirect(route('cashflow-projection.entries'));
        $response->assertSessionHasErrors('department_id');
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

    private function actingAsHrHeadUser(): self
    {
        return $this->actingAs($this->hrHeadUser)->withSession([
            'current_business_unit_id' => $this->hostBusinessUnit->id,
            'current_business_unit_code' => $this->hostBusinessUnit->code,
            'current_business_unit_name' => $this->hostBusinessUnit->name,
            'current_department_id' => $this->hrDepartment->id,
        ]);
    }
}
