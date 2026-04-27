<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // All authenticated users can submit tickets
        return true;
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
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'], // 10MB per file
            'form_token' => ['nullable', 'string', 'max:64'],
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
            'attachments.max' => 'You can attach a maximum of 5 files.',
            'attachments.*.file' => 'Each attachment must be a valid file.',
            'attachments.*.max' => 'Each attachment cannot exceed 10MB.',
        ];
    }
}
