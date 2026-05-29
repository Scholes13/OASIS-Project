<?php

namespace App\Services\Modules\Ticket;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Core\User;
use App\Models\Modules\Ticket\Ticket;
use App\Models\Modules\Ticket\TicketAttachment;
use App\Models\Modules\Ticket\TicketComment;
use App\Notifications\Ticket\TicketCommentNotification;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/**
 * Handles ticket commentary and attachment persistence.
 *
 * Owns comment creation, the comment-fanout notification (private
 * comments are scoped to IT Support recipients), and attachment
 * uploads (which may belong to a parent comment).
 */
class TicketCommentService
{
    /**
     * Add a comment to a ticket.
     *
     * Notifies the requester and assignee (excluding the commenter).
     * Private comments only fan out to IT Support recipients — the
     * requester never sees them.
     */
    public function addComment(
        Ticket $ticket,
        User $user,
        string $content,
        bool $isPrivate = false
    ): TicketComment {
        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'content' => $content,
            'is_private' => $isPrivate,
        ]);

        $this->notifyTicketComment($ticket, $comment, $user, $isPrivate);

        return $comment;
    }

    /**
     * Add an attachment to a ticket.
     *
     * Files are stored on the private `local` disk so they can only be
     * served through the authenticated download endpoint.  This avoids
     * exposing potentially sensitive ticket evidence under public URLs.
     *
     * @throws Exception
     */
    public function addAttachment(
        Ticket $ticket,
        UploadedFile $file,
        ?User $uploader = null,
        ?int $commentId = null
    ): TicketAttachment {
        $disk = 'local';
        $path = $file->store('ticket-attachments/'.$ticket->id, $disk);

        return TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'comment_id' => $commentId,
            'filename' => basename($path),
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'disk' => $disk,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $uploader?->id,
        ]);
    }

    /**
     * Build the recipient list for a ticket comment and dispatch the
     * notification.  The requester is excluded from private comments so
     * IT staff can keep internal context inside the ticket.
     */
    protected function notifyTicketComment(Ticket $ticket, TicketComment $comment, User $commenter, bool $isPrivate): void
    {
        $recipients = $this->collectTicketWatchers($ticket, $commenter, includeRequester: ! $isPrivate);

        if (empty($recipients)) {
            return;
        }

        Notification::send($recipients, new TicketCommentNotification($comment, $commenter, $ticket));
        $this->forgetUnreadNotificationCacheFor($recipients);
    }

    /**
     * Collect the users that should be notified about ticket activity.
     *
     * Currently the requester and the assigned IT Support staff member.
     * The actor is always excluded to avoid self-notify noise.
     *
     * @return list<User>
     */
    protected function collectTicketWatchers(Ticket $ticket, ?User $actor = null, bool $includeRequester = true): array
    {
        $userIds = [];

        if ($includeRequester && $ticket->requester_id) {
            $userIds[(int) $ticket->requester_id] = (int) $ticket->requester_id;
        }

        if ($ticket->assigned_to) {
            $userIds[(int) $ticket->assigned_to] = (int) $ticket->assigned_to;
        }

        if ($actor !== null) {
            unset($userIds[(int) $actor->id]);
        }

        if (empty($userIds)) {
            return [];
        }

        return User::whereIn('id', array_values($userIds))->get()->all();
    }

    /**
     * Forget the cached unread badge for the given recipients so the
     * Inertia bell reflects the new notification on the next render.
     *
     * @param  iterable<User>  $recipients
     */
    protected function forgetUnreadNotificationCacheFor(iterable $recipients): void
    {
        foreach ($recipients as $recipient) {
            if ($recipient instanceof User && $recipient->id) {
                Cache::forget(HandleInertiaRequests::unreadNotificationsCacheKey((int) $recipient->id));
            }
        }
    }
}
