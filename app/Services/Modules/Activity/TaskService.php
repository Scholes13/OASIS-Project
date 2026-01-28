<?php

namespace App\Services\Modules\Activity;

use App\Models\Core\User;
use App\Models\Modules\Activity\EmployeeTask;
use App\Models\Modules\Activity\TaskAttachment;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TaskService
{
    public function __construct(
        protected BackdatePermissionService $backdateService
    ) {
    }

    /**
     * Create a new employee task
     *
     * @throws Exception
     */
    public function create(array $data, User $user): EmployeeTask
    {
        // Validate task_date against backdate permission
        if (isset($data['task_date'])) {
            $taskDate = Carbon::parse($data['task_date']);
            
            if (!$this->backdateService->canCreateTaskWithDate($user, $taskDate)) {
                $allowedRange = $this->backdateService->getAllowedDateRange($user);
                throw new Exception(
                    'Task date is outside allowed range. You can only create tasks from ' .
                    $allowedRange['from']->format('Y-m-d') . ' to ' . 
                    $allowedRange['to']->format('Y-m-d') . '. ' .
                    'Request backdate access if you need to create tasks with older dates.'
                );
            }
        }

        return DB::transaction(function () use ($data, $user) {
            $task = EmployeeTask::create([
                'business_unit_id' => session('current_business_unit_id'),
                'department_id' => $user->primary_department_id,
                'created_by' => $user->id,
                'status' => 'planned',
                'activity_type_id' => $data['activity_type_id'],
                'sub_activity_id' => $data['sub_activity_id'] ?? null,
                'task_title' => $data['task_title'],
                'due_date' => $data['due_date'],
                'task_date' => $data['task_date'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
            ]);

            // Auto-add creator as owner participant
            $task->participants()->attach($user->id, [
                'is_owner' => true,
                'joined_at' => $task->created_at,
            ]);

            return $task;
        });
    }

    /**
     * Update an existing task
     *
     * @throws Exception
     */
    public function update(EmployeeTask $task, array $data, User $user): EmployeeTask
    {
        if (! $task->canBeEditedBy($user)) {
            throw new Exception('You do not have permission to edit this task');
        }

        if (in_array($task->status, ['completed', 'cancelled'])) {
            throw new Exception('Cannot edit a completed or cancelled task');
        }

        $task->update([
            'activity_type_id' => $data['activity_type_id'] ?? $task->activity_type_id,
            'sub_activity_id' => $data['sub_activity_id'] ?? $task->sub_activity_id,
            'task_title' => $data['task_title'] ?? $task->task_title,
            'due_date' => $data['due_date'] ?? $task->due_date,
            'notes' => $data['notes'] ?? $task->notes,
        ]);

        return $task->fresh();
    }

    /**
     * Join an existing task
     *
     * @throws Exception
     */
    public function join(EmployeeTask $task, User $user): void
    {
        if ($task->isParticipant($user->id)) {
            throw new Exception('User is already a participant');
        }

        if (in_array($task->status, ['completed', 'cancelled'])) {
            throw new Exception('Cannot join a completed or cancelled task');
        }

        // Joiner gets the same joined_at as task creation time (shared timestamp)
        $task->participants()->attach($user->id, [
            'is_owner' => false,
            'joined_at' => $task->created_at,
        ]);
    }

    /**
     * Leave a task (for non-owners only)
     *
     * @throws Exception
     */
    public function leave(EmployeeTask $task, User $user): void
    {
        if (! $task->isParticipant($user->id)) {
            throw new Exception('User is not a participant');
        }

        if ($task->isOwner($user->id)) {
            throw new Exception('Task owner cannot leave the task');
        }

        if (in_array($task->status, ['completed', 'cancelled'])) {
            throw new Exception('Cannot leave a completed or cancelled task');
        }

        $task->participants()->detach($user->id);
    }

    /**
     * Start a task (sets started_at for ALL participants)
     *
     * @throws Exception
     */
    public function start(EmployeeTask $task, User $user): void
    {
        if (! $task->isParticipant($user->id)) {
            throw new Exception('Only participants can start the task');
        }

        if ($task->status !== 'planned') {
            throw new Exception('Task can only be started from planned status');
        }

        $task->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * Complete a task (sets completed_at for ALL participants)
     *
     * @throws Exception
     */
    public function complete(EmployeeTask $task, User $user): void
    {
        if (! $task->isParticipant($user->id)) {
            throw new Exception('Only participants can complete the task');
        }

        if (! in_array($task->status, ['planned', 'in_progress'])) {
            throw new Exception('Task cannot be completed from current status');
        }

        $completedAt = now();
        $startedAt = $task->started_at ?? $completedAt;
        $duration = $startedAt->diffInMinutes($completedAt);

        $task->update([
            'status' => 'completed',
            'started_at' => $startedAt, // Set if not already set
            'completed_at' => $completedAt,
            'completed_by' => $user->id,
            'duration_minutes' => $duration,
        ]);
    }

    /**
     * Cancel a task (only owner or admin can cancel)
     *
     * @throws Exception
     */
    public function cancel(EmployeeTask $task, string $reason, User $user): void
    {
        if (! $task->isOwner($user->id) && ! $user->isSuperAdmin()) {
            throw new Exception('Only task owner or admin can cancel');
        }

        if (in_array($task->status, ['completed', 'cancelled'])) {
            throw new Exception('Task is already completed or cancelled');
        }

        $task->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Add attachment to task
     *
     * @throws Exception
     */
    public function addAttachment(EmployeeTask $task, UploadedFile $file, User $user): TaskAttachment
    {
        if (! $task->isParticipant($user->id)) {
            throw new Exception('Only participants can add attachments');
        }

        if (in_array($task->status, ['completed', 'cancelled'])) {
            throw new Exception('Cannot add attachments to completed or cancelled task');
        }

        // Check attachment limit
        if ($task->attachments()->count() >= TaskAttachment::MAX_ATTACHMENTS_PER_TASK) {
            throw new Exception('Maximum attachments limit reached ('.TaskAttachment::MAX_ATTACHMENTS_PER_TASK.')');
        }

        // Validate file type
        if (! TaskAttachment::isValidType($file->getMimeType())) {
            throw new Exception('Invalid file type. Allowed: images, PDF, Word, Excel');
        }

        // Validate file size
        if (! TaskAttachment::isValidSize($file->getSize())) {
            throw new Exception('File size exceeds maximum limit (5MB)');
        }

        // Store file
        $path = $file->store('task-attachments/'.$task->id, 'public');

        return TaskAttachment::create([
            'employee_task_id' => $task->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $user->id,
            'created_at' => now(),
        ]);
    }

    /**
     * Remove attachment from task
     *
     * @throws Exception
     */
    public function removeAttachment(TaskAttachment $attachment, User $user): void
    {
        $task = $attachment->employeeTask;

        // Only uploader, task owner, or admin can remove
        $canRemove = $attachment->uploaded_by === $user->id
            || $task->isOwner($user->id)
            || $user->isSuperAdmin();

        if (! $canRemove) {
            throw new Exception('You do not have permission to remove this attachment');
        }

        if (in_array($task->status, ['completed', 'cancelled'])) {
            throw new Exception('Cannot remove attachments from completed or cancelled task');
        }

        // Delete file from storage
        Storage::disk('public')->delete($attachment->file_path);

        // Delete record
        $attachment->delete();
    }

    /**
     * Delete a task (only owner or admin)
     *
     * @throws Exception
     */
    public function delete(EmployeeTask $task, User $user): void
    {
        if (! $task->isOwner($user->id) && ! $user->isSuperAdmin()) {
            throw new Exception('Only task owner or admin can delete');
        }

        // Delete all attachments from storage
        foreach ($task->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        // Delete task (cascades to participants and attachments)
        $task->delete();
    }
}
