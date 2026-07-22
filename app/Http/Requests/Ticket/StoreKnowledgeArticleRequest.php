<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreKnowledgeArticleRequest extends FormRequest
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
        $businessUnitId = (int) session('current_business_unit_id');

        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('ticket_knowledge_categories', 'id')
                    ->where('business_unit_id', $businessUnitId),
            ],
            'is_published' => ['nullable', 'boolean'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
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
            'title.required' => 'Article title is required.',
            'title.max' => 'Article title cannot exceed 255 characters.',
            'content.required' => 'Article content is required.',
            'category_id.exists' => 'Selected category is invalid.',
            'meta_description.max' => 'Meta description cannot exceed 500 characters.',
            'tags.*.max' => 'Each tag cannot exceed 50 characters.',
        ];
    }
}
