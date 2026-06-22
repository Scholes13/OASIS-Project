<?php

namespace App\Notifications\Ticket;

use App\Models\Core\User;
use App\Models\Modules\Ticket\Ticket;
use App\Models\Modules\Ticket\TicketComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TicketCommentNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected TicketComment $comment,
        protected User $commenter,
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
            'event' => 'ticket_comment',
            'type' => 'ticket_comment',
            'title' => sprintf('Komentar Baru: %s', $this->ticket->ticket_number),
            'message' => sprintf('%s menambahkan komentar pada ticket: %s', $this->commenter->name, $this->ticket->title),
            'action_url' => route('it-support.admin.tickets.show', $this->ticket),
            'priority' => 'normal',
            'occurred_at' => $this->comment->created_at?->toISOString() ?? now()->toISOString(),
            'actor' => [
                'id' => $this->commenter->id,
                'name' => $this->commenter->name,
            ],
            'entity' => [
                'type' => 'ticket',
                'id' => $this->ticket->id,
                'title' => $this->ticket->title,
            ],
        ];
    }
}
