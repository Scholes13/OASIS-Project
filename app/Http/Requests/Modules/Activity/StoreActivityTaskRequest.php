<?php

namespace App\Http\Requests\Modules\Activity;

use App\Models\Core\Department;
use App\Services\Modules\Activity\BackdatePermissionService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class StoreActivityTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $participantIds = $this->input('participant_ids', []);

        if (! is_array($participantIds)) {
            $participantIds = [];
        }

        $this->merge([
            'participant_ids' => array_values(array_filter(
                $participantIds,
                static fn ($id): bool => ! empty($id) && is_numeric($id) && (int) $id > 0
            )),
        ]);
    }

    public function rules(): array
    {
        $status = $this->input('status');
        $taskDate = $this->input('task_date');
        $taskDateCarbon = $this->parseDateSafely($taskDate);
        $requiresExplicitStartTime = $status === 'in_progress'
            && $taskDateCarbon
            && ! $taskDateCarbon->isSameDay(now());

        $startTimeRule = 'nullable|date_format:H:i';
        $endTimeRule = 'nullable|date_format:H:i';
        $completedDateRule = 'nullable|date';

        if ($status === 'completed' || $requiresExplicitStartTime) {
            $startTimeRule = 'required|date_format:H:i';
        }

        if ($status === 'completed') {
            $endTimeRule = 'required|date_format:H:i';
            $completedDateRule = 'required|date|after_or_equal:'.$taskDate;
        }

        $departmentId = session('current_department_id') ?? Auth::user()->getCurrentDepartmentId();
        $validActivityTypeIds = $this->getValidActivityTypeIds($departmentId);

        return [
            'task_title' => 'required|string|max:255',
            'task_description' => 'nullable|string',
            'activity_type_id' => [
                'required',
                'exists:employee_activity_types,id',
                function ($attribute, $value, $fail) use ($validActivityTypeIds) {
                    if (! in_array((int) $value, $validActivityTypeIds, true)) {
                        $fail('The selected activity type is not assigned to your department.');
                    }
                },
            ],
            'sub_activity_id' => 'nullable|exists:employee_sub_activities,id',
            'status' => 'required|in:planned,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high',
            'task_date' => 'required|date',
            'due_date' => $status === 'completed' ? 'nullable|date' : 'required|date|after_or_equal:task_date',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'exists:users,id',
            'start_time' => $startTimeRule,
            'end_time' => $endTimeRule,
            'completed_date' => $completedDateRule,
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $taskDate = $this->input('task_date');
            $status = $this->input('status');
            $taskDateCarbon = $this->parseDateSafely($taskDate);

            if (! $taskDateCarbon) {
                return;
            }

            // Validate backdate permission
            $backdateService = app(BackdatePermissionService::class);
            $user = Auth::user();

            if (! $backdateService->canCreateTaskWithDate($user, $taskDateCarbon)) {
                $allowedRange = $backdateService->getAllowedDateRange($user);
                $validator->errors()->add(
                    'task_date',
                    'Task date is outside allowed range. You can only create tasks from '.
                    $allowedRange['from']->format('Y-m-d').' to '.
                    $allowedRange['to']->format('Y-m-d').'. '.
                    'Request backdate access if you need to create tasks with older dates.'
                );
            }

            // Validate end_time > start_time when completed_date equals task_date
            if ($status === 'completed') {
                $completedDate = $this->input('completed_date') ?? $taskDate;
                $startTime = $this->input('start_time');
                $endTime = $this->input('end_time');

                if ($completedDate === $taskDate && ! empty($startTime) && ! empty($endTime)) {
                    if ($endTime <= $startTime) {
                        $validator->errors()->add('end_time', 'Waktu selesai harus setelah waktu mulai.');
                    }
                }
            }
        });
    }

    protected function getValidActivityTypeIds(?int $departmentId): array
    {
        if (! $departmentId) {
            return [];
        }

        return Department::find($departmentId)
            ?->activeActivityTypes()
            ->pluck('employee_activity_types.id')
            ->toArray() ?? [];
    }

    protected function parseDateSafely(?string $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
