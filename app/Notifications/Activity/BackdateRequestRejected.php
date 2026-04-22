<?php

namespace App\Notifications\Activity;

use App\Models\Modules\Activity\BackdatePermission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BackdateRequestRejected extends Notification
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
        return (new MailMessage)
            ->subject('Backdate Permission Request Rejected')
            ->view('emails.activity.backdate-request-rejected', [
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
            'type' => 'backdate_request_rejected',
            'category' => 'backdate',
            'event' => 'backdate_request_rejected',
            'backdate_permission_id' => $this->backdatePermission->id,
            'rejected_by_id' => $this->backdatePermission->rejected_by,
            'rejected_by_name' => $this->backdatePermission->rejector->name ?? 'Department Head',
            'requested_date' => $this->backdatePermission->requested_date->toISOString(),
            'rejection_reason' => $this->backdatePermission->rejection_reason,
            'rejected_at' => $this->backdatePermission->rejected_at?->toISOString(),
            'title' => 'Your backdate request was rejected',
            'message' => "Your backdate permission request for {$this->backdatePermission->requested_date->format('d M Y')} has been rejected",
            'action_url' => route('activity.backdate.requests'),
            'priority' => 'high',
            'occurred_at' => $this->backdatePermission->rejected_at?->toISOString() ?? now()->toISOString(),
        ];
    }
}
