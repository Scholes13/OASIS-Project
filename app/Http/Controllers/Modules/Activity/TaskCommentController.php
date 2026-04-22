<?php

namespace App\Http\Controllers\Modules\Activity;

use App\Http\Controllers\Controller;
use App\Models\Modules\Activity\EmployeeTask;
use App\Models\Modules\Activity\TaskComment;
use App\Notifications\Activity\TaskCommentNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    /**
     * Store a new comment on a task.
     */
    public function store(Request $request, EmployeeTask $task): RedirectResponse
    {
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
            ->unique('id')
            ->reject(fn ($u) => $u->id === auth()->id());

        foreach ($recipients as $recipient) {
            $recipient->notify(new TaskCommentNotification($comment, auth()->user(), $task));
        }

        return back();
    }

    /**
     * Update an existing comment.
     */
    public function update(Request $request, EmployeeTask $task, TaskComment $comment): RedirectResponse
    {
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

        return back();
    }

    /**
     * Soft-delete a comment.
     */
    public function destroy(EmployeeTask $task, TaskComment $comment): RedirectResponse
    {
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

        return back();
    }
}
