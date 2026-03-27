import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { ComponentProps } from 'react';
import Entries from '@/Pages/CashflowProjection/Entries';
import type { CashflowProjectionEntriesPageProps, LineItemFormData } from '@/Pages/CashflowProjection/types';

const postMock = vi.fn();
const patchMock = vi.fn();

const currentBusinessUnit = {
    id: 1,
    code: 'WNS',
    name: 'Werkudara Nirwana Sakti',
    logo: null,
};

vi.mock('@inertiajs/react', async () => {
    const React = await vi.importActual<typeof import('react')>('react');
    const actual = await vi.importActual<typeof import('@inertiajs/react')>('@inertiajs/react');

    return {
        ...actual,
        useForm: <T extends Record<string, unknown>>(initialValues: T) => {
            const [data, setDataState] = React.useState<T>(initialValues);
            const setData = React.useCallback(<K extends keyof T>(
                field: K | T | ((data: T) => T),
                value?: T[K]
            ) => {
                if (typeof field === 'function') {
                    setDataState(field);
                    return;
                }

                if (typeof field === 'object' && field !== null) {
                    setDataState(field as T);
                    return;
                }

                setDataState((current) => ({
                    ...current,
                    [field]: value,
                }));
            }, []);

            return {
                data,
                processing: false,
                errors: {},
                setData,
                post: postMock,
                patch: patchMock,
            };
        },
        Head: () => null,
        Link: ({ children, href, ...props }: ComponentProps<'a'>) => (
            <a href={href} {...props}>
                {children}
            </a>
        ),
        usePage: () => ({
            props: {
                currentBusinessUnit,
            },
        }),
    };
});

