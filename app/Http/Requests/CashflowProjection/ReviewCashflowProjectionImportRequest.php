<?php

namespace App\Http\Requests\CashflowProjection;

use Illuminate\Foundation\Http\FormRequest;

class ReviewCashflowProjectionImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'context_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'context_month' => ['required', 'integer', 'min:1', 'max:12'],
            'preview_token' => ['required', 'string'],
            'signed_rows' => ['required', 'array', 'min:1'],
            'rows' => ['required', 'array', 'min:1'],
            'signed_rows.*' => ['required', 'array'],
            'rows.*' => ['required', 'array'],
            'rows.*.row_number' => ['required', 'integer'],
            'rows.*.business_unit_code' => ['required', 'string'],
            'rows.*.department_code' => ['nullable', 'string'],
            'rows.*.action_code' => ['nullable', 'string'],
            'rows.*.transaction_date' => ['nullable', 'date_format:Y-m-d'],
            'rows.*.due_date' => ['nullable', 'date_format:Y-m-d'],
            'rows.*.is_estimated_date' => ['nullable', 'boolean'],
            'rows.*.amount' => ['nullable', 'decimal:0,2', 'min:0', 'max:9999999999999999.99'],
            'rows.*.description' => ['nullable', 'string', 'max:5000'],
            'rows.*.keterangan' => ['nullable', 'string', 'max:255'],
            'rows.*.no_dokumen' => ['nullable', 'string', 'max:255'],
            'rows.*.nama_vendor' => ['nullable', 'string', 'max:255'],
            'rows.*.notes' => ['nullable', 'string'],
            'rows.*.match.line_item_id' => ['nullable', 'integer'],
        ];
    }
}
