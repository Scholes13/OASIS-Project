<?php

namespace App\Services\Modules\Ticket;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Core\User;
use App\Models\Modules\Ticket\Ticket;
use App\Notifications\Ticket\TicketAssignedNotification;
use Exception;
use Illuminate\Support\Facades\Cache;

/**
 * Handles ticket assignment workflow.
 *
 * Validates that the target user is an IT Support admin within the
 * ticket's business unit, persists the change, and dispatches an
 * assignment notification to the new assignee (skipping self-assigns
 * to avoid noisy badges).
 */
class TicketAssignmentService
{
    /**
     * Assign a ticket to a user.
     *
     * Validates that the target user has the IT Support admin role
     * within the ticket's business unit scope.  Sends an assignment
     * notification to the new assignee (skipped when the actor assigns
     * the ticket to themselves).
     *
     * @throws Exception
     */
    public function assignTicket(Ticket $ticket, int $userId, ?User $actor = null): Ticket
    {
        $assignee = User::findOrFail($userId);

        // Validate user is IT Support admin in BU scope
        $hasAccess = $assignee->global_role === 'super_admin'
            || $assignee->businessUnits()
                ->where('business_unit_id', $ticket->business_unit_id)
                ->where('is_active', true)
                ->where('is_it_support_admin', true)
                ->exists();

        if (! $hasAccess) {
            throw new Exception(
                'User is not an IT Support admin in this business unit.'
            );
        }

        $previousAssigneeId = $ticket->assigned_to;
        $ticket->update(['assigned_to' => $userId]);
        $fresh = $ticket->fresh();

        if ((int) $previousAssigneeId !== (int) $userId) {
            $this->notifyTicketAssigned($fresh, $assignee, $actor);
        }

        return $fresh;
    }

    /**
     * Send an assignment notification to the assignee.  Skipped when the
     * actor assigns to themselves to avoid self-notify noise.
     */
    protected function notifyTicketAssigned(Ticket $ticket, User $assignee, ?User $actor): void
    {
        if ($actor !== null && (int) $actor->id === (int) $assignee->id) {
            return;
        }

        $assignee->notify(new TicketAssignedNotification($ticket));
        $this->forgetUnreadNotificationCacheFor([$assignee]);
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
