<?php

namespace App\Notifications\Ticket;

use App\Models\Modules\Ticket\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TicketStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Ticket $ticket,
        protected string $newStatus
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
            'category' => 'it_support',
            'event' => 'ticket_status_changed',
            'type' => 'ticket_status_changed',
            'title' => sprintf('Status Ticket Berubah: %s', $this->ticket->ticket_number),
            'message' => sprintf("Status ticket '%s' berubah menjadi %s", $this->ticket->title, $this->newStatus),
            'action_url' => $this->actionUrl($notifiable),
            'priority' => 'normal',
            'occurred_at' => now()->toISOString(),
            'entity' => [
                'type' => 'ticket',
                'id' => $this->ticket->id,
                'title' => $this->ticket->title,
            ],
        ];
    }

    protected function actionUrl(object $notifiable): string
    {
        $route = (int) $notifiable->id === (int) $this->ticket->requester_id
            ? 'it-support.my-tickets.show'
            : 'it-support.admin.tickets.show';

        return route($route, $this->ticket);
    }
}
