<?php

namespace App\Http\Requests\CashflowProjection;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmCashflowProjectionImportRequest extends FormRequest
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
            'context_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'context_month' => ['required', 'integer', 'min:1', 'max:12'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.status' => ['required', 'string', 'in:new,update,no_change,need_review,invalid'],
            'rows.*.business_unit_code' => ['required', 'string'],
            'rows.*.department_code' => ['nullable', 'string'],
            'rows.*.action_code' => ['nullable', 'string'],
            'rows.*.flow_type' => ['nullable', 'string', 'in:in,out'],
            'rows.*.transaction_date' => ['nullable', 'date'],
            'rows.*.due_date' => ['nullable', 'date'],
            'rows.*.amount' => ['nullable', 'numeric', 'min:0'],
            'rows.*.description' => ['nullable', 'string', 'max:5000'],
            'rows.*.keterangan' => ['nullable', 'string', 'max:255'],
            'rows.*.no_dokumen' => ['nullable', 'string', 'max:255'],
            'rows.*.nama_vendor' => ['nullable', 'string', 'max:255'],
            'rows.*.notes' => ['nullable', 'string'],
            'rows.*.match.line_item_id' => ['nullable', 'integer', 'exists:cashflow_projection_line_items,id'],
        ];
    }
}
