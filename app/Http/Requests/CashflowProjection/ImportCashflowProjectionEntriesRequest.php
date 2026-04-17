<?php

namespace App\Http\Requests\CashflowProjection;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class ImportCashflowProjectionEntriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                File::types(['xlsx'])
                    ->max(2048),
            ],
            'context_year' => ['nullable', 'integer', 'between:2000,2100'],
            'context_month' => ['nullable', 'integer', 'between:1,12'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'File import wajib diupload.',
            'file.types' => 'Format file wajib .xlsx',
            'file.max' => 'Ukuran file maksimal 2 MB.',
        ];
    }
}
