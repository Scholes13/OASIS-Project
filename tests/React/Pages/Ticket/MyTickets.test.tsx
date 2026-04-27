import { beforeEach, describe, expect, it, vi } from 'vitest';
import { act, render, screen, waitFor } from '@testing-library/react';
import { type ComponentProps } from 'react';
import { router } from '@inertiajs/react';
import MyTickets from '@/Pages/Ticket/MyTickets';
import type { Ticket, TicketStatus, TicketPriority, PaginatedData } from '@/types/ticket';

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
            get: vi.fn(),
            visit: vi.fn(),
        },
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

vi.mock('@/components/Ticket/TicketStatusBadge', () => ({
    TicketStatusBadge: ({ status }: { status: string }) => {
        const labels: Record<string, string> = {
            waiting: 'Menunggu',
            in_progress: 'Dalam Proses',
            done: 'Selesai',
            cancelled: 'Dibatalkan',
        };
        return <span data-testid={`status-${status}`}>{labels[status] || status}</span>;
    },
}));

vi.mock('@/components/Ticket/TicketPriorityBadge', () => ({
    TicketPriorityBadge: ({ priority }: { priority: string }) => {
        const labels: Record<string, string> = {
            low: 'Rendah',
            medium: 'Sedang',
            high: 'Tinggi',
            critical: 'Kritis',
        };
        return <span data-testid={`priority-${priority}`}>{labels[priority] || priority}</span>;
    },
}));

function makeTicket(overrides: Partial<Ticket> = {}): Ticket {
    const id = overrides.id ?? 1;
    return {
        id,
        ticket_number: overrides.ticket_number ?? `TKT-2026-00${id}`,
        title: overrides.title ?? 'Network connectivity issue',
        description: 'Cannot connect to internal network',
        requester: {
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
            role: 'user',
            avatar_url: null,
            primary_department_id: 1,
        },
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
        assigned_user: null,
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

function makePaginatedData(items: Ticket[]): PaginatedData<Ticket> {
    return {
        data: items,
        links: {
            first: '/it-support.my-tickets?page=1',
            last: '/it-support.my-tickets?page=1',
            prev: null,
            next: null,
        },
        meta: {
            current_page: 1,
            from: 1,
            last_page: 1,
            links: [],
            path: '/it-support.my-tickets',
            per_page: 10,
            to: items.length,
            total: items.length,
        },
    };
}

describe('My Tickets page', () => {
    const baseProps = {
        tickets: makePaginatedData([
            makeTicket({ id: 1, ticket_number: 'TKT-2026-001', status: 'waiting', priority: 'medium' }),
            makeTicket({ id: 2, ticket_number: 'TKT-2026-002', status: 'in_progress', priority: 'high' }),
            makeTicket({ id: 3, ticket_number: 'TKT-2026-003', status: 'done', priority: 'low' }),
        ]),
        filters: {
            search: '',
            status: '' as TicketStatus | '',
            priority: '' as TicketPriority | '',
        },
    };

    beforeEach(() => {
        vi.clearAllMocks();
        vi.useFakeTimers();
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

    afterEach(() => {
        vi.useRealTimers();
    });

    it('renders my tickets list', async () => {
        render(<MyTickets {...baseProps} />);

        await act(async () => {
            vi.advanceTimersByTime(500);
        });

        // Use getAllByText since elements may appear multiple times
        expect(screen.getAllByText('Tiket Saya').length).toBeGreaterThanOrEqual(1);
        // Check for at least some ticket numbers
        expect(screen.getAllByText('TKT-2026-001').length).toBeGreaterThan(0);
    });

    it('displays ticket status badges', async () => {
        render(<MyTickets {...baseProps} />);

        await act(async () => {
            vi.advanceTimersByTime(500);
        });

        // Status badges
        expect(screen.getAllByText('Menunggu').length).toBeGreaterThan(0); // waiting
        expect(screen.getAllByText('Dalam Proses').length).toBeGreaterThan(0); // in_progress
        expect(screen.getAllByText('Selesai').length).toBeGreaterThan(0); // done
    });

    it('displays ticket priority badges', async () => {
        render(<MyTickets {...baseProps} />);

        await act(async () => {
            vi.advanceTimersByTime(500);
        });

        // Priority badges
        expect(screen.getAllByText('Sedang').length).toBeGreaterThan(0); // medium
        expect(screen.getAllByText('Tinggi').length).toBeGreaterThan(0); // high
        expect(screen.getAllByText('Rendah').length).toBeGreaterThan(0); // low
    });

    it('shows empty state when no tickets', async () => {
        const emptyProps = {
            ...baseProps,
            tickets: makePaginatedData([]),
        };

        render(<MyTickets {...emptyProps} />);

        await act(async () => {
            vi.advanceTimersByTime(500);
        });

        expect(screen.getByText('Tidak Ada Tiket')).toBeInTheDocument();
        expect(screen.getByText(/anda belum memiliki ticket/i)).toBeInTheDocument();
    });

    it('shows submit new ticket button in header', async () => {
        render(<MyTickets {...baseProps} />);

        await act(async () => {
            vi.advanceTimersByTime(500);
        });

        // Check for link to the submit page
        const link = screen.getByRole('link');
        expect(link).toHaveAttribute('href', '/it-support.submit');
    });

    it('shows filter controls', async () => {
        render(<MyTickets {...baseProps} />);

        await act(async () => {
            vi.advanceTimersByTime(500);
        });

        // Search input
        expect(screen.getByPlaceholderText(/cari nomor ticket/i)).toBeInTheDocument();

        // Status select
        const selects = screen.getAllByRole('combobox');
        expect(selects.length).toBe(2); // Status and priority filters
    });

    it('shows ticket titles in table rows', async () => {
        render(<MyTickets {...baseProps} />);

        await act(async () => {
            vi.advanceTimersByTime(500);
        });

        // Ticket title may appear multiple times
        expect(screen.getAllByText('Network connectivity issue').length).toBeGreaterThan(0);
    });
});