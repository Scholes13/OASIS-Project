import { beforeEach, describe, expect, it, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { type ComponentProps } from 'react';
import { router } from '@inertiajs/react';
import Show from '@/Pages/Ticket/Show';
import type { Ticket, TicketComment, User } from '@/types/ticket';

vi.mock('@inertiajs/react', async () => {
    const actual = await vi.importActual<typeof import('@inertiajs/react')>('@inertiajs/react');

    return {
        ...actual,
        Head: () => null,
        Link: ({ children, href, ...props }: ComponentProps<'a'>) => (
            <a href={href} {...props}>
                {children}
            </a>
        ),
        router: {
            post: vi.fn(),
        },
        useForm: () => ({
            post: vi.fn(),
            put: vi.fn(),
            setData: vi.fn(),
            processing: false,
        }),
        usePage: () => ({
            props: {
                flash: {},
            },
        }),
    };
});

vi.mock('@/layouts/AppLayout', () => ({
    default: ({ children, title }: { children: React.ReactNode; title?: string }) => (
        <div data-testid="app-layout">
            <h1>{title}</h1>
            {children}
        </div>
    ),
}));

vi.mock('@/components/Ticket/CommentSection', () => ({
    CommentSection: ({ comments }: { comments: TicketComment[] }) => (
        <div data-testid="comment-section">
            {comments.map(c => (
                <div key={c.id} data-testid={`comment-${c.id}`}>
                    {c.content}
                </div>
            ))}
        </div>
    ),
}));

vi.mock('@/components/Ticket/AttachmentList', () => ({
    AttachmentList: ({ attachments }: { attachments: any[] }) => (
        <div data-testid="attachment-list">{attachments.length} attachments</div>
    ),
}));

vi.mock('@/components/Ticket/TicketStatusBadge', () => ({
    TicketStatusBadge: ({ status }: { status: string }) => (
        <span data-testid="status-badge">{status === 'waiting' ? 'Menunggu' : status}</span>
    ),
}));

vi.mock('@/components/Ticket/TicketPriorityBadge', () => ({
    TicketPriorityBadge: ({ priority }: { priority: string }) => (
        <span data-testid="priority-badge">{priority === 'medium' ? 'Sedang' : priority}</span>
    ),
}));

vi.mock('@/components/Ticket/SlaBadge', () => ({
    SlaBadge: ({ slaDeadline, isBreached }: { slaDeadline: string | null; isBreached: boolean }) => (
        <span data-testid="sla-badge">
            {isBreached ? 'Breached' : slaDeadline ? 'On Track' : 'No SLA'}
        </span>
    ),
}));

function makeUser(overrides: Partial<User> = {}): User {
    return {
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        role: 'user',
        avatar_url: null,
        primary_department_id: 1,
        ...overrides,
    };
}

function makeComment(overrides: Partial<TicketComment> = {}): TicketComment {
    return {
        id: 1,
        content: 'This is a comment',
        is_private: false,
        user: makeUser(),
        created_at: '2026-04-27T08:00:00Z',
        updated_at: '2026-04-27T08:00:00Z',
        ...overrides,
    };
}

function makeTicket(overrides: Partial<Ticket> = {}): Ticket {
    return {
        id: 1,
        ticket_number: 'TKT-2026-001',
        title: 'Network connectivity issue',
        description: 'Cannot connect to internal network. Please help.',
        requester: makeUser(),
        department: {
            id: 1,
            name: 'IT Department',
            code: 'IT',
            business_unit_id: 1,
        },
        status: 'waiting',
        priority: 'medium',
        category: {
            id: 1,
            name: 'Network',
            description: 'Network related issues',
            color: '#6366f1',
            is_active: true,
        },
        assigned_user: makeUser({ id: 2, name: 'Alice Support' }),
        creator: null,
        comments: [],
        attachments: [],
        follow_up_at: null,
        resolved_at: null,
        processing_time: null,
        sla_deadline: '2026-04-28T12:00:00Z',
        is_sla_breach: false,
        created_at: '2026-04-27T08:00:00Z',
        updated_at: '2026-04-27T08:00:00Z',
        ...overrides,
    };
}

describe('Ticket Show page', () => {
    const baseProps = {
        ticket: makeTicket({
            id: 1,
            ticket_number: 'TKT-2026-001',
            title: 'Network connectivity issue',
            description: 'Cannot connect to internal network',
            status: 'waiting',
            priority: 'medium',
            comments: [
                makeComment({ id: 1, content: 'Public comment', is_private: false }),
                makeComment({ id: 2, content: 'Private comment', is_private: true }),
            ],
        }),
        isAdmin: false,
        staff: [],
        articles: [],
    };

    beforeEach(() => {
        vi.clearAllMocks();
        global.route = vi.fn((name: string, params?: Record<string, string | number>) => {
            const base = `/${name}`;

            if (!params || Object.keys(params).length === 0) {
                return base;
            }

            return `${base}?${new URLSearchParams(
                Object.entries(params).reduce<Record<string, string>>((carry, [key, value]) => {
                    carry[key] = String(value);
                    return carry;
                }, {})
            ).toString()}`;
        }) as any;
    });

    it('renders ticket detail in requester mode', () => {
        render(<Show {...baseProps} isAdmin={false} />);

        // Ticket header
        expect(screen.getByText('TKT-2026-001')).toBeInTheDocument();
        expect(screen.getByText('Network connectivity issue')).toBeInTheDocument();

        // Status and priority badges - use getAllByText since they appear multiple times
        expect(screen.getAllByText('Menunggu').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Sedang').length).toBeGreaterThan(0);

        // Description
        expect(screen.getByText('Deskripsi')).toBeInTheDocument();
        expect(screen.getByText('Cannot connect to internal network')).toBeInTheDocument();
    });

    it('renders ticket detail in admin mode with extra controls', () => {
        const staff = [
            { id: 1, name: 'Alice Support', email: 'alice@example.com', role: 'user', avatar_url: null, primary_department_id: 1 },
        ] as User[];

        render(<Show {...baseProps} isAdmin={true} staff={staff} />);

        // In admin mode, should show status change controls
        expect(screen.getByText('Status Tiket')).toBeInTheDocument();
        expect(screen.getByText('Ubah Status')).toBeInTheDocument();

        // Should show assignment controls
        expect(screen.getByText('Penugasan')).toBeInTheDocument();
        expect(screen.getByText('Ubah')).toBeInTheDocument();
    });

    it('shows public comments only in requester mode', () => {
        const ticket = makeTicket({
            comments: [
                makeComment({ id: 1, content: 'Public comment 1', is_private: false }),
                makeComment({ id: 2, content: 'Private comment 2', is_private: true }),
                makeComment({ id: 3, content: 'Public comment 3', is_private: false }),
            ],
        });

        render(<Show {...baseProps} ticket={ticket} isAdmin={false} />);

        expect(screen.getByTestId('comment-section')).toBeInTheDocument();
        // Should only show public comments
        expect(screen.getByTestId('comment-1')).toBeInTheDocument();
        expect(screen.getByTestId('comment-3')).toBeInTheDocument();
        // Private comment should not be visible
        expect(screen.queryByTestId('comment-2')).not.toBeInTheDocument();
    });

    it('shows all comments in admin mode', () => {
        const ticket = makeTicket({
            comments: [
                makeComment({ id: 1, content: 'Public comment 1', is_private: false }),
                makeComment({ id: 2, content: 'Private comment 2', is_private: true }),
            ],
        });

        render(<Show {...baseProps} ticket={ticket} isAdmin={true} staff={[]} />);

        expect(screen.getByTestId('comment-section')).toBeInTheDocument();
        // All comments should be visible
        expect(screen.getByTestId('comment-1')).toBeInTheDocument();
        expect(screen.getByTestId('comment-2')).toBeInTheDocument();
    });

    it('shows requester and department in ticket details', () => {
        render(<Show {...baseProps} />);

        expect(screen.getByText('Pemohon')).toBeInTheDocument();
        expect(screen.getByText('John Doe')).toBeInTheDocument();
        expect(screen.getByText('Departemen')).toBeInTheDocument();
        expect(screen.getByText('IT Department')).toBeInTheDocument();
    });

    it('displays back navigation link', () => {
        render(<Show {...baseProps} />);

        expect(screen.getByRole('link', { name: /kembali/i })).toHaveAttribute('href', '/it-support.my-tickets');
    });
});