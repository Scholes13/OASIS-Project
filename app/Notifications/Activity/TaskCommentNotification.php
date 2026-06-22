<?php

namespace App\Notifications\Activity;

use App\Models\Core\User;
use App\Models\Modules\Activity\EmployeeTask;
use App\Models\Modules\Activity\TaskComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TaskCommentNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected TaskComment $comment,
        protected User $commenter,
        protected EmployeeTask $task
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'activity',
            'event' => 'task_comment',
            'type' => 'task_comment',
            'title' => sprintf('%s commented on %s', $this->commenter->name, $this->task->task_title),
            'message' => Str::limit($this->comment->body, 100),
            'action_url' => route('activity.task.show', $this->task),
            'priority' => 'normal',
            'occurred_at' => $this->comment->created_at->toISOString(),
            'actor' => [
                'id' => $this->commenter->id,
                'name' => $this->commenter->name,
            ],
            'entity' => [
                'type' => 'activity_task',
                'id' => $this->task->id,
                'title' => $this->task->task_title,
            ],
        ];
    }
}
