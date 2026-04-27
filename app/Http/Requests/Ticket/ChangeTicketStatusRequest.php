<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class ChangeTicketStatusRequest extends FormRequest
{
    /**
     * Allowed status transitions.
     * Terminal states (done, cancelled) cannot transition to any other state.
     *
     * @var array<string, array<string>>
     */
    protected const ALLOWED_TRANSITIONS = [
        'waiting' => ['in_progress', 'cancelled'],
        'in_progress' => ['done', 'cancelled'],
    ];

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
            'status' => ['required', 'in:waiting,in_progress,done,cancelled'],
        ];
    }

    /**
     * Add custom validation: validate status transition is allowed.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $newStatus = $this->input('status');
            $ticket = $this->route('ticket');

            if (! $newStatus || ! $ticket) {
                return;
            }

            $currentStatus = $ticket->status;

            // Terminal states cannot transition
            if (! isset(self::ALLOWED_TRANSITIONS[$currentStatus])) {
                $validator->errors()->add(
                    'status',
                    "Cannot change status from '{$currentStatus}'. It is a terminal state."
                );

                return;
            }

            // Check if the transition is allowed
            if (! in_array($newStatus, self::ALLOWED_TRANSITIONS[$currentStatus], true)) {
                $allowed = implode(', ', self::ALLOWED_TRANSITIONS[$currentStatus]);
                $validator->errors()->add(
                    'status',
                    "Cannot transition from '{$currentStatus}' to '{$newStatus}'. Allowed transitions: {$allowed}."
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
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be one of: waiting, in_progress, done, cancelled.',
        ];
    }
}
