<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateSlaSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('access-it-support');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'settings' => ['required', 'array', 'size:4'],
            'settings.*.priority' => ['required', 'in:low,medium,high,critical'],
            'settings.*.resolution_hours' => ['required', 'integer', 'min:1', 'max:720'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'settings.required' => 'SLA settings are required.',
            'settings.size' => 'Exactly 4 priority settings must be provided (low, medium, high, critical).',
            'settings.*.priority.required' => 'Priority is required for each SLA setting.',
            'settings.*.priority.in' => 'Priority must be one of: low, medium, high, critical.',
            'settings.*.resolution_hours.required' => 'Resolution hours are required for each priority.',
            'settings.*.resolution_hours.integer' => 'Resolution hours must be a whole number.',
            'settings.*.resolution_hours.min' => 'Resolution hours must be at least 1.',
            'settings.*.resolution_hours.max' => 'Resolution hours cannot exceed 720 (30 days).',
        ];
    }
}
