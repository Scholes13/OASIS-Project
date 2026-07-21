import { beforeEach, describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { format, startOfYear, subDays } from 'date-fns';
import { router } from '@inertiajs/react';
import Reporting from '@/Pages/Ticket/Reporting';
import type { ReportData } from '@/components/Ticket/reporting/types';

vi.mock('@inertiajs/react', async () => {
    const actual = await vi.importActual<typeof import('@inertiajs/react')>('@inertiajs/react');

    return {
        ...actual,
        Head: () => null,
        router: {
            get: vi.fn(),
        },
    };
});

const reportData: ReportData = {
    total_tickets: 12,
    resolved_tickets: 10,
    avg_resolution_hours: 3.5,
    by_status: [],
    by_priority: [],
    by_category: [],
    by_staff: [],
    daily_trend: [],
};

describe('Ticket Reporting page', () => {
    const baseProps = {
        reportData,
        filters: {
            date_from: '2026-04-01',
            date_to: '2026-04-27',
        },
    };

    beforeEach(() => {
        vi.clearAllMocks();
        global.route = vi.fn((name: string) => `/${name}`) as any;
    });

    it('renders the shared modern period filter and period-aware export actions', () => {
        render(<Reporting {...baseProps} />);

        expect(screen.getByRole('button', { name: 'Select date period, current: Custom range' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Choose custom date range, current: 01 Apr 2026 to 27 Apr 2026' })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Excel' })).toHaveAttribute(
            'href',
            '/it-support.admin.reporting.exportExcel?date_from=2026-04-01&date_to=2026-04-27',
        );
        expect(screen.getByRole('link', { name: 'PDF' })).toHaveAttribute(
            'href',
            '/it-support.admin.reporting.exportPdf?date_from=2026-04-01&date_to=2026-04-27',
        );
    });

    it('applies a standard period preset immediately', async () => {
        const user = userEvent.setup();
        render(<Reporting {...baseProps} />);

        await user.click(screen.getByRole('button', { name: 'Select date period, current: Custom range' }));
        await user.click(await screen.findByRole('option', { name: '90 Days' }));

        const today = new Date();
        expect(router.get).toHaveBeenCalledWith(
            '/it-support.admin.reporting',
            {
                date_from: format(subDays(today, 90), 'yyyy-MM-dd'),
                date_to: format(today, 'yyyy-MM-dd'),
            },
            expect.objectContaining({ preserveState: true, preserveScroll: true }),
        );
    });

    it('supports This Year and All Data with immediate filtering', async () => {
        const user = userEvent.setup();
        render(<Reporting {...baseProps} />);

        const periodButton = screen.getByRole('button', { name: 'Select date period, current: Custom range' });
        await user.click(periodButton);
        await user.click(await screen.findByRole('option', { name: 'This Year' }));

        const today = new Date();
        expect(router.get).toHaveBeenLastCalledWith(
            '/it-support.admin.reporting',
            {
                date_from: format(startOfYear(today), 'yyyy-MM-dd'),
                date_to: format(today, 'yyyy-MM-dd'),
            },
            expect.objectContaining({ preserveState: true, preserveScroll: true }),
        );

        await user.click(screen.getByRole('button', { name: 'Select date period, current: This Year' }));
        await user.click(await screen.findByRole('option', { name: 'All Data' }));

        expect(router.get).toHaveBeenLastCalledWith(
            '/it-support.admin.reporting',
            {
                date_from: '1000-01-01',
                date_to: format(today, 'yyyy-MM-dd'),
            },
            expect.objectContaining({ preserveState: true, preserveScroll: true }),
        );
        expect(screen.getByText('All available data')).toBeInTheDocument();
    });

    it('applies a custom range and updates export links only after Apply', async () => {
        const user = userEvent.setup();
        render(<Reporting {...baseProps} />);

        await user.click(screen.getByRole('button', { name: 'Choose custom date range, current: 01 Apr 2026 to 27 Apr 2026' }));
        const startDate = await screen.findByLabelText('Start date');
        const endDate = screen.getByLabelText('End date');

        await user.clear(startDate);
        await user.type(startDate, '2026-05-01');
        await user.clear(endDate);
        await user.type(endDate, '2026-06-30');

        expect(screen.getByRole('link', { name: 'Excel' })).toHaveAttribute(
            'href',
            '/it-support.admin.reporting.exportExcel?date_from=2026-04-01&date_to=2026-04-27',
        );

        await user.click(screen.getByRole('button', { name: 'Apply date filter' }));

        expect(router.get).toHaveBeenCalledWith(
            '/it-support.admin.reporting',
            { date_from: '2026-05-01', date_to: '2026-06-30' },
            expect.objectContaining({ preserveState: true, preserveScroll: true }),
        );
        expect(screen.getByRole('link', { name: 'Excel' })).toHaveAttribute(
            'href',
            '/it-support.admin.reporting.exportExcel?date_from=2026-05-01&date_to=2026-06-30',
        );
    });
});
