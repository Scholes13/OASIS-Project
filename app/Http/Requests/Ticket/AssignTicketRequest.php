<?php

namespace App\Http\Requests\Ticket;

use App\Models\Core\UserBusinessUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class AssignTicketRequest extends FormRequest
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
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Add custom validation: assigned user must have is_it_support_admin in the ticket's BU.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $assignedTo = $this->input('assigned_to');
            $ticket = $this->route('ticket');

            if (! $assignedTo || ! $ticket) {
                return;
            }

            $buId = $ticket->business_unit_id;

            $isItSupportAdmin = UserBusinessUnit::where('user_id', $assignedTo)
                ->where('business_unit_id', $buId)
                ->where('is_it_support_admin', true)
                ->exists();

            if (! $isItSupportAdmin) {
                $validator->errors()->add(
                    'assigned_to',
                    'The selected user is not an IT Support admin in this ticket\'s business unit.'
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'assigned_to.required' => 'An assignee is required.',
            'assigned_to.exists' => 'The selected user does not exist.',
        ];
    }
}
