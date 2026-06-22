import { beforeEach, describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import { type ComponentProps } from 'react';
import Dashboard from '@/Pages/Ticket/Dashboard';
import type { TicketDashboardMetrics, Ticket } from '@/types/ticket';

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
        },
        usePage: () => ({
            props: {
                flash: {},
            },
        }),
    };
});

vi.mock('@/components/Ticket/TicketStatusBadge', () => ({
    TicketStatusBadge: ({ status }: { status: string }) => (
        <span data-testid={`status-${status}`}>{status}</span>
    ),
}));

vi.mock('@/components/Ticket/TicketPriorityBadge', () => ({
    TicketPriorityBadge: ({ priority }: { priority: string }) => (
        <span data-testid={`priority-${priority}`}>{priority}</span>
    ),
}));

vi.mock('@/components/Ticket/SlaBadge', () => ({
    SlaBadge: ({ slaDeadline, isBreached }: { slaDeadline: string | null; isBreached: boolean }) => (
        <span data-testid="sla-badge">
            {isBreached ? 'Breached' : slaDeadline ? 'On Track' : 'No SLA'}
        </span>
    ),
}));

function makeTicket(overrides: Partial<Ticket> = {}): Ticket {
    return {
        id: 1,
        ticket_number: 'TKT-2026-001',
        title: 'Network connectivity issue',
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

function makeMetrics(overrides: Partial<TicketDashboardMetrics> = {}): TicketDashboardMetrics {
    return {
        total: 15,
        by_status: {
            waiting: 5,
            in_progress: 3,
            done: 7,
            cancelled: 0,
        },
        by_priority: {
            low: 3,
            medium: 8,
            high: 3,
            critical: 1,
        },
        by_category: [
            { name: 'Network', count: 6, color: '#6366f1' },
            { name: 'Hardware', count: 5, color: '#10b981' },
            { name: 'Software', count: 4, color: '#f59e0b' },
        ],
        by_staff: [
            { name: 'Alice', count: 5 },
            { name: 'Bob', count: 3 },
        ],
        avg_resolution_hours: 4.5,
        sla_breach_count: 2,
        recent_tickets: [
            makeTicket({ id: 1, ticket_number: 'TKT-2026-001', title: 'Network issue', status: 'waiting' }),
            makeTicket({ id: 2, ticket_number: 'TKT-2026-002', title: 'Printer problem', status: 'in_progress' }),
        ],
        ...overrides,
    };
}

describe('Ticket Dashboard page', () => {
    const baseProps = {
        metrics: makeMetrics(),
        filters: {
            date_from: '2026-04-01',
            date_to: '2026-04-27',
        },
    };

    beforeEach(() => {
        vi.clearAllMocks();
        global.route = vi.fn((name: string, params?: Record<string, string | number>) => {
            if (!name) {
                return {
                    has: (routeName: string) => [
                        'it-support.admin.tickets.index',
                        'it-support.admin.tickets.edit',
                    ].includes(routeName),
                };
            }

            if (params && typeof params === 'object') {
                return `/${name}?${new URLSearchParams(
                    Object.entries(params).reduce<Record<string, string>>((carry, [key, value]) => {
                        carry[key] = String(value);
                        return carry;
                    }, {})
                ).toString()}`;
            }

            return `/${name}`;
        }) as any;
    });

    it('renders dashboard with summary cards', () => {
        render(<Dashboard {...baseProps} />);

        expect(screen.getByText('Total Tickets')).toBeInTheDocument();
        // Numbers may appear in multiple places (chart legend + summary cards)
        expect(screen.getAllByText('15').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Menunggu').length).toBeGreaterThan(0);
        expect(screen.getAllByText('5').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Dalam Proses').length).toBeGreaterThan(0);
        expect(screen.getAllByText('3').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Selesai').length).toBeGreaterThan(0);
        expect(screen.getAllByText('7').length).toBeGreaterThan(0);
    });

    it('displays SLA breach count', () => {
        render(<Dashboard {...baseProps} />);

        expect(screen.getByText('SLA Breach')).toBeInTheDocument();
        expect(screen.getByText('2')).toBeInTheDocument();
    });

    it('renders recent tickets table', () => {
        render(<Dashboard {...baseProps} />);

        expect(screen.getByText('Recent Tickets')).toBeInTheDocument();
        expect(screen.getByText('TKT-2026-001')).toBeInTheDocument();
        expect(screen.getByText('Network issue')).toBeInTheDocument();
        expect(screen.getByText('TKT-2026-002')).toBeInTheDocument();
        expect(screen.getByText('Printer problem')).toBeInTheDocument();
    });

    it('shows Ticket and Priority columns in recent tickets table', () => {
        render(<Dashboard {...baseProps} />);

        // Check table headers
        const headers = screen.getAllByRole('columnheader');
        const headerTexts = headers.map(h => h.textContent);
        expect(headerTexts).toContain('Ticket');
        expect(headerTexts).toContain('Title');
        expect(headerTexts).toContain('Requester');
        expect(headerTexts).toContain('Status');
        expect(headerTexts).toContain('Priority');
        expect(headerTexts).toContain('SLA');
    });

    it('shows empty state when no recent tickets', () => {
        render(
            <Dashboard
                {...baseProps}
                metrics={makeMetrics({ recent_tickets: [] })}
            />
        );

        expect(screen.getByText('No recent tickets')).toBeInTheDocument();
    });
});