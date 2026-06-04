<?php

namespace App\Http\Requests\CashflowProjection;

use Illuminate\Foundation\Http\FormRequest;

class BulkDestroyCashflowProjectionLineItemsRequest extends FormRequest
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
            'line_item_ids' => ['required', 'array', 'min:1'],
            'line_item_ids.*' => ['integer', 'distinct', 'exists:cashflow_projection_line_items,id'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
        ];
    }
}
