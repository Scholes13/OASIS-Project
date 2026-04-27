import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { type ComponentProps } from 'react';
import { router } from '@inertiajs/react';
import Index from '@/Pages/Ticket/Index';
import type { Ticket, TicketCategory, TicketFilters, PaginatedData } from '@/types/ticket';
import type { User } from '@/types';

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
            reload: vi.fn(),
        },
        useForm: () => ({
            post: vi.fn(),
            processing: false,
        }),
    };
});

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
        assigned_user: {
            id: 2,
            name: 'Alice Support',
            email: 'alice@example.com',
            role: 'user',
            avatar_url: null,
            primary_department_id: 1,
        },
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
            first: '/it-support.admin.tickets.index?page=1',
            last: '/it-support.admin.tickets.index?page=1',
            prev: null,
            next: null,
        },
        meta: {
            current_page: 1,
            from: 1,
            last_page: 1,
            links: [],
            path: '/it-support.admin.tickets.index',
            per_page: 20,
            to: 2,
            total: 2,
        },
    };
}

describe('Ticket Index page (admin ticket list)', () => {
    const baseProps = {
        tickets: makePaginatedData([
            makeTicket({ id: 1, status: 'waiting', priority: 'medium' }),
            makeTicket({ id: 2, status: 'in_progress', priority: 'high' }),
        ]),
        categories: [
            { id: 1, name: 'Network', description: 'Network issues', color: '#6366f1', is_active: true },
            { id: 2, name: 'Hardware', description: 'Hardware issues', color: '#10b981', is_active: true },
        ] as TicketCategory[],
        staff: [
            { id: 1, name: 'Alice Support', email: 'alice@example.com', role: 'user', avatar_url: null, primary_department_id: 1 },
            { id: 2, name: 'Bob Support', email: 'bob@example.com', role: 'user', avatar_url: null, primary_department_id: 1 },
        ] as User[],
        filters: {
            search: '',
            status: '' as const,
            priority: '' as const,
            category_id: null,
            assigned_user_id: null,
            date_from: '',
            date_to: '',
        } as TicketFilters,
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
                    if (value !== undefined && value !== null) {
                        carry[key] = String(value);
                    }
                    return carry;
                }, {})
            ).toString()}`;
        }) as any;
    });

    it('renders ticket list table', () => {
        render(<Index {...baseProps} />);

        expect(screen.getByText('Semua Tiket')).toBeInTheDocument();
        expect(screen.getByText('TKT-2026-001')).toBeInTheDocument();
        expect(screen.getByText('TKT-2026-002')).toBeInTheDocument();
    });

    it('displays status badges in table', () => {
        render(<Index {...baseProps} />);

        // Status badges are rendered - find by text
        expect(screen.getByText('Menunggu')).toBeInTheDocument();
        expect(screen.getByText('Dalam Proses')).toBeInTheDocument();
    });

    it('displays priority badges in table', () => {
        render(<Index {...baseProps} />);

        // Priority badges (Indonesian labels)
        expect(screen.getByText('Sedang')).toBeInTheDocument(); // medium
        expect(screen.getByText('Tinggi')).toBeInTheDocument(); // high
    });

    it('shows filter controls', () => {
        render(<Index {...baseProps} />);

        // Search input
        expect(screen.getByPlaceholderText(/search tickets/i)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /search/i })).toBeInTheDocument();

        // Status tabs
        expect(screen.getByRole('button', { name: /all/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /menunggu/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /dalam proses/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /selesai/i })).toBeInTheDocument();
    });

    it('has category and priority filter dropdowns', () => {
        render(<Index {...baseProps} />);

        // Look for select elements by their placeholder or label
        const selects = screen.getAllByRole('combobox');
        expect(selects.length).toBeGreaterThan(0);
    });

    it('shows refresh button in header', () => {
        render(<Index {...baseProps} />);

        expect(screen.getByRole('button', { name: /refresh/i })).toBeInTheDocument();
    });
});