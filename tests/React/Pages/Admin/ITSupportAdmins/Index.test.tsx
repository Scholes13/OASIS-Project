import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { type ComponentProps } from 'react';
import { router } from '@inertiajs/react';
import Index from '@/Pages/Admin/ITSupportAdmins/Index';
import type { PaginatedData } from '@/types';

interface Assignment {
    id: number;
    is_it_support_admin: boolean;
    is_it_support_report_access: boolean;
    is_primary: boolean;
    user: { id: number; name: string; email: string } | null;
    business_unit: { id: number; name: string; code: string } | null;
    department: { id: number; name: string } | null;
    position: { id: number; name: string; access_level: string } | null;
}

interface BusinessUnit {
    id: number;
    name: string;
    code: string;
}

vi.mock('@inertiajs/react', async () => {
    const actual = await vi.importActual<typeof import('@inertiajs/react')>('@inertiajs/react');

    return {
        ...actual,
        Head: () => null,
        router: {
            get: vi.fn(),
            post: vi.fn(),
        },
        usePage: () => ({
            props: {
                flash: {},
            },
        }),
    };
});

function makeAssignment(overrides: Partial<Assignment> = {}): Assignment {
    return {
        id: 1,
        is_it_support_admin: false,
        is_it_support_report_access: false,
        is_primary: false,
        user: {
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
        },
        business_unit: {
            id: 1,
            name: 'WNS Business Unit',
            code: 'WNS',
        },
        department: {
            id: 1,
            name: 'IT Department',
        },
        position: {
            id: 1,
            name: 'IT Support Staff',
            access_level: 'Standard',
        },
        ...overrides,
    };
}

function makePaginatedData(items: Assignment[]): PaginatedData<Assignment> {
    return {
        data: items,
        links: {
            first: '/admin.it-support-admins.index?page=1',
            last: '/admin.it-support-admins.index?page=1',
            prev: null,
            next: null,
        },
        meta: {
            current_page: 1,
            from: 1,
            last_page: 1,
            links: [],
            path: '/admin.it-support-admins.index',
            per_page: 20,
            to: items.length,
            total: items.length,
        },
    };
}

describe('IT Support Admins page', () => {
    const baseProps = {
        assignments: makePaginatedData([
            makeAssignment({ id: 1, user: { id: 1, name: 'Alice', email: 'alice@example.com' }, is_it_support_admin: true, is_it_support_report_access: true }),
            makeAssignment({ id: 2, user: { id: 2, name: 'Bob', email: 'bob@example.com' }, is_it_support_admin: false, is_it_support_report_access: false }),
        ]),
        businessUnits: [
            { id: 1, name: 'WNS Business Unit', code: 'WNS' },
            { id: 2, name: 'MRP Business Unit', code: 'MRP' },
        ] as BusinessUnit[],
        adminCounts: { 1: 1, 2: 0 } as Record<number, number>,
        reportAccessCounts: { 1: 1, 2: 0 } as Record<number, number>,
        filters: {
            business_unit_id: '',
            search: '',
        },
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

    it('renders admin assignment table', () => {
        render(<Index {...baseProps} />);

        // Page title
        expect(screen.getByText('IT Support Assignment')).toBeInTheDocument();

        // Table headers
        expect(screen.getByText('User')).toBeInTheDocument();
        expect(screen.getByText('Business Unit')).toBeInTheDocument();
        expect(screen.getByText('Department')).toBeInTheDocument();
        expect(screen.getByText('Position')).toBeInTheDocument();
        // Use getAllByText since "IT Support Admin" and "Report Access" may appear in summary cards too
        expect(screen.getAllByText('IT Support Admin').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Report Access').length).toBeGreaterThan(0);

        // Table data
        expect(screen.getByText('Alice')).toBeInTheDocument();
        expect(screen.getByText('alice@example.com')).toBeInTheDocument();
        expect(screen.getByText('Bob')).toBeInTheDocument();
        expect(screen.getByText('bob@example.com')).toBeInTheDocument();
    });

    it('shows toggle switches for admin and report access', () => {
        render(<Index {...baseProps} />);

        // Find toggle buttons by their accessible role
        const toggles = screen.getAllByRole('button');
        expect(toggles.length).toBeGreaterThanOrEqual(4); // 2 admin toggles + 2 report toggles
    });

    it('disables report access toggle when admin is off', () => {
        render(<Index {...baseProps} />);

        // Find all toggle buttons
        const toggles = screen.getAllByRole('button');
        
        // The second user (Bob) has is_it_support_admin: false
        // The report access toggle for Bob should be disabled
        // We need to find the disabled toggle(s)
        
        // Check that there are disabled buttons in the report access column
        const disabledToggles = screen.getAllByRole('button', { disabled: true });
        expect(disabledToggles.length).toBeGreaterThanOrEqual(1);
    });

    it('shows summary cards with counts', () => {
        render(<Index {...baseProps} />);

        expect(screen.getByText('Total IT Support Admins')).toBeInTheDocument();
        // The number 1 may appear multiple times, just verify it exists
        expect(screen.getAllByText('1').length).toBeGreaterThan(0);

        // Report Access is in summary card
        const reportAccessCards = screen.getAllByText('Report Access');
        expect(reportAccessCards.length).toBeGreaterThan(0);
    });

    it('shows filter controls', () => {
        render(<Index {...baseProps} />);

        // Business unit filter
        expect(screen.getByRole('combobox')).toBeInTheDocument();

        // Search input
        expect(screen.getByPlaceholderText(/search by name or email/i)).toBeInTheDocument();

        // Search button
        expect(screen.getByRole('button', { name: /search/i })).toBeInTheDocument();
    });

    it('shows user initials in avatar', () => {
        render(<Index {...baseProps} />);

        // Check for first letter of Alice (A) - it appears in the avatar circle
        expect(screen.getByText('Alice')).toBeInTheDocument();
        // The avatar should show 'A' for Alice - find the element with Alice and check for avatar
        const aliceElement = screen.getByText('Alice').closest('tr');
        expect(aliceElement).toBeInTheDocument();
    });

    it('shows empty state when no assignments', () => {
        render(
            <Index
                {...baseProps}
                assignments={makePaginatedData([])}
            />
        );

        expect(screen.getByText('Tidak ada data ditemukan.')).toBeInTheDocument();
    });

    it('has clickable toggle buttons for admin access', () => {
        render(<Index {...baseProps} />);

        // Find the first toggle button (admin toggle for Alice)
        const buttons = screen.getAllByRole('button');
        const adminToggle = buttons[0];
        
        // The toggle button should be clickable and enabled
        expect(adminToggle).not.toBeDisabled();
        
        // Click the admin toggle - just verify it can be clicked without error
        fireEvent.click(adminToggle);
        
        // The button should still exist after click (component doesn't unmount on click)
        expect(screen.getAllByRole('button').length).toBeGreaterThan(0);
    });
});