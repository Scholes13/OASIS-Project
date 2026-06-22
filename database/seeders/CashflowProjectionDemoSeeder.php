<?php

namespace Database\Seeders;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\CashflowProjection\CashflowProjectionCycle;
use App\Models\Modules\CashflowProjection\CashflowProjectionFinanceInput;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class CashflowProjectionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $year = (int) now()->year;
        $currentMonth = (int) now()->month;

        BusinessUnit::query()
            ->where('is_active', true)
            ->get()
            ->each(function (BusinessUnit $businessUnit) use ($year, $currentMonth): void {
                $financeDepartment = $this->resolveFinanceDepartment($businessUnit);

                if (! $financeDepartment) {
                    return;
                }

                $userId = $this->resolveSeederUserId($businessUnit, $financeDepartment);

                if (! $userId) {
                    return;
                }

                $cycle = CashflowProjectionCycle::query()->updateOrCreate(
                    [
                        'business_unit_id' => $businessUnit->id,
                        'year' => $year,
                    ],
                    [
                        'status' => 'draft',
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]
                );

                $this->upsertFinanceInputs($cycle, $userId, $currentMonth);
                $this->upsertLineItems($cycle, $financeDepartment, $userId, $currentMonth);
            });
    }

    private function resolveFinanceDepartment(BusinessUnit $businessUnit): ?Department
    {
        return $businessUnit->departments()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereIn('code', ['FIN', 'CFC'])
                    ->orWhere('name', 'like', '%Finance%');
            })
            ->orderByRaw("CASE WHEN code = 'CFC' THEN 0 WHEN code = 'FIN' THEN 1 ELSE 2 END")
            ->first();
    }

    private function resolveSeederUserId(BusinessUnit $businessUnit, Department $financeDepartment): ?int
    {
        $financeUserId = UserBusinessUnit::query()
            ->where('business_unit_id', $businessUnit->id)
            ->where('department_id', $financeDepartment->id)
            ->where('is_active', true)
            ->whereHas('user', function ($query): void {
                $query->where('is_active', true);
            })
            ->value('user_id');

        if ($financeUserId) {
            return (int) $financeUserId;
        }

        $fallbackUserId = UserBusinessUnit::query()
            ->where('business_unit_id', $businessUnit->id)
            ->where('is_active', true)
            ->whereHas('user', function ($query): void {
                $query->where('is_active', true);
            })
            ->value('user_id');

        return $fallbackUserId ? (int) $fallbackUserId : null;
    }

    private function upsertFinanceInputs(CashflowProjectionCycle $cycle, int $userId, int $currentMonth): void
    {
        for ($month = 1; $month <= 12; $month++) {
            $isFocusMonth = $month === $currentMonth;

            CashflowProjectionFinanceInput::query()->updateOrCreate(
                [
                    'cycle_id' => $cycle->id,
                    'month' => $month,
                ],
                [
                    'cash_on_hand' => 260000000 + ($month * 12500000) + ($isFocusMonth ? 45000000 : 0),
                    'receivable_estimate' => 45000000 + ($month * 2500000),
                    'upcoming_event_revenue_estimate' => 20000000 + ($month * 1750000),
                    'capital_injection_estimate' => $isFocusMonth ? 90000000 : ($month % 4 === 0 ? 35000000 : 0),
                    'other_income' => 12000000 + ($month * 800000),
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]
            );
        }
    }

    private function upsertLineItems(CashflowProjectionCycle $cycle, Department $financeDepartment, int $userId, int $currentMonth): void
    {
        CashflowProjectionLineItem::query()
            ->where('cycle_id', $cycle->id)
            ->where('notes', '[cashflow-demo]')
            ->delete();

        for ($month = 1; $month <= 12; $month++) {
            if ($month === $currentMonth) {
                $this->seedFocusMonthLineItems($cycle, $financeDepartment, $userId, $month);

                continue;
            }

            $this->seedBaseMonthLineItems($cycle, $financeDepartment, $userId, $month);
        }
    }

    private function seedBaseMonthLineItems(CashflowProjectionCycle $cycle, Department $department, int $userId, int $month): void
    {
        $monthName = CarbonImmutable::create($cycle->year, $month, 1)->translatedFormat('F');
        $baseRevenue = 95000000 + ($month * 4500000);
        $baseExpense = 70000000 + ($month * 3750000);

        $this->upsertLineItem(
            cycle: $cycle,
            department: $department,
            userId: $userId,
            actionCode: 'IN_CFC_SUNTIKAN_MODAL',
            transactionDate: CarbonImmutable::create($cycle->year, $month, 5),
            amount: $baseRevenue,
            description: "[Demo] {$monthName} liquidity support",
            notes: '[cashflow-demo]'
        );

        $this->upsertLineItem(
            cycle: $cycle,
            department: $department,
            userId: $userId,
            actionCode: 'OUT_CFC_CORPORATE_EXPENSES',
            transactionDate: CarbonImmutable::create($cycle->year, $month, 19),
            amount: $baseExpense,
            description: "[Demo] {$monthName} corporate spend",
            notes: '[cashflow-demo]'
        );
    }

    private function seedFocusMonthLineItems(CashflowProjectionCycle $cycle, Department $department, int $userId, int $month): void
    {
        $rows = [
            [2, 'IN_CFC_SUNTIKAN_MODAL', 120000000, 'Bridge capital injection'],
            [4, 'OUT_CFC_CORPORATE_EXPENSES', 65000000, 'Vendor retainer release'],
            [7, 'IN_CFC_PENERIMAAN_PENGEMBALIAN_PINJAMAN', 90000000, 'Loan repayment batch'],
            [10, 'OUT_CFC_HUTANG_USAHA', 135000000, 'Trade payable settlement'],
            [13, 'OUT_CFC_BUNGA_ANGSURAN', 48000000, 'Installment and interest'],
            [17, 'IN_CFC_SUNTIKAN_MODAL', 155000000, 'Shareholder support tranche'],
            [22, 'OUT_CFC_PENGEMBALIAN_SUNTIKAN_MODAL', 185000000, 'Capital return to partner'],
            [27, 'IN_CFC_PENERIMAAN_PENGEMBALIAN_PINJAMAN', 110000000, 'Receivable recovery'],
        ];

        foreach ($rows as [$day, $actionCode, $amount, $description]) {
            $this->upsertLineItem(
                cycle: $cycle,
                department: $department,
                userId: $userId,
                actionCode: $actionCode,
                transactionDate: CarbonImmutable::create($cycle->year, $month, $day),
                amount: $amount,
                description: '[Demo] '.$description,
                notes: '[cashflow-demo]'
            );
        }
    }

    private function upsertLineItem(
        CashflowProjectionCycle $cycle,
        Department $department,
        int $userId,
        string $actionCode,
        CarbonImmutable $transactionDate,
        int $amount,
        string $description,
        string $notes
    ): void {
        CashflowProjectionLineItem::query()->create([
            'cycle_id' => $cycle->id,
            'department_id' => $department->id,
            'flow_type' => str_starts_with($actionCode, 'IN_') ? 'in' : 'out',
            'action_code' => $actionCode,
            'transaction_date' => $transactionDate->format('Y-m-d'),
            'due_date' => $transactionDate->format('Y-m-d'),
            'is_estimated_date' => false,
            'amount' => $amount,
            'description' => $description,
            'notes' => $notes,
            'source_type' => 'manual',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }
}
