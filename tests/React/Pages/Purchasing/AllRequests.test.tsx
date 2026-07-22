import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { router } from '@inertiajs/react';
import AllRequests from '@/Pages/Purchasing/AllRequests';

const baseProps = {
    auth: { user: { id: 1, name: 'Viewer', email: 'viewer@example.com', role: 'user' } },
    currentBusinessUnit: { id: 2, code: 'WNS', name: 'Werkudara Nirwana Sakti', logo: null },
    availableBusinessUnits: [],
    navigation: { sections: [] },
    flash: {},
    appName: 'Oasis',
    departments: [{ id: 6, business_unit_id: 2, code: 'GA', name: 'General Affair' }],
    filters: {},
    requests: {
        data: [
            {
                type: 'stock_request' as const,
                id: 31,
                number: 'ST.WNS/202607/025',
                summary: 'Office stock replenishment',
                status: 'ready_for_purchasing',
                date_of_request: '2026-07-10',
                created_at: '2026-07-10T10:00:00Z',
                business_unit_id: 2,
                business_unit_code: 'WNS',
                business_unit_name: 'Werkudara Nirwana Sakti',
                department_id: 6,
                department_code: 'GA',
                department_name: 'General Affair',
                user_id: 90,
                requester_name: 'Sheilia',
                items_count: 2,
                total_amount: '300000.00',
                currency: 'IDR',
                show_url: '/stock-requests/31',
            },
            {
                type: 'purchase_request' as const,
                id: 164,
                number: 'PR.WNS/202607/164',
                summary: 'Event equipment',
                status: 'in_approval',
                date_of_request: '2026-07-09',
                created_at: '2026-07-09T10:00:00Z',
                business_unit_id: 2,
                business_unit_code: 'WNS',
                business_unit_name: 'Werkudara Nirwana Sakti',
                department_id: 6,
                department_code: 'GA',
                department_name: 'General Affair',
                user_id: 91,
                requester_name: 'Requester PR',
                items_count: 1,
                total_amount: '125000.00',
                currency: 'IDR',
                show_url: '/purchase-requests/164',
            },
        ],
        links: { first: '/purchasing/all-requests?page=1', last: '/purchasing/all-requests?page=1', prev: null, next: null },
        meta: { current_page: 1, from: 1, last_page: 1, links: [], path: '/purchasing/all-requests', per_page: 15, to: 2, total: 2 },
    },
};

describe('All purchasing requests page', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        global.route = vi.fn((name: string) => name === 'purchasing.all-requests' ? '/purchasing/all-requests' : `/${name}`) as typeof route;
    });

    it('renders purchase and stock requests with correct detail links', () => {
        render(<AllRequests {...baseProps} />);

        expect(screen.getByRole('heading', { name: 'All Requests' })).toBeInTheDocument();
        expect(screen.getByText('ST/WNS/202607/025').closest('a')).toHaveAttribute('href', '/stock-requests/31');
        expect(screen.getByText('PR/WNS/202607/164').closest('a')).toHaveAttribute('href', '/purchase-requests/164');
        expect(screen.getByText('Sheilia')).toBeInTheDocument();
        expect(screen.getByText('Requester PR')).toBeInTheDocument();
        expect(screen.getByText('Ready for purchasing')).toBeInTheDocument();
        expect(screen.getByText((_, element) => element?.tagName === 'P' && element.textContent === '2 requests')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Request type' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Request status' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Department' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Rows per page' })).toBeInTheDocument();
        expect(screen.getByRole('region', { name: 'Purchasing requests' })).toBeInTheDocument();
        expect(screen.getByRole('group', { name: 'Requested date range' })).toBeInTheDocument();
    });

    it('preserves cents for non IDR purchase requests', () => {
        const usdRequest = {
            ...baseProps.requests.data[1],
            currency: 'USD',
            total_amount: '1250.50',
        };

        render(<AllRequests {...baseProps} requests={{ ...baseProps.requests, data: [usdRequest] }} />);

        expect(screen.getByText(/USD 1\.250,50/)).toBeInTheDocument();
    });

    it('submits combined filters to canonical route', () => {
        render(<AllRequests {...baseProps} />);

        fireEvent.change(screen.getByPlaceholderText('Number, purpose, or requester'), { target: { value: 'Sheilia' } });
        fireEvent.click(screen.getByRole('button', { name: 'Apply' }));

        expect(router.get).toHaveBeenCalledWith(
            '/purchasing/all-requests',
            expect.objectContaining({ search: 'Sheilia', per_page: '15' }),
            { preserveState: true, preserveScroll: true },
        );
    });

    it('renders combined empty state', () => {
        render(<AllRequests {...baseProps} requests={{ ...baseProps.requests, data: [], meta: { ...baseProps.requests.meta, from: null, to: null, total: 0 } }} />);

        expect(screen.getByRole('heading', { name: 'No purchasing requests found' })).toBeInTheDocument();
    });

    it('blocks an invalid date range with visible guidance', () => {
        render(<AllRequests {...baseProps} />);

        fireEvent.change(screen.getByLabelText('Date to'), { target: { value: '2026-07-01' } });
        fireEvent.change(screen.getByLabelText('Date from'), { target: { value: '2026-07-10' } });

        expect(screen.getByText('From date must be before To date.')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Apply' })).toBeDisabled();
    });

    it('clears active filters', () => {
        render(<AllRequests {...baseProps} filters={{ search: 'Sheilia' }} />);

        fireEvent.click(screen.getByRole('button', { name: 'Clear filters' }));

        expect(router.get).toHaveBeenCalledWith('/purchasing/all-requests');
    });
});
