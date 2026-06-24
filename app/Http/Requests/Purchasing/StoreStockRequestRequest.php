<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            // Business Unit and Department
            'business_unit_id' => ['required', 'integer', 'exists:business_units,id'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],

            // ST Details
            'purpose' => ['required', 'string', 'min:10', 'max:1000'],
            'date_of_request' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:date_of_request'],

            // Offline Approval Document
            'offline_approval_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], // 10MB max

            // Approval Workflow
            'approval_workflow' => ['nullable', 'array'],
            'approval_workflow.*.approver_id' => ['nullable', 'integer', 'exists:users,id', 'distinct'],
            'approval_workflow.*.task_type' => ['nullable', 'string', 'in:approval,paraf'],
            'approval_notes' => ['nullable', 'string', 'max:1000'],

            // Items
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.item_description' => ['nullable', 'string', 'max:1000'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.image' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'], // 2MB max
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
            // Business Unit and Department
            'business_unit_id.required' => 'Business unit is required.',
            'business_unit_id.exists' => 'Selected business unit is invalid.',
            'department_id.required' => 'Department is required.',
            'department_id.exists' => 'Selected department is invalid.',

            // ST Details
            'purpose.required' => 'Purpose field is required.',
            'purpose.min' => 'Purpose must be at least 10 characters.',
            'purpose.max' => 'Purpose cannot exceed 1000 characters.',
            'date_of_request.required' => 'Request date is required.',
            'date_of_request.date' => 'Request date must be a valid date.',
            'expected_date.date' => 'Expected date must be a valid date.',
            'expected_date.after_or_equal' => 'Expected date must be on or after the request date.',

            // Offline Approval Document
            'offline_approval_document.file' => 'Offline approval document must be a file.',
            'offline_approval_document.mimes' => 'Offline approval document must be a PDF, JPG, JPEG, or PNG file.',
            'offline_approval_document.max' => 'Offline approval document cannot exceed 10MB.',

            // Approval Workflow
            'approval_workflow.required' => 'At least one approver is required.',
            'approval_workflow.min' => 'At least one approver is required.',
            'approval_workflow.*.approver_id.required' => 'Approver is required.',
            'approval_workflow.*.approver_id.exists' => 'Selected approver is invalid.',
            'approval_workflow.*.approver_id.distinct' => 'Duplicate approvers are not allowed.',
            'approval_workflow.*.task_type.required' => 'Task type is required.',
            'approval_workflow.*.task_type.in' => 'Invalid task type.',
            'approval_notes.max' => 'Approval notes cannot exceed 1000 characters.',

            // Items
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.item_name.required' => 'Item name is required.',
            'items.*.item_name.max' => 'Item name cannot exceed 255 characters.',
            'items.*.item_description.max' => 'Item description cannot exceed 1000 characters.',
            'items.*.quantity.required' => 'Quantity is required.',
            'items.*.quantity.numeric' => 'Quantity must be a number.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.unit.required' => 'Unit is required.',
            'items.*.unit.max' => 'Unit cannot exceed 50 characters.',
            'items.*.image.file' => 'Item image must be a file.',
            'items.*.image.mimes' => 'Item image must be a JPG, JPEG, or PNG file.',
            'items.*.image.max' => 'Item image cannot exceed 2MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure business_unit_id is set from session if not provided
        if (! $this->has('business_unit_id')) {
            $this->merge([
                'business_unit_id' => session('current_business_unit_id'),
            ]);
        }

        // Ensure department_id is set from session if not provided
        if (! $this->has('department_id')) {
            $this->merge([
                'department_id' => session('current_department_id'),
            ]);
        }
    }
}
