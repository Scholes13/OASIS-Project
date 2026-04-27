<?php

namespace App\Notifications\Ticket;

use App\Models\Core\User;
use App\Models\Modules\Ticket\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Ticket $ticket,
        protected User $requester
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
            'event' => 'ticket_created',
            'type' => 'ticket_created',
            'title' => sprintf('Ticket Baru: %s', $this->ticket->ticket_number),
            'message' => sprintf('%s membuat ticket: %s', $this->requester->name, $this->ticket->title),
            'action_url' => route('it-support.admin.tickets.show', $this->ticket),
            'priority' => 'normal',
            'occurred_at' => $this->ticket->created_at?->toISOString() ?? now()->toISOString(),
            'actor' => [
                'id' => $this->requester->id,
                'name' => $this->requester->name,
            ],
            'entity' => [
                'type' => 'ticket',
                'id' => $this->ticket->id,
                'title' => $this->ticket->title,
            ],
        ];
    }
}
