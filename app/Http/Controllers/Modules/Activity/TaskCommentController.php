<?php

namespace App\Http\Controllers\Modules\Activity;

use App\Http\Controllers\Controller;
use App\Models\Modules\Activity\EmployeeTask;
use App\Models\Modules\Activity\TaskComment;
use App\Notifications\Activity\TaskCommentNotification;
use App\Services\Modules\Activity\ActivityAuthorizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function __construct(
        protected ActivityAuthorizationService $authorizationService,
    ) {}

    /**
     * Redirect back to the task detail modal so comments stay visible.
     */
    protected function redirectToTaskModal(EmployeeTask $task): RedirectResponse
    {
        return redirect()->to(
            route('activity.task.index', ['task' => $task->id, 'modal' => 'detail'])
        );
    }

    /**
     * Store a new comment on a task.
     */
    public function store(Request $request, EmployeeTask $task): RedirectResponse
    {
        $this->authorizeTaskTenant($request, $task);
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $body = trim($validated['body']);

        if ($body === '') {
            return back()->withErrors(['body' => 'The body field is required.']);
        }

        // Check user is creator or participant
        if ($task->created_by !== auth()->id() && ! $task->participants->contains('id', auth()->id())) {
            abort(403);
        }

        // Check task is not cancelled
        if ($task->status === 'cancelled') {
            abort(403, 'Cancelled tasks are read-only');
        }

        $comment = TaskComment::create([
            'employee_task_id' => $task->id,
            'user_id' => auth()->id(),
            'body' => $body,
        ]);

        // Notify all participants + creator except commenter
        $recipients = $task->participants
            ->merge([$task->creator])
            ->filter()
            ->unique('id')
            ->reject(fn ($u) => $u->id === auth()->id());

        foreach ($recipients as $recipient) {
            $recipient->notify(new TaskCommentNotification($comment, auth()->user(), $task));
        }

        return $this->redirectToTaskModal($task);
    }

    /**
     * Update an existing comment.
     */
    public function update(Request $request, EmployeeTask $task, TaskComment $comment): RedirectResponse
    {
        $this->authorizeTaskTenant($request, $task);
        // Check comment belongs to task
        if ($comment->employee_task_id !== $task->id) {
            abort(404);
        }

        // Check user is author
        if ($comment->user_id !== auth()->id()) {
            abort(403);
        }

        // Check task is not cancelled
        if ($task->status === 'cancelled') {
            abort(403, 'Cancelled tasks are read-only');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $body = trim($validated['body']);

        if ($body === '') {
            return back()->withErrors(['body' => 'The body field is required.']);
        }

        $comment->update([
            'body' => $body,
            'edited_at' => now(),
        ]);

        return $this->redirectToTaskModal($task);
    }

    /**
     * Soft-delete a comment.
     */
    public function destroy(EmployeeTask $task, TaskComment $comment): RedirectResponse
    {
        $this->authorizeTaskTenant(request(), $task);
        // Check comment belongs to task
        if ($comment->employee_task_id !== $task->id) {
            abort(404);
        }

        // Check user is author
        if ($comment->user_id !== auth()->id()) {
            abort(403);
        }

        // Check task is not cancelled
        if ($task->status === 'cancelled') {
            abort(403, 'Cancelled tasks are read-only');
        }

        $comment->delete();

        return $this->redirectToTaskModal($task);
    }

    protected function authorizeTaskTenant(Request $request, EmployeeTask $task): void
    {
        $businessUnitId = (int) session('current_business_unit_id');
        abort_unless(
            $businessUnitId > 0
            && (int) $task->business_unit_id === $businessUnitId
            && $this->authorizationService->belongsToBusinessUnit($request->user(), $businessUnitId),
            403,
        );
    }
}
