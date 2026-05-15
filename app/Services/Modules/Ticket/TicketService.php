<?php

namespace App\Services\Modules\Ticket;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Core\User;
use App\Models\Modules\Ticket\Ticket;
use App\Models\Modules\Ticket\TicketAttachment;
use App\Models\Modules\Ticket\TicketComment;
use App\Notifications\Ticket\TicketAssignedNotification;
use App\Notifications\Ticket\TicketCommentNotification;
use App\Notifications\Ticket\TicketStatusChangedNotification;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class TicketService
{
    /**
     * Allowed status transitions.
     *
     * Terminal statuses (done, cancelled) have no outgoing transitions.
     *
     * @var array<string, list<string>>
     */
    protected const STATUS_TRANSITIONS = [
        'waiting' => ['in_progress', 'cancelled'],
        'in_progress' => ['done', 'cancelled'],
        'done' => [],
        'cancelled' => [],
    ];

    public function __construct(
        protected TicketNumberService $ticketNumberService,
        protected SlaService $slaService
    ) {}

    /**
     * Create a new ticket with number generation and duplicate prevention.
     *
     * @throws Exception
     */
    public function createTicket(array $data, User $creator, int $buId): Ticket
    {
        // Duplicate prevention via form_token + cache
        if (isset($data['form_token'])) {
            $cacheKey = "ticket_form_token:{$buId}:{$data['form_token']}";

            if (Cache::has($cacheKey)) {
                $existingId = Cache::get($cacheKey);
                $existing = Ticket::find($existingId);

                if ($existing) {
                    return $existing;
                }
            }
        }

        return DB::transaction(function () use ($data, $creator, $buId) {
            $ticketNumber = $this->ticketNumberService->generateTicketNumber($buId);

            $ticket = Ticket::create([
                'business_unit_id' => $buId,
                'ticket_number' => $ticketNumber,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'requester_id' => $data['requester_id'] ?? $creator->id,
                'department_id' => $data['department_id'] ?? null,
                'status' => 'waiting',
                'priority' => $data['priority'] ?? 'medium',
                'category_id' => $data['category_id'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? null,
                'created_by' => $creator->id,
                'follow_up_at' => $data['follow_up_at'] ?? null,
                'form_token' => $data['form_token'] ?? null,
            ]);

            // Cache the form_token to prevent duplicate submissions (TTL: 10 minutes)
            if (isset($data['form_token'])) {
                $cacheKey = "ticket_form_token:{$buId}:{$data['form_token']}";
                Cache::put($cacheKey, $ticket->id, now()->addMinutes(10));
            }

            return $ticket;
        });
    }

    /**
     * Update ticket fields.
     *
     * @throws Exception
     */
    public function updateTicket(Ticket $ticket, array $data): Ticket
    {
        $ticket->update([
            'title' => $data['title'] ?? $ticket->title,
            'description' => $data['description'] ?? $ticket->description,
            'priority' => $data['priority'] ?? $ticket->priority,
            'category_id' => $data['category_id'] ?? $ticket->category_id,
            'department_id' => $data['department_id'] ?? $ticket->department_id,
            'follow_up_at' => $data['follow_up_at'] ?? $ticket->follow_up_at,
        ]);

        return $ticket->fresh();
    }

    /**
     * Change ticket status with transition validation.
     *
     * Notifies the requester (and the assigned staff member if any other
     * than the actor) so the people watching the ticket actually see the
     * progress.  Self-actions are skipped to avoid noisy badges.
     *
     * @throws Exception
     */
    public function changeStatus(Ticket $ticket, string $newStatus, ?User $user = null): Ticket
    {
        $currentStatus = $ticket->status;
        $allowed = self::STATUS_TRANSITIONS[$currentStatus] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw new Exception(
                "Invalid status transition from '{$currentStatus}' to '{$newStatus}'."
            );
        }

        $updateData = ['status' => $newStatus];

        // Set resolved_at when ticket is marked as done
        if ($newStatus === 'done') {
            $updateData['resolved_at'] = now();
        }

        $ticket->update($updateData);
        $fresh = $ticket->fresh();

        $this->notifyTicketStatusChange($fresh, $newStatus, $user);

        return $fresh;
    }

    /**
     * Send the status change notification to the watchers (requester +
     * assignee), excluding the user that triggered the change.
     */
    protected function notifyTicketStatusChange(Ticket $ticket, string $newStatus, ?User $actor): void
    {
        $recipients = $this->collectTicketWatchers($ticket, $actor);

        if (empty($recipients)) {
            return;
        }

        Notification::send($recipients, new TicketStatusChangedNotification($ticket, $newStatus));
        $this->forgetUnreadNotificationCacheFor($recipients);
    }

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
     * Get dashboard metrics for the given business units and optional date range.
     *
     * @return array<string, mixed>
     */
    public function getDashboardMetrics(
        array $buIds,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        $query = Ticket::forBusinessUnits($buIds);

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo.' 23:59:59');
        }

        $tickets = $query->get();

        // Preload SLA settings once for the BU scope so the breach loop
        // below does not run a TicketSlaSettings lookup per ticket.
        Ticket::preloadSlaSettings($buIds);

        // Summary cards
        $total = $tickets->count();
        $byStatus = $tickets->groupBy('status')->map->count();
        $byPriority = $tickets->groupBy('priority')->map->count();

        // By category — frontend expects {name, count, color}
        $byCategory = Ticket::forBusinessUnits($buIds)
            ->select('category_id', DB::raw('count(*) as count'))
            ->whereNotNull('category_id')
            ->when($dateFrom, fn ($q) => $q->where('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('created_at', '<=', $dateTo.' 23:59:59'))
            ->groupBy('category_id')
            ->with('category:id,name,color')
            ->get()
            ->map(fn ($item) => [
                'name' => $item->category?->name ?? 'Uncategorized',
                'count' => $item->count,
                'color' => $item->category?->color ?? '#6b7280',
            ])
            ->values()
            ->all();

        // By assigned staff — frontend expects {name, count}
        $byStaff = Ticket::forBusinessUnits($buIds)
            ->select('assigned_to', DB::raw('count(*) as count'))
            ->whereNotNull('assigned_to')
            ->when($dateFrom, fn ($q) => $q->where('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('created_at', '<=', $dateTo.' 23:59:59'))
            ->groupBy('assigned_to')
            ->with('assignedUser:id,name')
            ->get()
            ->map(fn ($item) => [
                'name' => $item->assignedUser?->name ?? 'Unassigned',
                'count' => $item->count,
            ])
            ->values()
            ->all();

        // SLA breach count
        $slaBreachCount = $tickets
            ->filter(fn (Ticket $ticket): bool => $ticket->isSlaBreach())
            ->count();

        // Recent tickets (last 10)
        $recentTickets = Ticket::forBusinessUnits($buIds)
            ->with(['requester', 'assignedUser', 'category'])
            ->latest()
            ->limit(10)
            ->get();

        return [
            'total' => $total,
            'by_status' => [
                'waiting' => $byStatus->get('waiting', 0),
                'in_progress' => $byStatus->get('in_progress', 0),
                'done' => $byStatus->get('done', 0),
                'cancelled' => $byStatus->get('cancelled', 0),
            ],
            'by_priority' => [
                'low' => $byPriority->get('low', 0),
                'medium' => $byPriority->get('medium', 0),
                'high' => $byPriority->get('high', 0),
                'critical' => $byPriority->get('critical', 0),
            ],
            'by_category' => $byCategory,
            'by_staff' => $byStaff,
            'sla_breach_count' => $slaBreachCount,
            'recent_tickets' => $recentTickets,
        ];
    }
}
