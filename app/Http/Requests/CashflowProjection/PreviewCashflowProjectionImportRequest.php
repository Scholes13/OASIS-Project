<?php

namespace App\Http\Requests\CashflowProjection;

use Illuminate\Foundation\Http\FormRequest;

class PreviewCashflowProjectionImportRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'extensions:xlsx',
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,application/octet-stream,application/x-zip-compressed',
                'max:10240',
            ],
        ];
    }
}
