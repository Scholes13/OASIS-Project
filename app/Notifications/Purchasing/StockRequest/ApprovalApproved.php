<?php

namespace App\Notifications\Purchasing\StockRequest;

use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalApproved extends Notification
{
    use Queueable;

    public function __construct(
        protected StockRequest $stockRequest
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
            ->subject('Stock Request Approved - ST #'.$this->stockRequest->st_number)
            ->line('Your stock request has been approved.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'stock_approval_approved',
            'category' => 'purchasing',
            'event' => 'stock_request_approved',
            'title' => sprintf('Stock Request %s was approved', $this->stockRequest->st_number),
            'message' => sprintf('Your Stock Request %s has been fully approved.', $this->stockRequest->st_number),
            'action_url' => route('stock-requests.show', $this->stockRequest),
            'priority' => 'high',
            'occurred_at' => $this->stockRequest->approved_at?->toISOString() ?? now()->toISOString(),
            'st_id' => $this->stockRequest->id,
            'st_number' => $this->stockRequest->st_number,
        ];
    }
}
