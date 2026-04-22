<?php

namespace App\Notifications\Purchasing\Admin;

use App\Models\Modules\Purchasing\Admin\AdminTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification
{
    use Queueable;

    protected AdminTask $task;

    public function __construct(AdminTask $task)
    {
        $this->task = $task;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $taskable = $this->task->taskable;
        $taskType = $this->getTaskType();
        $taskNumber = $this->getTaskNumber();

        return (new MailMessage)
            ->subject("New Admin Task Assigned - {$taskType} #{$taskNumber}")
            ->view('emails.purchasing.admin.task-assigned', [
                'task' => $this->task,
                'admin' => $notifiable,
                'taskable' => $taskable,
                'taskType' => $taskType,
                'taskNumber' => $taskNumber,
            ]);
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        $taskable = $this->task->taskable;
        $taskType = $this->getTaskType();
        $taskNumber = $this->getTaskNumber();

        return [
            'type' => 'task_assigned',
            'category' => 'purchasing',
            'event' => 'admin_task_assigned',
            'task_id' => $this->task->id,
            'taskable_type' => $this->task->taskable_type,
            'taskable_id' => $this->task->taskable_id,
            'task_type' => $taskType,
            'task_number' => $taskNumber,
            'business_unit_id' => $this->task->business_unit_id,
            'department_id' => $this->task->department_id,
            'estimated_amount' => $this->task->estimated_total_price,
            'entered_at' => $this->task->entered_at->toISOString(),
            'title' => "{$taskType} admin task {$taskNumber} was assigned to you",
            'message' => "New {$taskType} admin task #{$taskNumber} has been assigned to you",
            'action_url' => url('/purchasing/admin/tasks/'.$this->task->id),
            'priority' => 'high',
            'occurred_at' => $this->task->updated_at?->toISOString() ?? now()->toISOString(),
        ];
    }

    /**
     * Get the task type label
     */
    protected function getTaskType(): string
    {
        if (str_contains($this->task->taskable_type, 'PurchaseRequest')) {
            return 'PR';
        }

        if (str_contains($this->task->taskable_type, 'StockRequest')) {
            return 'ST';
        }

        return 'Task';
    }

    /**
     * Get the task number from the taskable
     */
    protected function getTaskNumber(): string
    {
        $taskable = $this->task->taskable;

        if (isset($taskable->pr_number)) {
            return $taskable->pr_number;
        }

        if (isset($taskable->st_number)) {
            return $taskable->st_number;
        }

        return (string) $this->task->id;
    }
}
