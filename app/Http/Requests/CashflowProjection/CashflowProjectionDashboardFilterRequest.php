<?php

namespace App\Http\Requests\CashflowProjection;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CashflowProjectionDashboardFilterRequest extends FormRequest
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
            'filter' => ['nullable', 'string', 'in:month,year,range'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'filter.in' => 'Filter dashboard tidak valid.',
            'year.required' => 'Tahun wajib dipilih.',
            'year.integer' => 'Tahun harus berupa angka.',
            'year.between' => 'Tahun harus berada di antara 2000 sampai 2100.',
            'month.integer' => 'Bulan harus berupa angka.',
            'month.between' => 'Bulan harus berada di antara 1 sampai 12.',
            'start_date.date' => 'Tanggal mulai tidak valid.',
            'end_date.date' => 'Tanggal akhir tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal mulai.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'filter' => $this->input('filter', 'month'),
            'year' => $this->integer('year', (int) now()->format('Y')),
            'month' => $this->integer('month', (int) now()->format('n')),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $filterMode = (string) $this->input('filter', 'month');

            if ($filterMode !== 'range') {
                return;
            }

            if (! $this->filled('start_date') || ! $this->filled('end_date')) {
                $validator->errors()->add('start_date', 'Tanggal mulai dan tanggal akhir wajib diisi untuk filter rentang tanggal.');

                return;
            }

            $selectedYear = (int) $this->input('year');
            $startDate = CarbonImmutable::parse((string) $this->input('start_date'));
            $endDate = CarbonImmutable::parse((string) $this->input('end_date'));

            if ((int) $startDate->format('Y') !== $selectedYear || (int) $endDate->format('Y') !== $selectedYear) {
                $validator->errors()->add('start_date', 'Rentang tanggal harus berada di tahun yang sama dengan filter tahun.');
            }
        });
    }
}
