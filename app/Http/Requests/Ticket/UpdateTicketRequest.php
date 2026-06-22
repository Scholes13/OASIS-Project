<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateTicketRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'category_id' => ['nullable', 'integer', 'exists:ticket_categories,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'follow_up_at' => ['nullable', 'date'],
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
            'title.required' => 'Ticket title is required.',
            'title.max' => 'Ticket title cannot exceed 255 characters.',
            'description.required' => 'Ticket description is required.',
            'priority.required' => 'Priority is required.',
            'priority.in' => 'Priority must be one of: low, medium, high, critical.',
            'category_id.exists' => 'Selected category is invalid.',
            'department_id.exists' => 'Selected department is invalid.',
            'follow_up_at.date' => 'Follow-up date must be a valid date.',
        ];
    }
}
