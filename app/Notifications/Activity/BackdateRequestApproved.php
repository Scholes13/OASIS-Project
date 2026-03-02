<?php

namespace App\Notifications\Activity;

use App\Models\Modules\Activity\BackdatePermission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BackdateRequestApproved extends Notification
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
            ->subject('Backdate Permission Approved')
            ->view('emails.activity.backdate-request-approved', [
                'backdatePermission' => $this->backdatePermission,
                'requester' => $notifiable,
            ]);
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'backdate_request_approved',
            'backdate_permission_id' => $this->backdatePermission->id,
            'approved_by_id' => $this->backdatePermission->approved_by,
            'approved_by_name' => $this->backdatePermission->approver->name ?? 'Department Head',
            'requested_date' => $this->backdatePermission->requested_date->toISOString(),
            'granted_until' => $this->backdatePermission->granted_until?->toISOString(),
            'approved_at' => $this->backdatePermission->approved_at?->toISOString(),
            'message' => "Your backdate permission request for {$this->backdatePermission->requested_date->format('d M Y')} has been approved",
            'action_url' => route('activity.backdate.requests'),
        ];
    }
}
