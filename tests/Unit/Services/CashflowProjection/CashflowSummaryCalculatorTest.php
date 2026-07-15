<?php

namespace Tests\Unit\Services\CashflowProjection;

use App\Models\Modules\CashflowProjection\CashflowProjectionFinanceInput;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use App\Services\Modules\CashflowProjection\CashflowSummaryCalculator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CashflowSummaryCalculatorTest extends TestCase
{
    public function test_monthly_summary_carries_closing_balance_forward_when_month_has_no_finance_input(): void
    {
        $summary = app(CashflowSummaryCalculator::class)->buildMonthlySummary(
            new Collection([
                new CashflowProjectionLineItem([
                    'transaction_date' => '2026-04-01',
                    'flow_type' => 'in',
                    'amount' => 500_000_000,
                ]),
            ]),
            new Collection([
                new CashflowProjectionFinanceInput([
                    'month' => 4,
                    'cash_on_hand' => 1_000_000_000,
                    'receivable_estimate' => 0,
                    'upcoming_event_revenue_estimate' => 0,
                    'capital_injection_estimate' => 0,
                    'other_income' => 0,
                ]),
            ])
        );

        $this->assertSame('1500000000.00', $summary[3]['closing_balance']);
        $this->assertSame('1500000000.00', $summary[4]['opening_balance']);
        $this->assertSame('1500000000.00', $summary[4]['closing_balance']);
        $this->assertSame('1500000000.00', $summary[5]['opening_balance']);
        $this->assertSame('1500000000.00', $summary[5]['closing_balance']);
    }

    public function test_summary_adds_cents_exactly(): void
    {
        $summary = app(CashflowSummaryCalculator::class)->buildMonthlySummary(
            new Collection([
                new CashflowProjectionLineItem(['transaction_date' => '2026-01-01', 'flow_type' => 'in', 'amount' => '0.01']),
                new CashflowProjectionLineItem(['transaction_date' => '2026-01-02', 'flow_type' => 'in', 'amount' => '0.02']),
            ]),
            new Collection
        );

        $this->assertSame('0.03', $summary[0]['plus']);
        $this->assertSame('0.03', $summary[0]['closing_balance']);
    }
}