describe('Cashflow Projection Entries page', () => {
    const baseProps: CashflowProjectionEntriesPageProps = {
        year: 2026,
        selectedMonth: 3,
        departments: [
            {
                id: 10,
                code: 'CFC',
                name: 'Core Finance',
                business_unit_id: 1,
                business_unit_code: 'WNS',
                business_unit_name: 'Werkudara Nirwana Sakti',
                template_type: 'cfc',
                actions: [
                    { code: 'IN_ACC_PIUTANG_REVENUE', label: 'Piutang & Revenue', flow_type: 'in' },
                    { code: 'IN_TEP_ESTIMASI_UPCOMING_REVENUE', label: 'Estimasi Penerimaan dari Upcoming Revenue', flow_type: 'in' },
                    { code: 'IN_CFC_SUNTIKAN_MODAL', label: 'Suntikan Modal', flow_type: 'in' },
                    { code: 'OUT_ACC_PAJAK', label: 'Pajak', flow_type: 'out' },
                    { code: 'OUT_ACC_OPS', label: 'Operational Department ACC', flow_type: 'out' },
                    { code: 'OUT_TEP_COST_OF_REVENUE', label: 'Cost of Revenue dari Upcoming Revenue', flow_type: 'out' },
                    { code: 'OUT_HR_GAJI_BENEFIT', label: 'Gaji & Benefit Karyawan', flow_type: 'out' },
                    { code: 'OUT_CFC_CORPORATE_EXPENSES', label: 'Corporate Expense', flow_type: 'out' },
                    { code: 'OUT_CFC_OPS', label: 'Operational Department CFC', flow_type: 'out' },
                ],
            },
            {
                id: 11,
                code: 'HR',
                name: 'Human Resources',
                business_unit_id: 1,
                business_unit_code: 'WNS',
                business_unit_name: 'Werkudara Nirwana Sakti',
                template_type: 'hr',
                actions: [
                    { code: 'OUT_HR_GAJI_BENEFIT', label: 'Gaji & Benefit Karyawan', flow_type: 'out' },
                ],
            },
            {
                id: 21,
                code: 'OPS',
                name: 'Operations',
                business_unit_id: 2,
                business_unit_code: 'MRP',
                business_unit_name: 'Morpheus',
                template_type: 'standard',
                actions: [
                    { code: 'OUT_OPS_OPS', label: 'Operational OPS', flow_type: 'out' },
                ],
            },
        ],
        lineItems: [
            {
                id: 99,
                department_id: 11,
                department_code: 'HR',
                department_name: 'Human Resources',
                business_unit_id: 1,
                business_unit_code: 'WNS',
                business_unit_name: 'Werkudara Nirwana Sakti',
                flow_type: 'out',
                action_code: 'OUT_HR_GAJI_BENEFIT',
                action_label: 'Gaji & Benefit Karyawan',
                transaction_date: '2026-03-18',
                due_date: '2026-03-18',
                amount: 1200000,
                description: 'Payroll March',
                notes: 'Urgent',
                is_estimated_date: false,
                creator_name: 'Rina',
                creator_department_label: 'CFC',
                updater_name: 'Budi',
                updater_department_label: 'HR',
            },
        ],
    };

    beforeEach(() => {
        vi.clearAllMocks();
        global.route = vi.fn((name: string, params?: Record<string, unknown>) => {
            if (name === 'cashflow-projection.line-items.update' && params?.lineItem) {
                return `/cashflow-projection.line-items.update/${params.lineItem}`;
            }

            return `/${name}`;
        });
    });

    it('lets users filter departments by selected business unit before picking category', () => {
        render(<Entries {...baseProps} />);

        fireEvent.change(screen.getByLabelText(/business unit/i), {
            target: { value: '2' },
        });
        fireEvent.change(screen.getByLabelText(/type/i), {
            target: { value: 'out' },
        });

        const departmentSelect = screen.getByLabelText(/department/i);
        expect(screen.getByRole('option', { name: /operations/i })).toBeInTheDocument();
        expect(screen.queryByRole('option', { name: /human resources/i })).not.toBeInTheDocument();
        expect(departmentSelect).toHaveValue('21');
        expect(screen.getByRole('option', { name: /ops - mrp - operational department ops/i })).toBeInTheDocument();
    });

    it('loads an existing entry into edit mode and submits a patch request', () => {
        render(<Entries {...baseProps} />);

        fireEvent.click(screen.getByRole('button', { name: /edit payroll march/i }));

        const descriptionInput = screen.getByLabelText(/entry name/i);
        expect(descriptionInput).toHaveValue('Payroll March');
        expect(screen.getByText(/editing human resources entry for wns/i)).toBeInTheDocument();

        fireEvent.change(descriptionInput, {
            target: { value: 'Payroll March Revised' },
        });
        fireEvent.submit(screen.getByRole('button', { name: /save changes/i }).closest('form') as HTMLFormElement);

        expect(patchMock).toHaveBeenCalledWith(
            '/cashflow-projection.line-items.update/99',
            expect.objectContaining({ preserveScroll: true })
        );
    });

    it('renders target and attribution metadata for each row', () => {
        render(<Entries {...baseProps} />);

        expect(screen.getByText(/wns • human resources/i)).toBeInTheDocument();
        expect(screen.getByText(/hr - gaji & benefit karyawan/i)).toBeInTheDocument();
        expect(screen.getByText(/created by: rina \(cfc\)/i)).toBeInTheDocument();
        expect(screen.getByText(/last edited by: budi \(hr\)/i)).toBeInTheDocument();
    });

    it('shows a safe empty state when there are no selectable departments', () => {
        const propsWithSingleBu = {
            ...baseProps,
            departments: [],
        } satisfies CashflowProjectionEntriesPageProps;

        render(<Entries {...propsWithSingleBu} />);

        expect(screen.getByRole('option', { name: /no business unit available/i })).toBeInTheDocument();
        expect(screen.getByRole('option', { name: /no category available/i })).toBeInTheDocument();
    });

    it('normalizes category labels for CFC entries and shows the linked BU notice', async () => {
        render(<Entries {...baseProps} />);

        expect(screen.getByRole('option', { name: /acc - piutang & revenue/i })).toBeInTheDocument();
        expect(screen.getByRole('option', { name: /cfc - suntikan modal/i })).toBeInTheDocument();

        fireEvent.change(screen.getByLabelText(/type/i), {
            target: { value: 'out' },
        });

        expect(screen.getByRole('option', { name: /acc - pajak/i })).toBeInTheDocument();
        expect(screen.getByRole('option', { name: /acc - operational department acc/i })).toBeInTheDocument();
        expect(screen.getByRole('option', { name: /tep - cost of revenue dari upcoming revenue/i })).toBeInTheDocument();
        expect(screen.getByRole('option', { name: /hr - gaji & benefit karyawan/i })).toBeInTheDocument();
        expect(screen.getByRole('option', { name: /cfc - corporate expense/i })).toBeInTheDocument();
        expect(screen.getByRole('option', { name: /cfc - operational department cfc/i })).toBeInTheDocument();

        fireEvent.change(screen.getByLabelText(/business unit/i), {
            target: { value: '2' },
        });

        await waitFor(() => {
            expect(
                screen.getByText(
                    /this entry will be saved to linked business unit mrp - morpheus, not to the active business unit wns - werkudara nirwana sakti/i
                )
            ).toBeInTheDocument();
            expect(screen.getByRole('option', { name: /ops - mrp - operational department ops/i })).toBeInTheDocument();
        });
    });
});
