import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { ComponentProps } from 'react';
import Settings from '@/Pages/CashflowProjection/Settings';
import type { CashflowProjectionSettingsPageProps } from '@/Pages/CashflowProjection/types';

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
    };
});

describe('Cashflow Projection Settings page', () => {
    const baseProps: CashflowProjectionSettingsPageProps = {
        year: 2026,
        selectedMonth: 3,
        financeInputs: [
            {
                id: 1,
                month: 3,
                cash_on_hand: 2000000,
                receivable_estimate: 500000,
                upcoming_event_revenue_estimate: 200000,
                capital_injection_estimate: 0,
                other_income: 100000,
                creator_name: 'Rina',
                creator_department_label: 'CFC',
                updater_name: 'Dimas',
                updater_department_label: 'ACC',
            },
        ],
        linkedUnits: [],
        availableBusinessUnits: [],
    };

    beforeEach(() => {
        vi.clearAllMocks();
        global.route = vi.fn((name: string) => `/${name}`);
    });

    it('renders attribution metadata for saved finance inputs', () => {
        render(<Settings {...baseProps} />);

        expect(screen.getByText(/created by: rina \(cfc\)/i)).toBeInTheDocument();
        expect(screen.getByText(/last edited by: dimas \(acc\)/i)).toBeInTheDocument();
    });
});
