<?php

namespace App\Http\Controllers\Modules\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\AssignTicketRequest;
use App\Http\Requests\Ticket\ChangeTicketStatusRequest;
use App\Http\Requests\Ticket\StoreTicketCommentRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\Ticket\KnowledgeArticle;
use App\Models\Modules\Ticket\Ticket;
use App\Models\Modules\Ticket\TicketCategory;
use App\Services\Modules\Ticket\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function __construct(
        private TicketService $ticketService,
    ) {}

    /**
     * List all tickets (IT Support admin).
     *
     * GET /it-support/tickets
     */
    public function index(Request $request): Response
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();

        $assignedTo = $request->get('assigned_user_id', $request->get('assigned_to', ''));

        $filters = [
            'search' => $request->get('search', ''),
            'status' => $request->get('status', ''),
            'priority' => $request->get('priority', ''),
            'category_id' => $request->get('category_id', ''),
            'assigned_user_id' => $assignedTo,
        ];

        $query = Ticket::forBusinessUnits($scopedBuIds)
            ->with(['category', 'assignedUser', 'requester', 'department']);

        if ($filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['priority']) {
            $query->where('priority', $filters['priority']);
        }

        if ($filters['category_id']) {
            $query->where('category_id', $filters['category_id']);
        }

        if ($assignedTo) {
            $query->where('assigned_to', $assignedTo);
        }

        $tickets = $query->latest()
            ->paginate(15)
            ->withQueryString();

        $categories = TicketCategory::whereIn('business_unit_id', $scopedBuIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $staff = $this->getItSupportStaff($scopedBuIds);

        return Inertia::render('Ticket/Index', [
            'tickets' => $tickets,
            'categories' => $categories,
            'staff' => $staff,
            'filters' => $filters,
        ]);
    }

    /**
     * Show ticket detail (admin, full access including private comments).
     *
     * GET /it-support/tickets/{ticket}
     */
    public function show(Ticket $ticket): Response
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();
        abort_unless(in_array((int) $ticket->business_unit_id, $scopedBuIds, true), 403);

        $ticket->load([
            'category',
            'assignedUser',
            'requester',
            'creator',
            'department',
            'businessUnit',
            'comments' => fn ($q) => $q->with('user')->latest(),
            'attachments',
            'knowledgeArticles',
        ]);

        $staff = $this->getItSupportStaff($scopedBuIds);

        $articles = KnowledgeArticle::whereIn('business_unit_id', $scopedBuIds)
            ->where('is_published', true)
            ->orderBy('title')
            ->get(['id', 'title', 'slug']);

        return Inertia::render('Ticket/Show', [
            'ticket' => $ticket,
            'isAdmin' => true,
            'staff' => $staff,
            'articles' => $articles,
        ]);
    }

    /**
     * Show the edit form for a ticket.
     *
     * GET /it-support/tickets/{ticket}/edit
     */
    public function edit(Ticket $ticket): Response
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();
        abort_unless(in_array((int) $ticket->business_unit_id, $scopedBuIds, true), 403);

        $ticket->load(['category', 'assignedUser', 'requester', 'department', 'comments.user', 'attachments']);

        $categories = TicketCategory::whereIn('business_unit_id', $scopedBuIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'color']);

        $staff = $this->getItSupportStaff($scopedBuIds);

        $departments = Department::whereIn('business_unit_id', $scopedBuIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Ticket/Edit', [
            'ticket' => $ticket,
            'categories' => $categories,
            'staff' => $staff,
            'departments' => $departments,
        ]);
    }

    /**
     * Update a ticket.
     *
     * PUT /it-support/tickets/{ticket}
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();
        abort_unless(in_array((int) $ticket->business_unit_id, $scopedBuIds, true), 403);

        try {
            $this->ticketService->updateTicket($ticket, $request->validated());

            return redirect()->route('it-support.admin.tickets.show', $ticket)
                ->with('success', 'Ticket berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Failed to update ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal memperbarui ticket. Silakan coba lagi.');
        }
    }

    /**
     * Delete a ticket.
     *
     * DELETE /it-support/tickets/{ticket}
     */
    public function destroy(Ticket $ticket): RedirectResponse
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();
        abort_unless(in_array((int) $ticket->business_unit_id, $scopedBuIds, true), 403);

        // Only allow deletion of waiting/cancelled tickets
        if (! in_array($ticket->status, ['waiting', 'cancelled'], true)) {
            return back()->with('error', 'Hanya ticket dengan status waiting atau cancelled yang dapat dihapus.');
        }

        $ticket->comments()->delete();
        $ticket->attachments()->delete();
        $ticket->knowledgeArticles()->detach();
        $ticket->delete();

        return redirect()->route('it-support.admin.tickets.index')
            ->with('success', 'Ticket berhasil dihapus.');
    }

    /**
     * Add a comment to a ticket (admin, supports private comments).
     *
     * POST /it-support/tickets/{ticket}/comment
     */
    public function addComment(StoreTicketCommentRequest $request, Ticket $ticket): RedirectResponse
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();
        abort_unless(in_array((int) $ticket->business_unit_id, $scopedBuIds, true), 403);

        $validated = $request->validated();

        $this->ticketService->addComment(
            $ticket,
            $request->user(),
            $validated['content'],
            $validated['is_private'] ?? false
        );

        return back()->with('success', 'Komentar berhasil ditambahkan.');
    }

    /**
     * Change ticket status.
     *
     * PUT /it-support/tickets/{ticket}/change-status
     */
    public function changeStatus(ChangeTicketStatusRequest $request, Ticket $ticket): RedirectResponse
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();
        abort_unless(in_array((int) $ticket->business_unit_id, $scopedBuIds, true), 403);

        try {
            $this->ticketService->changeStatus(
                $ticket,
                $request->validated()['status'],
                $request->user()
            );

            return back()->with('success', 'Status ticket berhasil diubah.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Assign a ticket to an IT Support staff member.
     *
     * POST /it-support/tickets/{ticket}/assign
     */
    public function assignTicket(AssignTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();
        abort_unless(in_array((int) $ticket->business_unit_id, $scopedBuIds, true), 403);

        try {
            $this->ticketService->assignTicket($ticket, $request->validated()['assigned_to']);

            return back()->with('success', 'Ticket berhasil di-assign.');
        } catch (\Exception $e) {
            Log::error('Failed to assign ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Resolve the active BU scope for IT Support admin.
     * Parent or holding BUs include all descendants for roll-up views.
     *
     * @return array<int>
     */
    private function resolveScopedBusinessUnitIds(): array
    {
        $currentBusinessUnitId = (int) session('current_business_unit_id');

        if ($currentBusinessUnitId <= 0) {
            return [];
        }

        $currentBusinessUnit = BusinessUnit::with('descendants')->find($currentBusinessUnitId);

        if (! $currentBusinessUnit) {
            return [$currentBusinessUnitId];
        }

        return $currentBusinessUnit->getAccessibleBusinessUnits();
    }

    /**
     * Get IT Support staff for the given business unit IDs.
     *
     * @param  array<int>  $buIds
     */
    private function getItSupportStaff(array $buIds): \Illuminate\Support\Collection
    {
        $staffIds = UserBusinessUnit::whereIn('business_unit_id', $buIds)
            ->where('is_it_support_admin', true)
            ->where('is_active', true)
            ->pluck('user_id')
            ->unique();

        return User::whereIn('id', $staffIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
