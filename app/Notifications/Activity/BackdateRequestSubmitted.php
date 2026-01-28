<?php

namespace App\Notifications\Activity;

use App\Models\Modules\Activity\BackdatePermission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BackdateRequestSubmitted extends Notification
{
    use Queueable;

    protected BackdatePermission $backdatePermission;

    public function __construct(BackdatePermission $backdatePermission)
    {
        $this->backdatePermission = $backdatePermission;
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
        return (new MailMessage)
            ->subject('Backdate Permission Request - ' . $this->backdatePermission->requester->name)
            ->view('emails.activity.backdate-request-submitted', [
                'backdatePermission' => $this->backdatePermission,
                'departmentHead' => $notifiable,
            ]);
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'backdate_request_submitted',
            'backdate_permission_id' => $this->backdatePermission->id,
            'requester_id' => $this->backdatePermission->user_id,
            'requester_name' => $this->backdatePermission->requester->name,
            'requested_date' => $this->backdatePermission->requested_date->toISOString(),
            'reason' => $this->backdatePermission->reason,
            'department_name' => $this->backdatePermission->department->name ?? 'N/A',
            'message' => "{$this->backdatePermission->requester->name} has requested backdate permission for {$this->backdatePermission->requested_date->format('d M Y')}",
            'action_url' => route('activity.backdate-approvals'),
        ];
    }
}
