import { fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { ComponentProps } from 'react';
import Entries from '@/Pages/CashflowProjection/Entries';
import type { CashflowProjectionEntriesPageProps, LineItemFormData } from '@/Pages/CashflowProjection/types';

const { postMock, patchMock, deleteMock, showToastSuccessMock, currentBusinessUnit, pageFlashState } = vi.hoisted(() => ({
    postMock: vi.fn(),
    patchMock: vi.fn(),
    deleteMock: vi.fn(),
    showToastSuccessMock: vi.fn(),
    currentBusinessUnit: {
        id: 1,
        code: 'WNS',
        name: 'Werkudara Nirwana Sakti',
        logo: null,
    },
    pageFlashState: {
        cashflow_import: undefined as Record<string, unknown> | undefined,
    },
}));

vi.mock('@inertiajs/react', async () => {
    const React = await vi.importActual<typeof import('react')>('react');
    const actual = await vi.importActual<typeof import('@inertiajs/react')>('@inertiajs/react');

    return {
        ...actual,
        router: {
            ...actual.router,
            delete: deleteMock,
        },
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
                reset: vi.fn(),
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
                flash: pageFlashState,
            },
        }),
    };
});

vi.mock('@/components/ui/toast', () => ({
    showToast: {
        success: showToastSuccessMock,
    },
}));

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
                    { code: 'IN_ACC_PIUTANG_REVENUE', label: 'ACC - Piutang & Revenue', flow_type: 'in' },
                    { code: 'IN_TEP_ESTIMASI_UPCOMING_REVENUE', label: 'TEP - Estimasi Penerimaan dari Upcoming Revenue', flow_type: 'in' },
                    { code: 'IN_CFC_SUNTIKAN_MODAL', label: 'CFC - Suntikan Modal', flow_type: 'in' },
                    { code: 'OUT_ACC_PAJAK', label: 'ACC - Pajak', flow_type: 'out' },
                    { code: 'OUT_ACC_OPS', label: 'ACC - Operational Department ACC', flow_type: 'out' },
                    { code: 'OUT_TEP_COST_OF_REVENUE', label: 'TEP - Cost of Revenue dari Upcoming Revenue', flow_type: 'out' },
                    { code: 'OUT_HR_GAJI_BENEFIT', label: 'HR - Gaji & Benefit Karyawan', flow_type: 'out' },
                    { code: 'OUT_CFC_CORPORATE_EXPENSES', label: 'CFC - Corporate Expense', flow_type: 'out' },
                    { code: 'OUT_CFC_OPS', label: 'CFC - Operational Department CFC', flow_type: 'out' },
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
                    { code: 'OUT_HR_GAJI_BENEFIT', label: 'HR - Gaji & Benefit Karyawan', flow_type: 'out' },
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
                    { code: 'OUT_OPS_OPS', label: 'OPS - Operational Department OPS', flow_type: 'out' },
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
                action_label: 'HR - Gaji & Benefit Karyawan',
                transaction_date: '2026-03-18',
                due_date: '2026-03-18',
                amount: 1200000,
                description: 'Payroll March',
                notes: 'Urgent',
                is_estimated_date: false,
                has_edit_history: true,
                creator_name: 'Rina',
                creator_department_label: 'CFC',
                updater_name: 'Budi',
                updater_department_label: 'HR',
            },
            {
                id: 100,
                department_id: 10,
                department_code: 'CFC',
                department_name: 'Core Finance',
                business_unit_id: 1,
                business_unit_code: 'WNS',
                business_unit_name: 'Werkudara Nirwana Sakti',
                flow_type: 'in',
                action_code: 'IN_ACC_PIUTANG_REVENUE',
                action_label: 'ACC - Piutang & Revenue',
                transaction_date: '2026-03-21',
                due_date: '2026-03-21',
                amount: 450000,
                description: 'Same author entry',
                notes: null,
                is_estimated_date: false,
                has_edit_history: false,
                creator_name: 'Sari',
                creator_department_label: 'ACC',
                updater_name: 'Sari',
                updater_department_label: 'ACC',
            },
            {
                id: 101,
                department_id: 10,
                department_code: 'CFC',
                department_name: 'Core Finance',
                business_unit_id: 1,
                business_unit_code: 'WNS',
                business_unit_name: 'Werkudara Nirwana Sakti',
                flow_type: 'out',
                action_code: 'OUT_CFC_CORPORATE_EXPENSES',
                action_label: 'CFC - Corporate Expense',
                transaction_date: '2026-03-25',
                due_date: '2026-03-25',
                amount: 900000,
                description: 'Same author edited entry',
                notes: null,
                is_estimated_date: false,
                has_edit_history: true,
                creator_name: 'Tono',
                creator_department_label: 'CFC',
                updater_name: 'Tono',
                updater_department_label: 'CFC',
            },
            {
                id: 102,
                department_id: 10,
                department_code: 'CFC',
                department_name: 'Core Finance',
                business_unit_id: 1,
                business_unit_code: 'WNS',
                business_unit_name: 'Werkudara Nirwana Sakti',
                flow_type: 'out',
                action_code: 'OUT_CFC_OPS',
                action_label: 'CFC - Operational Department CFC',
                transaction_date: '2026-03-28',
                due_date: '2026-03-28',
                amount: 700000,
                description: 'Estimated utility payment',
                notes: null,
                is_estimated_date: true,
                has_edit_history: false,
                creator_name: 'Ayu',
                creator_department_label: 'CFC',
                updater_name: 'Ayu',
                updater_department_label: 'CFC',
            },
        ],
    };

    beforeEach(() => {
        vi.clearAllMocks();
        pageFlashState.cashflow_import = undefined;
        global.route = vi.fn((name: string, params?: Record<string, unknown>) => {
            if (name === 'cashflow-projection.entries.import-template') {
                return `/cashflow-projection.entries.import-template?year=${params?.year}&month=${params?.month}`;
            }

            if (name === 'cashflow-projection.entries.import') {
                return '/cashflow-projection.entries.import';
            }

            if (name === 'cashflow-projection.line-items.update' && params?.lineItem) {
                return `/cashflow-projection.line-items.update/${params.lineItem}`;
            }

            if (name === 'cashflow-projection.line-items.destroy' && params?.lineItem) {
                return `/cashflow-projection.line-items.destroy/${params.lineItem}`;
            }

            return `/${name}`;
        });
    });

    it('shows import actions and opens the upload dialog', () => {
        render(<Entries {...baseProps} />);

        expect(screen.getByRole('link', { name: /download template/i })).toHaveAttribute(
            'href',
            '/cashflow-projection.entries.import-template?year=2026&month=3'
        );
        expect(screen.getByRole('button', { name: /import excel/i })).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: /import excel/i }));

        expect(screen.getByRole('dialog')).toBeInTheDocument();
        expect(screen.getByText(/upload file `.xlsx` yang mengikuti template resmi/i)).toBeInTheDocument();
    });

    it('submits xlsx uploads to the import route using form data', async () => {
        render(<Entries {...baseProps} />);

        fireEvent.click(screen.getByRole('button', { name: /import excel/i }));

        const file = new File(
            ['spreadsheet'],
            'cashflow_entries_import.xlsx',
            { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }
        );

        fireEvent.change(screen.getByLabelText(/excel file/i), {
            target: { files: [file] },
        });
        fireEvent.click(screen.getByRole('button', { name: /upload import/i }));

        await waitFor(() => {
            expect(postMock).toHaveBeenCalledWith(
                '/cashflow-projection.entries.import',
                expect.objectContaining({
                    preserveScroll: true,
                    forceFormData: true,
                    onSuccess: expect.any(Function),
                })
            );
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
        expect(screen.getByRole('option', { name: /ops - mrp - operational/i })).toBeInTheDocument();
    });

    it('opens row actions and keeps the edit flow intact from the dropdown', () => {
        render(<Entries {...baseProps} />);

        fireEvent.click(screen.getByRole('button', { name: /more actions for payroll march/i }));

        expect(screen.getByRole('button', { name: /edit entry/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /delete entry/i })).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: /edit entry/i }));

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

    it('confirms deletion from the row actions menu and preserves scroll on delete', async () => {
        deleteMock.mockImplementation((_url, options?: { onSuccess?: (page?: unknown) => void; onFinish?: () => void }) => {
            options?.onSuccess?.({
                props: {
                    flash: {
                        success: 'Line item cashflow berhasil dihapus.',
                    },
                },
            });
            options?.onFinish?.();
        });

        render(<Entries {...baseProps} />);

        fireEvent.click(screen.getByRole('button', { name: /more actions for payroll march/i }));
        fireEvent.click(screen.getByRole('button', { name: /delete entry/i }));

        expect(screen.getByRole('dialog')).toBeInTheDocument();
        expect(screen.getByText(/delete this entry/i)).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: /^delete$/i }));

        await waitFor(() => {
            expect(deleteMock).toHaveBeenCalledWith(
                '/cashflow-projection.line-items.destroy/99',
                expect.objectContaining({ preserveScroll: true })
            );
        });

        expect(showToastSuccessMock).toHaveBeenCalledWith('Line item cashflow berhasil dihapus.');
    });

    it('prevents duplicate delete requests while the confirmation action is loading', async () => {
        render(<Entries {...baseProps} />);

        fireEvent.click(screen.getByRole('button', { name: /more actions for payroll march/i }));
        fireEvent.click(screen.getByRole('button', { name: /delete entry/i }));

        const confirmButton = within(screen.getByRole('dialog')).getByRole('button', { name: /^delete$/i });

        fireEvent.click(confirmButton);
        fireEvent.click(confirmButton);

        await waitFor(() => {
            expect(deleteMock).toHaveBeenCalledTimes(1);
        });
    });

    it('renders failed import details from the shared flash payload', () => {
        pageFlashState.cashflow_import = {
            status: 'failed',
            summary: 'Import gagal. Perbaiki file lalu coba lagi.',
            file_name: 'cashflow_entries_import.xlsx',
            total_rows: 12,
            processed_rows: 12,
            created_rows: 0,
            updated_rows: 0,
            failed_rows: 2,
            truncated: false,
            errors: [
                {
                    row: 7,
                    column: 'action_code',
                    message: 'Kode tidak valid untuk department HR.',
                    value: 'OUT_HR_UNKNOWN',
                },
                {
                    row: null,
                    column: 'template',
                    message: 'Header sheet Template harus sesuai urutan template resmi.',
                    value: null,
                },
            ],
        };

        render(<Entries {...baseProps} />);

        expect(screen.getByText(/import gagal. perbaiki file lalu coba lagi./i)).toBeInTheDocument();
        expect(screen.getByText(/source file:/i)).toBeInTheDocument();
        expect(screen.getByText(/row 7 - action_code: kode tidak valid untuk department hr./i)).toBeInTheDocument();
        expect(screen.getByText(/template - template: header sheet template harus sesuai urutan template resmi./i)).toBeInTheDocument();
    });

    it('renders success import summary from the shared flash payload', () => {
        pageFlashState.cashflow_import = {
            status: 'success',
            summary: 'Import berhasil diproses.',
            file_name: 'cashflow_entries_import.xlsx',
            total_rows: 8,
            processed_rows: 8,
            created_rows: 5,
            updated_rows: 3,
            failed_rows: 0,
            truncated: false,
            errors: [],
        };

        render(<Entries {...baseProps} />);

        expect(screen.getByText(/import berhasil diproses./i)).toBeInTheDocument();
        expect(screen.getByText('8')).toBeInTheDocument();
        expect(screen.getByText('5')).toBeInTheDocument();
        expect(screen.getByText('3')).toBeInTheDocument();
        expect(screen.getByText(/cashflow_entries_import.xlsx/i)).toBeInTheDocument();
    });

    it('renders business unit codes and only shows last edited for meaningful edits', () => {
        render(<Entries {...baseProps} />);

        expect(screen.getAllByText(/^WNS$/i)).toHaveLength(4);
        expect(screen.queryByText(/human resources/i, { selector: 'td' })).not.toBeInTheDocument();
        expect(screen.getByText(/hr - gaji & benefit karyawan/i)).toBeInTheDocument();
        expect(screen.getByText(/created by: rina \(cfc\)/i)).toBeInTheDocument();
        expect(screen.getByText(/last edited by: budi \(hr\)/i)).toBeInTheDocument();
        expect(screen.getByText(/created by: sari \(acc\)/i)).toBeInTheDocument();
        expect(screen.queryByText(/last edited by: sari \(acc\)/i)).not.toBeInTheDocument();
        expect(screen.getByText(/created by: tono \(cfc\)/i)).toBeInTheDocument();
        expect(screen.getByText(/last edited by: tono \(cfc\)/i)).toBeInTheDocument();
        expect(screen.getAllByText(/confirmed/i).length).toBeGreaterThan(0);
        expect(screen.getByText(/pending/i)).toBeInTheDocument();
        expect(screen.queryByText(/projected/i)).not.toBeInTheDocument();
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
        expect(screen.getByRole('option', { name: /acc - operational/i })).toBeInTheDocument();
        expect(screen.getByRole('option', { name: /tep - cost of revenue dari upcoming revenue/i })).toBeInTheDocument();
        expect(screen.getByRole('option', { name: /hr - gaji & benefit karyawan/i })).toBeInTheDocument();
        expect(screen.getByRole('option', { name: /cfc - corporate expense/i })).toBeInTheDocument();
        expect(screen.getByRole('option', { name: /cfc - operational/i })).toBeInTheDocument();

        fireEvent.change(screen.getByLabelText(/business unit/i), {
            target: { value: '2' },
        });

        await waitFor(() => {
            expect(
                screen.getByText(
                    /this entry will be saved to linked business unit mrp - morpheus, not to the active business unit wns - werkudara nirwana sakti/i
                )
            ).toBeInTheDocument();
            expect(screen.getByRole('option', { name: /ops - mrp - operational/i })).toBeInTheDocument();
        });
    });

    it('dedupes and groups category options by department prefix while shortening operational labels', () => {
        render(
            <Entries
                {...baseProps}
                departments={[
                    {
                        id: 10,
                        code: 'CFC',
                        name: 'Core Finance',
                        business_unit_id: 1,
                        business_unit_code: 'WNS',
                        business_unit_name: 'Werkudara Nirwana Sakti',
                        template_type: 'cfc',
                        actions: [
                            { code: 'OUT_CFC_OPS', label: 'CFC - Operational Department CFC', flow_type: 'out' },
                            { code: 'OUT_ACC_PAJAK', label: 'ACC - Pajak', flow_type: 'out' },
                            { code: 'OUT_ACC_OPS', label: 'ACC - Operational Department ACC', flow_type: 'out' },
                            { code: 'OUT_ACC_OPS', label: 'ACC - Operational Department ACC', flow_type: 'out' },
                            { code: 'OUT_TEP_COST_OF_REVENUE', label: 'TEP - Cost of Revenue dari Upcoming Revenue', flow_type: 'out' },
                            { code: 'OUT_HR_GAJI_BENEFIT', label: 'HR - Gaji & Benefit Karyawan', flow_type: 'out' },
                            { code: 'OUT_CFC_CORPORATE_EXPENSES', label: 'CFC - Corporate Expense', flow_type: 'out' },
                            { code: 'OUT_CFC_OPS', label: 'CFC - Operational Department CFC', flow_type: 'out' },
                        ],
                    },
                ]}
            />
        );

        fireEvent.change(screen.getByLabelText(/type/i), {
            target: { value: 'out' },
        });

        const categorySelect = screen.getByLabelText(/category/i);
        const optionLabels = within(categorySelect)
            .getAllByRole('option')
            .map((option) => option.textContent?.trim())
            .filter((label): label is string => Boolean(label));

        expect(optionLabels).toEqual([
            'ACC - Operational',
            'ACC - Pajak',
            'CFC - Corporate Expense',
            'CFC - Operational',
            'HR - Gaji & Benefit Karyawan',
            'TEP - Cost of Revenue dari Upcoming Revenue',
        ]);
        expect(optionLabels.filter((label) => label === 'ACC - Operational')).toHaveLength(1);
        expect(optionLabels.find((label) => label.includes('Operational Department ACC'))).toBeUndefined();
    });
});
