<?php

namespace App\Http\Requests\Modules\Activity;

use App\Models\Core\Department;
use App\Models\Modules\Activity\EmployeeTask;
use App\Services\Modules\Activity\BackdatePermissionService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class UpdateActivityTaskRequest extends FormRequest
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
        $task = $this->route('task');
        $status = $this->input('status');
        $submittedTaskDate = $this->input('task_date');
        $submittedTaskDateCarbon = $this->parseDateSafely($submittedTaskDate);

        // Detect partial update
        $isPartialUpdate = $this->has('due_date') && ! $this->has('task_title');
        $isStatusUpdate = $this->has('status') && ! $this->has('task_title');

        if ($isPartialUpdate || $isStatusUpdate) {
            return [
                'due_date' => 'sometimes|date',
                'status' => 'sometimes|in:planned,in_progress,completed,cancelled',
                'confirm_reset_execution' => 'sometimes|boolean',
            ];
        }

        // Full update validation
        $departmentId = $task->department_id ?? Auth::user()->getCurrentDepartmentId();
        $validActivityTypeIds = $this->getValidActivityTypeIds($departmentId);

        // Determine if correction is needed
        $needsStartTimeCorrection = $this->needsStartTimeCorrection($task, $submittedTaskDateCarbon, $status);
        $needsCompletionCorrection = $this->needsCompletionCorrection($task, $submittedTaskDate, $status);

        $startTimeRule = $needsStartTimeCorrection ? 'required|date_format:H:i' : 'nullable|date_format:H:i';
        $endTimeRule = $needsCompletionCorrection ? 'required|date_format:H:i' : 'nullable|date_format:H:i';
        $completedDateRule = $needsCompletionCorrection ? 'required|date|after_or_equal:task_date' : 'nullable|date';

        return [
            'task_title' => 'required|string|max:255',
            'task_description' => 'nullable|string',
            'activity_type_id' => [
                'required',
                'exists:employee_activity_types,id',
                function ($attribute, $value, $fail) use ($validActivityTypeIds) {
                    if (! in_array((int) $value, $validActivityTypeIds, true)) {
                        $fail('The selected activity type is not assigned to this department.');
                    }
                },
            ],
            'sub_activity_id' => 'nullable|exists:employee_sub_activities,id',
            'status' => 'required|in:planned,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high',
            'task_date' => 'required|date',
            'due_date' => $status === 'completed' ? 'nullable|date' : 'required|date|after_or_equal:task_date',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'nullable|integer|exists:users,id',
            'start_time' => $startTimeRule,
            'end_time' => $endTimeRule,
            'completed_date' => $completedDateRule,
            'confirm_reset_execution' => 'sometimes|boolean',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $task = $this->route('task');
            $submittedTaskDate = $this->input('task_date');
            $status = $this->input('status');

            // Skip additional validation for partial updates
            $isPartialUpdate = $this->has('due_date') && ! $this->has('task_title');
            $isStatusUpdate = $this->has('status') && ! $this->has('task_title');

            if ($isPartialUpdate) {
                if ($this->filled('due_date') && $task->task_date && $this->input('due_date') < $task->task_date->format('Y-m-d')) {
                    $validator->errors()->add('due_date', 'The due date field must be a date after or equal to task date.');
                }

                return;
            }

            if ($isStatusUpdate) {
                if ($status === 'planned' && ($task->started_at || $task->completed_at) && ! $this->boolean('confirm_reset_execution')) {
                    $validator->errors()->add('status', 'Resetting to planned requires confirmation because it clears execution history.');
                }

                // Validate quick action guards
                $this->validateQuickAction($validator, $task, $status);

                return;
            }

            if ($status === 'planned' && ($task->started_at || $task->completed_at) && ! $this->boolean('confirm_reset_execution')) {
                $validator->errors()->add('status', 'Resetting to planned requires confirmation because it clears execution history.');
            }

            // Validate backdate permission using submitted task_date
            $taskDateCarbon = $this->parseDateSafely($submittedTaskDate);
            if (! $taskDateCarbon) {
                return;
            }

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
                $completedDate = $this->input('completed_date') ?? $submittedTaskDate;
                $startTime = $this->input('start_time');
                $endTime = $this->input('end_time');

                if ($completedDate === $submittedTaskDate && ! empty($startTime) && ! empty($endTime)) {
                    if ($endTime <= $startTime) {
                        $validator->errors()->add('end_time', 'Waktu selesai harus setelah waktu mulai.');
                    }
                }
            }
        });
    }

    protected function needsStartTimeCorrection(EmployeeTask $task, ?Carbon $submittedDate, string $status): bool
    {
        if (! $submittedDate || ($status !== 'in_progress' && $status !== 'completed')) {
            return false;
        }

        if ($status === 'completed') {
            if (! $task->started_at) {
                return true;
            }

            return $submittedDate->format('Y-m-d') !== $task->started_at->format('Y-m-d');
        }

        if (! $task->started_at) {
            return ! $submittedDate->isSameDay(now());
        }

        return false;
    }

    protected function needsCompletionCorrection(EmployeeTask $task, ?string $submittedTaskDate, string $status): bool
    {
        if ($status !== 'completed') {
            return false;
        }

        if (! $task->completed_at) {
            return true;
        }

        if (! $this->filled('completed_date')) {
            return false;
        }

        return $this->input('completed_date') !== $task->completed_at->format('Y-m-d');
    }

    protected function validateQuickAction(Validator $validator, EmployeeTask $task, string $status): void
    {
        $today = now()->startOfDay();
        $taskDate = Carbon::parse($task->task_date)->startOfDay();

        // Block quick start for future tasks (historical quick start is allowed)
        if ($status === 'in_progress' && ! $task->started_at && $taskDate->isAfter($today)) {
            $validator->errors()->add('status', 'Task uses a historical or future date. Please confirm actual execution time.');
        }

        // Block direct complete (planned -> completed) for non-today tasks without started_at
        if ($status === 'completed' && ! $task->started_at && ! $taskDate->isSameDay($today)) {
            $validator->errors()->add('status', 'Task uses a historical date. Please confirm actual execution time.');
        }
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
