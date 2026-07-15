<?php

namespace App\Services\Modules\Ticket;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\Ticket\Ticket;
use App\Models\Modules\Ticket\TicketAttachment;
use App\Models\Modules\Ticket\TicketCategory;
use App\Models\Modules\Ticket\TicketComment;
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
        protected TicketAssignmentService $assignmentService,
        protected TicketCommentService $commentService
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
            $requesterId = $data['requester_id'] ?? $creator->id;
            $requester = (int) $requesterId === (int) $creator->id
                ? $creator
                : User::findOrFail($requesterId);
            $requesterAssignment = $requester->businessUnits()
                ->where('business_unit_id', $buId)
                ->where('is_active', true)
                ->first();

            if (! $requesterAssignment || ! $requesterAssignment->department_id) {
                throw new Exception('Requester does not have an active department in the selected business unit.');
            }

            $this->assertCategoryBelongsToBusinessUnit($data['category_id'] ?? null, $buId);

            $ticket = Ticket::create([
                'business_unit_id' => $buId,
                'ticket_number' => $ticketNumber,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'requester_id' => $requesterId,
                'department_id' => $requesterAssignment->department_id,
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
        $businessUnitId = (int) $ticket->business_unit_id;
        $this->assertCategoryBelongsToBusinessUnit(
            $data['category_id'] ?? $ticket->category_id,
            $businessUnitId
        );
        $this->assertDepartmentBelongsToBusinessUnit(
            $data['department_id'] ?? $ticket->department_id,
            $businessUnitId
        );

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

    protected function assertCategoryBelongsToBusinessUnit(?int $categoryId, int $businessUnitId): void
    {
        if ($categoryId !== null && ! TicketCategory::whereKey($categoryId)
            ->where('business_unit_id', $businessUnitId)
            ->where('is_active', true)
            ->exists()) {
            throw new Exception('Ticket category does not belong to the ticket business unit.');
        }
    }

    protected function assertDepartmentBelongsToBusinessUnit(?int $departmentId, int $businessUnitId): void
    {
        if ($departmentId !== null && ! Department::whereKey($departmentId)
            ->where('business_unit_id', $businessUnitId)
            ->where('is_active', true)
            ->exists()) {
            throw new Exception('Department does not belong to the ticket business unit.');
        }
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
     * Thin proxy that delegates to {@see TicketAssignmentService} so
     * controller call sites remain unchanged.
     *
     * @throws Exception
     */
    public function assignTicket(Ticket $ticket, int $userId, ?User $actor = null): Ticket
    {
        return $this->assignmentService->assignTicket($ticket, $userId, $actor);
    }

    /**
     * Add a comment to a ticket.
     *
     * Thin proxy that delegates to {@see TicketCommentService} so
     * controller call sites remain unchanged.
     */
    public function addComment(
        Ticket $ticket,
        User $user,
        string $content,
        bool $isPrivate = false
    ): TicketComment {
        return $this->commentService->addComment($ticket, $user, $content, $isPrivate);
    }

    /**
     * Add an attachment to a ticket.
     *
     * Thin proxy that delegates to {@see TicketCommentService} so
     * controller call sites remain unchanged.
     *
     * @throws Exception
     */
    public function addAttachment(
        Ticket $ticket,
        UploadedFile $file,
        ?User $uploader = null,
        ?int $commentId = null
    ): TicketAttachment {
        return $this->commentService->addAttachment($ticket, $file, $uploader, $commentId);
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
