<?php

namespace App\Notifications\Purchasing\StockRequest;

use App\Models\Modules\Purchasing\StockRequest\StockApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalRejected extends Notification
{
    use Queueable;

    public function __construct(
        protected StockApproval $approval
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

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Stock Request Rejected - ST #'.$this->approval->stockRequest->st_number)
            ->line('Your stock request has been rejected.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $stockRequest = $this->approval->stockRequest;
        $approverName = $this->approval->approver?->name ?? 'Approver';

        return [
            'type' => 'stock_approval_rejected',
            'category' => 'purchasing',
            'event' => 'stock_request_rejected',
            'title' => sprintf('Stock Request %s was rejected', $stockRequest->st_number),
            'message' => sprintf('Your Stock Request %s was rejected by %s.', $stockRequest->st_number, $approverName),
            'action_url' => route('stock-requests.show', $stockRequest),
            'priority' => 'high',
            'occurred_at' => $this->approval->responded_at?->toISOString() ?? now()->toISOString(),
            'st_id' => $stockRequest->id,
            'st_number' => $stockRequest->st_number,
            'approval_id' => $this->approval->id,
            'approver_name' => $approverName,
            'rejection_notes' => $this->approval->notes,
        ];
    }
}
