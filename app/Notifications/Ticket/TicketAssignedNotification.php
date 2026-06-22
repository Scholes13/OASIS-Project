<?php

namespace App\Notifications\Ticket;

use App\Models\Modules\Ticket\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Ticket $ticket
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
            'event' => 'ticket_assigned',
            'type' => 'ticket_assigned',
            'title' => sprintf('Ticket Ditugaskan: %s', $this->ticket->ticket_number),
            'message' => sprintf('Anda ditugaskan untuk menangani ticket: %s', $this->ticket->title),
            'action_url' => route('it-support.admin.tickets.show', $this->ticket),
            'priority' => 'normal',
            'occurred_at' => now()->toISOString(),
            'entity' => [
                'type' => 'ticket',
                'id' => $this->ticket->id,
                'title' => $this->ticket->title,
            ],
        ];
    }
}
