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
            'preview_token' => ['required', 'string'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.row_number' => ['nullable', 'integer'],
            'rows.*.status' => ['required', 'string', 'in:new,update,no_change,need_review,invalid'],
            'rows.*.business_unit_code' => ['required', 'string'],
            'rows.*.department_code' => ['nullable', 'string'],
            'rows.*.action_code' => ['nullable', 'string'],
            'rows.*.flow_type' => ['nullable', 'string', 'in:in,out'],
            'rows.*.action_label' => ['nullable', 'string'],
            'rows.*.transaction_date' => ['required', 'date_format:Y-m-d'],
            'rows.*.due_date' => ['nullable', 'date_format:Y-m-d'],
            'rows.*.is_estimated_date' => ['nullable', 'boolean'],
            'rows.*.amount' => ['required', 'decimal:0,2', 'min:0', 'max:9999999999999999.99'],
            'rows.*.description' => ['required', 'string', 'max:5000'],
            'rows.*.keterangan' => ['nullable', 'string', 'max:255'],
            'rows.*.no_dokumen' => ['nullable', 'string', 'max:255'],
            'rows.*.nama_vendor' => ['nullable', 'string', 'max:255'],
            'rows.*.notes' => ['nullable', 'string'],
            'rows.*.match.line_item_id' => ['nullable', 'integer', 'exists:cashflow_projection_line_items,id'],
            'rows.*.original' => ['nullable', 'array'],
            'rows.*.changes' => ['nullable', 'array'],
            'rows.*.changes.*.field' => ['required_with:rows.*.changes', 'string'],
            'rows.*.changes.*.old' => ['nullable'],
            'rows.*.changes.*.new' => ['nullable'],
            'rows.*.errors' => ['nullable', 'array'],
            'rows.*.errors.*.field' => ['required_with:rows.*.errors', 'string'],
            'rows.*.errors.*.message' => ['required_with:rows.*.errors', 'string'],
        ];
    }
}
