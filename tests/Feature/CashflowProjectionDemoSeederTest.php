<?php

namespace Tests\Feature;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\CashflowProjection\CashflowProjectionCycle;
use App\Models\Modules\CashflowProjection\CashflowProjectionFinanceInput;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use Carbon\Carbon;
use Database\Seeders\CashflowProjectionDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashflowProjectionDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_cashflow_demo_data_for_a_finance_business_unit_without_duplicates(): void
    {
        Carbon::setTestNow('2026-03-10 09:00:00');

        $businessUnit = BusinessUnit::factory()->create([
            'code' => 'WNS',
            'name' => 'Werkudara Nirwana Sakti',
        ]);

        $financeDepartment = Department::factory()->create([
            'business_unit_id' => $businessUnit->id,
            'code' => 'FIN',
            'name' => 'Finance',
        ]);

        $position = Position::query()
            ->where('department_id', $financeDepartment->id)
            ->where('access_level', 'department_head')
            ->firstOrFail();

        $user = User::factory()->create([
            'name' => 'Finance Seeder User',
            'primary_department_id' => $financeDepartment->id,
            'primary_position_id' => $position->id,
            'last_active_business_unit_id' => $businessUnit->id,
        ]);

        UserBusinessUnit::query()->create([
            'user_id' => $user->id,
            'business_unit_id' => $businessUnit->id,
            'department_id' => $financeDepartment->id,
            'position_id' => $position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->seed(CashflowProjectionDemoSeeder::class);
        $this->seed(CashflowProjectionDemoSeeder::class);

        $cycle = CashflowProjectionCycle::query()
            ->where('business_unit_id', $businessUnit->id)
            ->where('year', 2026)
            ->first();

        $this->assertNotNull($cycle);
        $this->assertSame($user->id, $cycle->created_by);
        $this->assertSame(12, CashflowProjectionFinanceInput::query()->where('cycle_id', $cycle->id)->count());
        $this->assertSame(30, CashflowProjectionLineItem::query()->where('cycle_id', $cycle->id)->count());
        $this->assertSame(
            8,
            CashflowProjectionLineItem::query()
                ->where('cycle_id', $cycle->id)
                ->whereMonth('transaction_date', 3)
                ->count()
        );
        $this->assertDatabaseHas('cashflow_projection_line_items', [
            'cycle_id' => $cycle->id,
            'flow_type' => 'in',
            'description' => '[Demo] Bridge capital injection',
        ]);
        $this->assertDatabaseHas('cashflow_projection_line_items', [
            'cycle_id' => $cycle->id,
            'flow_type' => 'out',
            'description' => '[Demo] Capital return to partner',
        ]);

        Carbon::setTestNow();
    }
}
