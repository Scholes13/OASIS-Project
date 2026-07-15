<?php

namespace App\Http\Requests\Purchasing;

use App\Services\Modules\Purchasing\AllRequests\PurchasingRequestScopeResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AllPurchasingRequestsRequest extends FormRequest
{
    /** @var array<int, int>|null */
    private ?array $resolvedBusinessUnitIds = null;

    public function authorize(): bool
    {
        return $this->user() !== null && $this->businessUnitIds() !== [];
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'type' => ['nullable', Rule::in(['purchase_request', 'stock_request'])],
            'status' => ['nullable', Rule::in([
                'draft',
                'submitted',
                'in_approval',
                'approved',
                'ga_review',
                'ga_rejected',
                'ready_for_purchasing',
                'rejected',
                'voided',
                'done',
            ])],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where(fn ($query) => $query
                    ->whereIn('business_unit_id', $this->businessUnitIds())
                    ->where('is_active', true)),
            ],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', Rule::in(['created_at', 'date_of_request', 'number', 'status', 'total_amount'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<int, int> */
    private function businessUnitIds(): array
    {
        if ($this->resolvedBusinessUnitIds !== null) {
            return $this->resolvedBusinessUnitIds;
        }

        $user = $this->user();

        return $this->resolvedBusinessUnitIds = $user === null
            ? []
            : app(PurchasingRequestScopeResolver::class)->businessUnitIds(
                $user,
                (int) $this->session()->get('current_business_unit_id'),
            );
    }
}
