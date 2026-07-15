<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $businessUnitId = (int) session('current_business_unit_id');
        $departmentId = (int) session('current_department_id');

        return $user !== null
            && $businessUnitId > 0
            && in_array($businessUnitId, $user->getAccessibleBusinessUnitIds(), true)
            && $departmentId > 0
            && $user->canAccessDepartment($departmentId);
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
            'business_unit_id' => ['required', 'integer', Rule::in([(int) session('current_business_unit_id')])],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where(fn ($query) => $query
                ->where('business_unit_id', (int) session('current_business_unit_id'))
                ->where('is_active', true))],

            // Category
            'category_id' => ['nullable', 'integer', 'exists:pr_categories,id'],

            // PR Details
            'used_for' => ['required', 'string', 'min:10', 'max:1000'],
            'date_of_request' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:date_of_request'],

            // Currency
            'currency' => ['required', 'string', 'in:IDR,USD,EUR,SGD'],

            // Supporting Document
            'supporting_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // 5MB max

            // Approval Workflow
            'submission_intent' => ['required', 'string', 'in:draft,submit'],
            'approval_workflow' => [Rule::requiredIf(! $this->isDraft()), 'array', Rule::when(! $this->isDraft(), ['min:1'])],
            'approval_workflow.*.approver_id' => [Rule::requiredIf(! $this->isDraft()), 'integer', 'distinct'],
            'approval_workflow.*.task_type' => [Rule::requiredIf(! $this->isDraft()), 'string', 'in:approval,review,notification,paraf'],
            'approval_notes' => ['nullable', 'string', 'max:1000'],

            // Items
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.brand_name' => ['nullable', 'string', 'max:255'],
            'items.*.item_description' => ['nullable', 'string', 'max:1000'],
            'items.*.supplier_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.currency' => ['required', 'string', 'in:IDR,USD,EUR,SGD'],
            'items.*.expense_department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where(fn ($query) => $query
                ->where('business_unit_id', (int) session('current_business_unit_id'))
                ->where('is_active', true))],
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

            // Category
            'category_id.exists' => 'Selected category is invalid.',

            // PR Details
            'used_for.required' => 'Purpose / Used For field is required.',
            'used_for.min' => 'Purpose / Used For must be at least 10 characters.',
            'used_for.max' => 'Purpose / Used For cannot exceed 1000 characters.',
            'date_of_request.required' => 'Request date is required.',
            'date_of_request.date' => 'Request date must be a valid date.',
            'expected_date.date' => 'Expected date must be a valid date.',
            'expected_date.after_or_equal' => 'Expected date must be on or after the request date.',

            // Currency
            'currency.required' => 'Currency is required.',
            'currency.in' => 'Selected currency is invalid.',

            // Supporting Document
            'supporting_document.file' => 'Supporting document must be a file.',
            'supporting_document.mimes' => 'Supporting document must be a PDF, JPG, JPEG, or PNG file.',
            'supporting_document.max' => 'Supporting document cannot exceed 5MB.',

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
            'items.*.brand_name.max' => 'Brand name cannot exceed 255 characters.',
            'items.*.item_description.max' => 'Item description cannot exceed 1000 characters.',
            'items.*.supplier_name.max' => 'Supplier name cannot exceed 255 characters.',
            'items.*.quantity.required' => 'Quantity is required.',
            'items.*.quantity.numeric' => 'Quantity must be a number.',
            'items.*.quantity.min' => 'Quantity must be greater than 0.',
            'items.*.unit.required' => 'Unit is required.',
            'items.*.unit.max' => 'Unit cannot exceed 50 characters.',
            'items.*.unit_price.required' => 'Unit price is required.',
            'items.*.unit_price.numeric' => 'Unit price must be a number.',
            'items.*.unit_price.min' => 'Unit price must be 0 or greater.',
            'items.*.currency.required' => 'Currency is required.',
            'items.*.currency.in' => 'Selected currency is invalid.',
            'items.*.expense_department_id.required' => 'Expense department is required.',
            'items.*.expense_department_id.exists' => 'Selected expense department is invalid.',
            'items.*.image.file' => 'Item image must be a file.',
            'items.*.image.mimes' => 'Item image must be a JPG, JPEG, or PNG file.',
            'items.*.image.max' => 'Item image cannot exceed 2MB.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'business_unit_id' => 'business unit',
            'department_id' => 'department',
            'category_id' => 'category',
            'used_for' => 'purpose',
            'date_of_request' => 'request date',
            'expected_date' => 'expected date',
            'currency' => 'currency',
            'supporting_document' => 'supporting document',
            'approval_workflow' => 'approval workflow',
            'approval_notes' => 'approval notes',
            'items' => 'items',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('submission_intent')) {
            $this->merge(['submission_intent' => 'submit']);
        }

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

    public function isDraft(): bool
    {
        return $this->input('submission_intent') === 'draft';
    }
}
