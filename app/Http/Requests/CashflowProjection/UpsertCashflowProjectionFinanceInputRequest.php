<?php

namespace App\Http\Requests\CashflowProjection;

use Illuminate\Foundation\Http\FormRequest;

class UpsertCashflowProjectionFinanceInputRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'cash_on_hand' => $this->moneyRules(),
            'receivable_estimate' => $this->moneyRules(),
            'upcoming_event_revenue_estimate' => $this->moneyRules(),
            'capital_injection_estimate' => $this->moneyRules(),
            'other_income' => $this->moneyRules(),
        ];
    }

    /** @return array<int, string> */
    private function moneyRules(): array
    {
        return ['required', 'decimal:0,2', 'min:0', 'max:9999999999999999.99'];
    }
}
