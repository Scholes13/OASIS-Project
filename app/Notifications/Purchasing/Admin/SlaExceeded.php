<?php

namespace App\Notifications\Purchasing\Admin;

use App\Models\Modules\Purchasing\Admin\AdminTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SlaExceeded extends Notification
{
    use Queueable;

    protected AdminTask $task;
    protected string $slaType; // 'followup' or 'completion'

    public function __construct(AdminTask $task, string $slaType)
    {
        $this->task = $task;
        $this->slaType = $slaType;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $taskable = $this->task->taskable;
        $taskType = $this->getTaskType();
        $taskNumber = $this->getTaskNumber();
        $slaLabel = $this->slaType === 'followup' ? 'Follow-up' : 'Completion';

        return (new MailMessage)
            ->subject("SLA Alert: {$slaLabel} Time Exceeded - {$taskType} #{$taskNumber}")
            ->view('emails.purchasing.admin.sla-exceeded', [
                'task' => $this->task,
                'recipient' => $notifiable,
                'taskable' => $taskable,
                'taskType' => $taskType,
                'taskNumber' => $taskNumber,
                'slaType' => $this->slaType,
                'slaLabel' => $slaLabel,
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
        $slaLabel = $this->slaType === 'followup' ? 'Follow-up' : 'Completion';

        return [
            'type' => 'sla_exceeded',
            'sla_type' => $this->slaType,
            'task_id' => $this->task->id,
            'taskable_type' => $this->task->taskable_type,
            'taskable_id' => $this->task->taskable_id,
            'task_type' => $taskType,
            'task_number' => $taskNumber,
            'business_unit_id' => $this->task->business_unit_id,
            'department_id' => $this->task->department_id,
            'assigned_admin_id' => $this->task->assigned_admin_id,
            'status' => $this->task->status,
            'estimated_amount' => $this->task->estimated_total_price,
            'entered_at' => $this->task->entered_at->toISOString(),
            'started_at' => $this->task->started_at?->toISOString(),
            'message' => "SLA Alert: {$slaLabel} time exceeded for {$taskType} #{$taskNumber}",
            'action_url' => url('/purchasing/admin/tasks/' . $this->task->id),
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
