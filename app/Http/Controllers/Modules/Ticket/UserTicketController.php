<?php

namespace App\Http\Controllers\Modules\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreTicketCommentRequest;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Models\Modules\Ticket\Ticket;
use App\Models\Modules\Ticket\TicketCategory;
use App\Services\Modules\Ticket\KnowledgeBaseService;
use App\Services\Modules\Ticket\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserTicketController extends Controller
{
    public function __construct(
        private TicketService $ticketService,
        private KnowledgeBaseService $knowledgeBaseService,
    ) {}

    /**
     * Show the create ticket form.
     *
     * GET /it-support/submit
     */
    public function create(): Response
    {
        $buId = (int) session('current_business_unit_id');

        $categories = TicketCategory::where('business_unit_id', $buId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'color']);

        return Inertia::render('Ticket/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a new ticket.
     *
     * POST /it-support/submit
     */
    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $user = $request->user();
        $buId = (int) session('current_business_unit_id');

        $ticket = $this->ticketService->createTicket($request->validated(), $user, $buId);

        // Handle file attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $this->ticketService->addAttachment($ticket, $file, $user);
            }
        }

        // TODO: dispatch TicketCreatedNotification (Task 11)

        return redirect()->route('it-support.my-tickets')
            ->with('success', "Ticket {$ticket->ticket_number} berhasil dibuat.");
    }

    /**
     * List the authenticated user's tickets.
     *
     * GET /it-support/my-tickets
     */
    public function myTickets(Request $request): Response
    {
        $user = $request->user();
        $buId = (int) session('current_business_unit_id');

        $filters = [
            'search' => $request->get('search', ''),
            'status' => $request->get('status', ''),
        ];

        $query = Ticket::where('requester_id', $user->id)
            ->where('business_unit_id', $buId)
            ->with(['category', 'assignedUser']);

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

        $tickets = $query->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Ticket/MyTickets', [
            'tickets' => $tickets,
            'filters' => $filters,
        ]);
    }

    /**
     * Show ticket detail (read-only for requester).
     *
     * GET /it-support/my-tickets/{ticket}
     */
    public function show(Ticket $ticket): Response
    {
        abort_unless($ticket->requester_id === auth()->id(), 403);

        $ticket->load([
            'category',
            'assignedUser',
            'requester',
            'comments' => fn ($q) => $q->where('is_private', false)->with('user')->latest(),
            'attachments',
            'knowledgeArticles',
        ]);

        return Inertia::render('Ticket/Show', [
            'ticket' => $ticket,
            'isAdmin' => false,
        ]);
    }

    /**
     * Add a public comment to a ticket (requester only).
     *
     * POST /it-support/my-tickets/{ticket}/comment
     */
    public function addComment(StoreTicketCommentRequest $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->requester_id === auth()->id(), 403);

        $this->ticketService->addComment(
            $ticket,
            $request->user(),
            $request->validated()['content'],
            false
        );

        return back()->with('success', 'Komentar berhasil ditambahkan.');
    }
}
