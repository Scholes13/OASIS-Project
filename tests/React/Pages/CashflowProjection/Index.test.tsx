import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { router } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import Index from '@/Pages/CashflowProjection/Index';
import type { CashflowProjectionPageProps } from '@/Pages/CashflowProjection/types';

vi.mock('@inertiajs/react', async () => {
    const actual = await vi.importActual<typeof import('@inertiajs/react')>('@inertiajs/react');

    return {
        ...actual,
        router: {
            ...actual.router,
            get: vi.fn(),
        },
        Head: () => null,
        Link: ({ children, href, ...props }: ComponentProps<'a'>) => (
            <a href={href} {...props}>
                {children}
            </a>
        ),
    };
});

describe('Cashflow Projection dashboard page', () => {
    const baseProps: CashflowProjectionPageProps = {
        year: 2026,
        selectedMonth: 3,
        cycle: {
            id: 1,
            status: 'draft',
            year: 2026,
        },
        minimumBalanceGlobal: 200000000,
        filters: {
            mode: 'month',
            year: 2026,
            month: 3,
            start_date: '2026-03-01',
            end_date: '2026-03-31',
            available_years: [2027, 2026, 2025],
        },
        summary: {
            total_balance: 1580,
            inflow: 600,
            outflow: 120,
            finance_income: 200,
            net_cashflow: 680,
        },
        dailySummary: [
            { date: '2026-03-05', plus: 500, minus: 0, net: 500 },
            { date: '2026-03-18', plus: 0, minus: 120, net: -120 },
            { date: '2026-03-28', plus: 100, minus: 0, net: 100 },
        ],
        monthlySummary: [
            { month: 1, plus: 300, minus: 80, finance_income: 150, opening_balance: 1000, net: 370, closing_balance: 1370, is_warning: false },
            { month: 2, plus: 0, minus: 0, finance_income: 0, opening_balance: 0, net: 0, closing_balance: 0, is_warning: true },
            { month: 3, plus: 600, minus: 120, finance_income: 200, opening_balance: 900, net: 680, closing_balance: 1580, is_warning: false },
        ],
        departments: [],
        lineItems: [
            {
                id: 2,
                department_id: 1,
                department_code: 'FIN',
                department_name: 'Finance',
                flow_type: 'out',
                action_code: 'finance_expense',
                action_label: 'Finance Expense',
                transaction_date: '2026-03-18',
                due_date: '2026-03-18',
                amount: 120,
                description: 'March vendor payment',
                notes: null,
                is_estimated_date: false,
            },
            {
                id: 3,
                department_id: 1,
                department_code: 'FIN',
                department_name: 'Finance',
                flow_type: 'in',
                action_code: 'finance_income',
                action_label: 'Finance Income',
                transaction_date: '2026-03-28',
                due_date: '2026-03-28',
                amount: 100,
                description: 'March top-up',
                notes: null,
                is_estimated_date: false,
            },
            {
                id: 4,
                department_id: 1,
                department_code: 'FIN',
                department_name: 'Finance',
                flow_type: 'out',
                action_code: 'finance_expense',
                action_label: 'Finance Expense',
                transaction_date: '2026-03-30',
                due_date: '2026-03-30',
                amount: 90,
                description: 'Estimated March expense',
                notes: null,
                is_estimated_date: true,
            },
        ],
        financeInputs: [],
        permissions: {
            canManageFinance: true,
        },
        scope: 'own',
        linkedBusinessUnits: [],
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
        });
    });

    it('renders a compact period trigger and keeps actions hidden until opened', () => {
        render(<Index {...baseProps} />);

        expect(screen.getByRole('button', { name: /mar 2026/i })).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: /apply period/i })).not.toBeInTheDocument();
        expect(screen.getByText(/granular trend for mar 2026/i)).toBeInTheDocument();
        expect(screen.getByText(/saldo proyeksi/i)).toBeInTheDocument();
        expect(screen.queryByText(/balance snapshot/i)).not.toBeInTheDocument();
    });

    it('opens the period dropdown and applies a year filter as the global dashboard context', async () => {
        render(<Index {...baseProps} />);

        fireEvent.click(screen.getByRole('button', { name: /mar 2026/i }));

        expect(screen.getByText(/filter period/i)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /apply period/i })).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: /^year$/i }));
        fireEvent.click(screen.getByRole('button', { name: /apply period/i }));

        await waitFor(() => {
            expect(router.get).toHaveBeenCalledWith(
                '/cashflow-projection.index',
                expect.objectContaining({
                    filter: 'year',
                    year: 2026,
                }),
                expect.objectContaining({
                    preserveScroll: true,
                    preserveState: true,
                    replace: true,
                })
            );
        });
    });

    it('keeps the chart subtitle synced with the active period from props', () => {
        const { rerender } = render(<Index {...baseProps} />);

        expect(screen.getByText(/granular trend for mar 2026/i)).toBeInTheDocument();

        rerender(
            <Index
                {...baseProps}
                filters={{
                    ...baseProps.filters,
                    month: 7,
                    start_date: '2026-07-01',
                    end_date: '2026-07-31',
                }}
                selectedMonth={7}
            />
        );

        expect(screen.getByText(/granular trend for jul 2026/i)).toBeInTheDocument();
        expect(screen.queryByText(/granular trend for mar 2026/i)).not.toBeInTheDocument();
    });

    it('shows day filters inside a compact dropdown instead of inline pills', async () => {
        const dayOptionLabel = new Date(2026, 2, 18).toLocaleDateString('id-ID', {
            weekday: 'short',
            day: '2-digit',
        });

        render(<Index {...baseProps} />);

        expect(screen.getByRole('button', { name: /all days/i })).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: new RegExp(dayOptionLabel, 'i') })).not.toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: /all days/i }));

        expect(await screen.findByRole('button', { name: new RegExp(dayOptionLabel, 'i') })).toBeInTheDocument();
    });

    it('starts export through browser navigation so downloads work in the same tab', () => {
        const openSpy = vi.spyOn(window, 'open').mockImplementation(() => null);

        render(
            <Index
                {...baseProps}
                scope="consolidated"
                linkedBusinessUnits={[
                    { id: 2, code: 'MRP', name: 'Maharaja Pratama' },
                ]}
            />
        );

        fireEvent.click(screen.getByRole('button', { name: /export excel/i }));

        expect(openSpy).toHaveBeenCalledWith(
            '/cashflow-projection.export?filter=month&year=2026&month=3&scope=consolidated',
            '_self'
        );

        openSpy.mockRestore();
    });

    it('uses transaction badges as state labels rather than inflow or outflow direction', () => {
        render(<Index {...baseProps} />);

        expect(screen.getByText('March vendor payment')).toBeInTheDocument();
        expect(screen.getAllByText(/confirmed/i).length).toBeGreaterThan(0);
        expect(screen.getByText(/pending/i)).toBeInTheDocument();
        expect(screen.queryByText(/projected/i)).not.toBeInTheDocument();
    });
});
