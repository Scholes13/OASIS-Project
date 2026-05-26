<?php

namespace App\Http\Requests\CashflowProjection;

use App\Models\Core\Department;
use Illuminate\Foundation\Http\FormRequest;

class StoreCashflowProjectionLineItemRequest extends FormRequest
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
            'department_id' => [
                'required',
                'integer',
                'exists:departments,id',
                function (string $attribute, $value, \Closure $fail): void {
                    $dept = Department::find($value);
                    if ($dept && $dept->children()->where('is_active', true)->exists()) {
                        $fail('Cashflow line item harus dibuat di sub-department, bukan di root department dengan sub-department aktif.');
                    }
                },
            ],
            'action_code' => ['required', 'string', 'max:100'],
            'transaction_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'is_estimated_date' => ['nullable', 'boolean'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
