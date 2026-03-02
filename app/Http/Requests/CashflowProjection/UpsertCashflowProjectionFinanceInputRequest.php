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
            'cash_on_hand' => ['required', 'numeric', 'min:0'],
            'receivable_estimate' => ['required', 'numeric', 'min:0'],
            'upcoming_event_revenue_estimate' => ['required', 'numeric', 'min:0'],
            'capital_injection_estimate' => ['required', 'numeric', 'min:0'],
            'other_income' => ['required', 'numeric', 'min:0'],
        ];
    }
}
