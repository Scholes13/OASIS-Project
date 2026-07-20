import { beforeEach, describe, expect, it, vi } from 'vitest';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { format, startOfYear, subDays } from 'date-fns';
import { type ComponentProps } from 'react';
import { router } from '@inertiajs/react';
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
        volume_by_day: [
            { date: '2026-04-25', count: 4 },
            { date: '2026-04-27', count: 6 },
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

    it('renders period totals, daily volume, and workload from the same metrics', () => {
        render(<Dashboard {...baseProps} />);

        expect(screen.getByText('Total Tickets')).toBeInTheDocument();
        expect(screen.getAllByText('15').length).toBeGreaterThan(0);
        expect(screen.getByText('25/04')).toBeInTheDocument();
        expect(screen.getByText('27/04')).toBeInTheDocument();
        expect(screen.getByRole('img', { name: '4 tickets on 25/04' })).toBeVisible();
        expect(screen.getByRole('img', { name: '6 tickets on 27/04' })).toHaveStyle({ height: '100%' });
        expect(screen.getByText('Tickets across the latest 2 active days shown.')).toBeInTheDocument();
        expect(screen.getByText('Alice')).toBeInTheDocument();
        expect(screen.getByText('5 (33%)')).toBeInTheDocument();
    });

    it('flows dashboard cards in independent content-height columns', () => {
        render(<Dashboard {...baseProps} />);

        const primaryColumn = screen.getByTestId('ticket-dashboard-primary-column');
        const secondaryColumn = screen.getByTestId('ticket-dashboard-secondary-column');

        expect(within(primaryColumn).getByText('Ticket Volume Tracker')).toBeInTheDocument();
        expect(within(primaryColumn).getByText('Ticket Status Board')).toBeInTheDocument();
        expect(within(primaryColumn).queryByText('Recent Support Activity')).not.toBeInTheDocument();
        expect(within(secondaryColumn).getByText('Recent Support Activity')).toBeInTheDocument();
        expect(within(secondaryColumn).getByText('Team Workload')).toBeInTheDocument();
    });

    it('keeps a single active day visible at full chart height', () => {
        render(
            <Dashboard
                {...baseProps}
                metrics={makeMetrics({
                    total: 3,
                    volume_by_day: [{ date: '2026-04-27', count: 3 }],
                })}
            />
        );

        expect(screen.getByRole('img', { name: '3 tickets on 27/04' })).toHaveStyle({ height: '100%' });
        expect(screen.getByText('Tickets across the latest 1 active day shown.')).toBeInTheDocument();
    });

    it('renders seven skewed active days with monotonic visible heights', () => {
        const volumeByDay = [1, 2, 3, 4, 5, 12, 100].map((count, index) => ({
            date: `2026-04-${String(index + 20).padStart(2, '0')}`,
            count,
        }));

        render(
            <Dashboard
                {...baseProps}
                metrics={makeMetrics({ total: 127, volume_by_day: volumeByDay })}
            />
        );

        const bars = screen.getAllByRole('img', { name: /tickets on/ });
        const heights = bars.map((bar) => Number.parseFloat(bar.style.height));

        expect(bars).toHaveLength(7);
        expect(heights).toEqual([...heights].sort((left, right) => left - right));
        expect(heights[0]).toBeCloseTo(8.92);
        expect(heights[6]).toBe(100);
    });

    it('renders current recent support activity', () => {
        render(<Dashboard {...baseProps} />);

        expect(screen.getByText('Recent Support Activity')).toBeInTheDocument();
        expect(screen.getByText('TKT-2026-001')).toBeInTheDocument();
        expect(screen.getAllByText('Network issue').length).toBeGreaterThan(0);
        expect(screen.getByText('TKT-2026-002')).toBeInTheDocument();
        expect(screen.getAllByText('Printer problem').length).toBeGreaterThan(0);
    });

    it('applies a period preset immediately instead of only changing the date fields', async () => {
        const user = userEvent.setup();
        render(<Dashboard {...baseProps} />);

        await user.click(screen.getByRole('button', { name: 'Select date period, current: Custom range' }));
        await user.click(await screen.findByRole('option', { name: '90 Days' }));

        const today = new Date();
        expect(router.get).toHaveBeenCalledWith(
            '/it-support.admin.dashboard',
            {
                date_from: format(subDays(today, 90), 'yyyy-MM-dd'),
                date_to: format(today, 'yyyy-MM-dd'),
            },
            expect.objectContaining({ preserveState: true, preserveScroll: true }),
        );
        expect(screen.getByRole('button', { name: 'Select date period, current: 90 Days' })).toBeInTheDocument();
    });

    it('applies the This Year preset from the start of the current year', async () => {
        const user = userEvent.setup();
        render(<Dashboard {...baseProps} />);

        await user.click(screen.getByRole('button', { name: 'Select date period, current: Custom range' }));
        await user.click(await screen.findByRole('option', { name: 'This Year' }));

        const today = new Date();
        expect(router.get).toHaveBeenCalledWith(
            '/it-support.admin.dashboard',
            {
                date_from: format(startOfYear(today), 'yyyy-MM-dd'),
                date_to: format(today, 'yyyy-MM-dd'),
            },
            expect.objectContaining({ preserveState: true, preserveScroll: true }),
        );
    });

    it('applies All Data across the full supported database date range', async () => {
        const user = userEvent.setup();
        render(<Dashboard {...baseProps} />);

        await user.click(screen.getByRole('button', { name: 'Select date period, current: Custom range' }));
        await user.click(await screen.findByRole('option', { name: 'All Data' }));

        expect(router.get).toHaveBeenCalledWith(
            '/it-support.admin.dashboard',
            {
                date_from: '1000-01-01',
                date_to: format(new Date(), 'yyyy-MM-dd'),
            },
            expect.objectContaining({ preserveState: true, preserveScroll: true }),
        );
        expect(screen.getByRole('button', { name: 'Select date period, current: All Data' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Choose custom date range, current: All available data' })).toBeInTheDocument();
        expect(screen.getByText('All available data')).toBeInTheDocument();
    });

    it('applies a custom range from the compact date filter', async () => {
        const user = userEvent.setup();
        render(<Dashboard {...baseProps} />);

        await user.click(screen.getByRole('button', { name: 'Choose custom date range, current: 01 Apr 2026 to 27 Apr 2026' }));
        const startDate = await screen.findByLabelText('Start date');
        const endDate = screen.getByLabelText('End date');

        await user.clear(startDate);
        await user.type(startDate, '2026-05-01');
        await user.clear(endDate);
        await user.type(endDate, '2026-06-30');
        await user.click(screen.getByRole('button', { name: 'Apply date filter' }));

        expect(router.get).toHaveBeenCalledWith(
            '/it-support.admin.dashboard',
            { date_from: '2026-05-01', date_to: '2026-06-30' },
            expect.objectContaining({ preserveState: true, preserveScroll: true }),
        );
        expect(screen.getByRole('button', { name: 'Choose custom date range, current: 01 May 2026 to 30 Jun 2026' })).toBeInTheDocument();
    });

    it('discards un-applied custom date drafts when the popover closes', async () => {
        const user = userEvent.setup();
        render(<Dashboard {...baseProps} />);

        const rangeButton = screen.getByRole('button', { name: 'Choose custom date range, current: 01 Apr 2026 to 27 Apr 2026' });
        await user.click(rangeButton);
        const startDate = await screen.findByLabelText('Start date');

        await user.clear(startDate);
        await user.type(startDate, '2026-05-01');
        expect(rangeButton).toHaveAccessibleName('Choose custom date range, current: 01 Apr 2026 to 27 Apr 2026');

        await user.keyboard('{Escape}');
        await waitFor(() => expect(screen.queryByLabelText('Start date')).not.toBeInTheDocument());
        await user.click(rangeButton);

        expect(await screen.findByLabelText('Start date')).toHaveValue('2026-04-01');
        expect(screen.getByLabelText('End date')).toHaveValue('2026-04-27');
        expect(router.get).not.toHaveBeenCalled();
    });

    it('shows empty state when no recent tickets', () => {
        render(
            <Dashboard
                {...baseProps}
                metrics={makeMetrics({ recent_tickets: [] })}
            />
        );

        expect(screen.getByText('No recent activity')).toBeInTheDocument();
    });
});
