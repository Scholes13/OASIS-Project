<?php

namespace App\Http\Requests\CashflowProjection;

use Illuminate\Foundation\Http\FormRequest;

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
                'file',
                // Validate by client extension AND content MIME.  .xlsx is a
                // ZIP container, so browsers may report application/zip or
                // application/octet-stream while the actual content sniffed
                // by fileinfo (used by the `mimetypes` rule) reliably matches
                // the OOXML or zip MIME family.  Combining `extensions` with
                // `mimetypes` blocks renamed binaries (evil.exe -> .xlsx)
                // without rejecting browsers that mis-label the upload.
                'extensions:xlsx',
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,application/octet-stream,application/x-zip-compressed',
                'max:2048',
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
            'file.extensions' => 'Format file wajib .xlsx',
            'file.mimetypes' => 'Format file wajib .xlsx',
            'file.max' => 'Ukuran file maksimal 2 MB.',
        ];
    }
}
