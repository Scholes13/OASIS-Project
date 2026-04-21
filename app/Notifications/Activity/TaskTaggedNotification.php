<?php

namespace App\Notifications\Activity;

use App\Models\Core\User;
use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskTaggedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected EmployeeTask $task,
        protected User $actor
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You were tagged in an activity')
            ->line('You were tagged in an activity.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'activity',
            'event' => 'task_tagged',
            'type' => 'task_tagged',
            'title' => sprintf('You were tagged by %s in Activity %s', $this->actor->name, $this->task->task_title),
            'message' => sprintf('%s tagged you in %s.', $this->actor->name, $this->task->task_title),
            'action_url' => route('activity.task.show', $this->task),
            'priority' => 'normal',
            'occurred_at' => now()->toISOString(),
            'actor' => [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
            ],
            'entity' => [
                'type' => 'activity_task',
                'id' => $this->task->id,
                'title' => $this->task->task_title,
            ],
            'task_id' => $this->task->id,
        ];
    }
}
