import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { type FormEvent, useEffect, useMemo, useState } from 'react';
import { ArrowLeft, Pencil, Server, ShoppingBag, Users } from 'lucide-react';
import { motion } from 'framer-motion';
import './cashflow-dashboard.css';
import AddProjectionCard from './components/AddProjectionCard';
import type { CashflowProjectionEntriesPageProps, LineItemFormData } from './types';
import { formatCurrency, formatMonthLabel } from './utils';
import type { PageProps } from '@/types';

type FlowType = 'in' | 'out';

type CategoryOption = {
    value: string;
    label: string;
    flow_type: FlowType;
    department_id: number;
};

type BusinessUnitOption = {
    id: number;
    code: string;
    name: string;
};

function extractDepartmentCode(actionCode: string, fallbackCode: string): string {
    const match = actionCode.match(/^[A-Z]+_([A-Z0-9]+)_/);
    return match?.[1] ?? fallbackCode;
}

function formatCategoryLabel(actionCode: string, actionLabel: string, departmentCode: string): string {
    const normalizedDepartmentCode = extractDepartmentCode(actionCode, departmentCode);
    const prefixedLabelPattern = /^[A-Z0-9]+ - /;

    if (prefixedLabelPattern.test(actionLabel)) {
        return actionLabel;
    }

    const normalizedLabel = actionLabel.toLowerCase().startsWith('operational')
        ? `Operational Department ${normalizedDepartmentCode}`
        : actionLabel;

    return `${normalizedDepartmentCode} - ${normalizedLabel}`;
}

function toIsoDate(year: number, month: number, day: number): string {
    const safeMonth = String(Math.max(1, Math.min(12, month))).padStart(2, '0');
    const safeDay = String(Math.max(1, day)).padStart(2, '0');
    return `${year}-${safeMonth}-${safeDay}`;
}

function resolveIcon(actionLabel: string) {
    const text = actionLabel.toLowerCase();
    if (text.includes('revenue') || text.includes('sales')) return ShoppingBag;
    if (text.includes('gaji') || text.includes('hr') || text.includes('karyawan')) return Users;
    return Server;
}

function formatDate(dateValue: string): string {
    const [year, month, day] = dateValue.split('-').map(Number);
    if (!year || !month || !day) return dateValue;
    return new Date(year, month - 1, day).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

const statusBadgeMap: Record<string, string> = {
    confirmed: 'bg-emerald-100 text-emerald-700',
    pending: 'bg-amber-100 text-amber-700',
    projected: 'bg-blue-100 text-blue-700',
};

export default function CashflowProjectionEntries({
    year,
    selectedMonth,
    departments,
    lineItems,
}: CashflowProjectionEntriesPageProps) {
    const { currentBusinessUnit: activeBusinessUnit } = usePage<PageProps>().props;
    const [flowType, setFlowType] = useState<FlowType>('in');
    const [editingLineItemId, setEditingLineItemId] = useState<number | null>(null);

    const defaultTransactionDate = useMemo(() => {
        const now = new Date();
        if (now.getFullYear() === year && now.getMonth() + 1 === selectedMonth) {
            return toIsoDate(year, selectedMonth, now.getDate());
        }
        return toIsoDate(year, selectedMonth, 1);
    }, [year, selectedMonth]);

    const businessUnitOptions = useMemo<BusinessUnitOption[]>(() => {
        return departments.reduce<BusinessUnitOption[]>((options, department) => {
            if (!department.business_unit_id || !department.business_unit_code) {
                return options;
            }

            if (options.some((option) => option.id === department.business_unit_id)) {
                return options;
            }

            return [
                ...options,
                {
                    id: department.business_unit_id,
                    code: department.business_unit_code,
                    name: department.business_unit_name ?? department.business_unit_code,
                },
            ];
        }, []);
    }, [departments]);

    const [selectedBusinessUnitId, setSelectedBusinessUnitId] = useState<number>(() => businessUnitOptions[0]?.id ?? 0);

    const departmentsForSelectedBusinessUnit = useMemo(() => {
        return departments.filter((department) => department.business_unit_id === selectedBusinessUnitId);
    }, [departments, selectedBusinessUnitId]);

    const selectedBusinessUnitOption = useMemo(() => {
        return businessUnitOptions.find((option) => option.id === selectedBusinessUnitId) ?? null;
    }, [businessUnitOptions, selectedBusinessUnitId]);

    const linkedBusinessUnitNotice = useMemo(() => {
        if (!activeBusinessUnit || !selectedBusinessUnitOption || selectedBusinessUnitOption.id === activeBusinessUnit.id) {
            return null;
        }

        return `This entry will be saved to linked business unit ${selectedBusinessUnitOption.code} - ${selectedBusinessUnitOption.name}, not to the active business unit ${activeBusinessUnit.code} - ${activeBusinessUnit.name}.`;
    }, [activeBusinessUnit, selectedBusinessUnitOption]);

    const lineItemForm = useForm<LineItemFormData>({
        year,
        business_unit_id: selectedBusinessUnitId,
        department_id: departmentsForSelectedBusinessUnit[0]?.id ?? 0,
        action_code: '',
        transaction_date: defaultTransactionDate,
        due_date: '',
        is_estimated_date: false,
        amount: 0,
        description: '',
        notes: '',
    });
    const { data, setData, post, patch, processing, errors } = lineItemForm;

    const selectedDepartment = useMemo(() => {
        return departments.find((department) => department.id === data.department_id) ?? null;
    }, [data.department_id, departments]);

    const filteredCategoryOptions = useMemo<CategoryOption[]>(() => {
        if (!selectedDepartment) {
            return [];
        }

        return selectedDepartment.actions
            .filter((action) => action.flow_type === flowType)
            .map((action) => ({
                value: action.code,
                label: formatCategoryLabel(action.code, action.label, selectedDepartment.code),
                flow_type: action.flow_type,
                department_id: selectedDepartment.id,
            }));
    }, [flowType, selectedDepartment]);

    useEffect(() => {
        if (!businessUnitOptions.length) {
            return;
        }

        if (businessUnitOptions.some((option) => option.id === selectedBusinessUnitId)) {
            return;
        }

        const fallbackBusinessUnitId = businessUnitOptions[0].id;
        setSelectedBusinessUnitId(fallbackBusinessUnitId);
        setData('business_unit_id', fallbackBusinessUnitId);
    }, [businessUnitOptions, selectedBusinessUnitId, setData]);

    useEffect(() => {
        setData('business_unit_id', selectedBusinessUnitId);

        if (!departmentsForSelectedBusinessUnit.length) {
            setData('department_id', 0);
            setData('action_code', '');
            return;
        }

        if (departmentsForSelectedBusinessUnit.some((department) => department.id === data.department_id)) {
            return;
        }

        setData('department_id', departmentsForSelectedBusinessUnit[0].id);
    }, [data.department_id, departmentsForSelectedBusinessUnit, selectedBusinessUnitId, setData]);

    useEffect(() => {
        if (!filteredCategoryOptions.length) {
            setData('action_code', '');
            return;
        }

        if (filteredCategoryOptions.some((option) => option.value === data.action_code)) {
            return;
        }

        setData('action_code', filteredCategoryOptions[0].value);
    }, [data.action_code, filteredCategoryOptions, setData]);

    useEffect(() => {
        setData('year', year);
        setData('transaction_date', defaultTransactionDate);
    }, [defaultTransactionDate, selectedMonth, setData, year]);

    const handleLineItemSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editingLineItemId !== null) {
            patch(route('cashflow-projection.line-items.update', { lineItem: editingLineItemId }), {
                preserveScroll: true,
            });

            return;
        }

        post(route('cashflow-projection.line-items.store'), { preserveScroll: true });
    };

    const handleCategoryChange = (categoryCode: string) => {
        const category = filteredCategoryOptions.find((option) => option.value === categoryCode);
        if (!category) return;
        setData('action_code', category.value);
        setData('department_id', category.department_id);
    };

    const handleBusinessUnitChange = (businessUnitId: number) => {
        setSelectedBusinessUnitId(businessUnitId);
    };

    const handleDepartmentChange = (departmentId: number) => {
        setData('department_id', departmentId);
    };

    const resetForm = () => {
        setEditingLineItemId(null);
        setFlowType('in');
        setSelectedBusinessUnitId(businessUnitOptions[0]?.id ?? 0);
        setData({
            year,
            business_unit_id: businessUnitOptions[0]?.id ?? 0,
            department_id: departments.filter((department) => department.business_unit_id === (businessUnitOptions[0]?.id ?? 0))[0]?.id ?? 0,
            action_code: '',
            transaction_date: defaultTransactionDate,
            due_date: '',
            is_estimated_date: false,
            amount: 0,
            description: '',
            notes: '',
        });
    };

    const beginEdit = (lineItemId: number) => {
        const lineItem = lineItems.find((item) => item.id === lineItemId);
        if (!lineItem) {
            return;
        }

        setEditingLineItemId(lineItemId);
        setFlowType(lineItem.flow_type);
        setSelectedBusinessUnitId(lineItem.business_unit_id ?? 0);
        setData({
            year,
            business_unit_id: lineItem.business_unit_id ?? 0,
            department_id: lineItem.department_id,
            action_code: lineItem.action_code,
            transaction_date: lineItem.transaction_date,
            due_date: lineItem.due_date ?? '',
            is_estimated_date: lineItem.is_estimated_date,
            amount: lineItem.amount,
            description: lineItem.description,
            notes: lineItem.notes ?? '',
        });
    };

    return (
        <>
            <Head title="Cashflow Entries" />

            <div className="w-full px-6 py-8 lg:px-8 2xl:px-10">
                <div className="mx-auto w-full max-w-screen-2xl space-y-8">
                    {/* Page Header */}
                    <div className="flex items-start justify-between">
                        <div className="space-y-1">
                            <Link
                                href={route('cashflow-projection.index')}
                                className="mb-2 inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-primary"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                Back to Dashboard
                            </Link>
                            <h1 className="text-[2rem] font-bold text-foreground">Cashflow Entries</h1>
                            <p className="text-sm text-muted-foreground">
                                {formatMonthLabel(selectedMonth)} {year} &mdash; Add and manage projection entries.
                            </p>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-6 xl:grid-cols-3">
                        {/* Left column — Add Projection Form */}
                        <div className="space-y-6">
                            <AddProjectionCard
                                lineItemData={data}
                                lineItemProcessing={processing}
                                flowType={flowType}
                                businessUnitOptions={businessUnitOptions}
                                departmentOptions={departmentsForSelectedBusinessUnit.map((department) => ({
                                    id: department.id,
                                    name: department.name,
                                }))}
                                categoryOptions={filteredCategoryOptions}
                                isEditing={editingLineItemId !== null}
                                selectedDepartmentName={selectedDepartment?.name}
                                selectedBusinessUnitCode={selectedBusinessUnitOption?.code}
                                businessUnitNotice={linkedBusinessUnitNotice}
                                fieldErrors={errors}
                                onFlowTypeChange={setFlowType}
                                onBusinessUnitChange={handleBusinessUnitChange}
                                onDepartmentChange={handleDepartmentChange}
                                onCategoryChange={handleCategoryChange}
                                onCancelEdit={resetForm}
                                onFieldChange={setData}
                                onSubmit={handleLineItemSubmit}
                            />
                        </div>

                        {/* Right column — Full transactions table */}
                        <motion.section
                            className="rounded-2xl border border-border bg-card p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04)] xl:col-span-2"
                            initial={{ opacity: 0, y: 12 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.25, delay: 0.06 }}
                        >
                            <div className="mb-5 flex items-center justify-between">
                                <div>
                                    <h2 className="text-xl font-semibold text-foreground">All Entries</h2>
                                    <p className="mt-1 text-sm text-muted-foreground">{lineItems.length} projection entries for {formatMonthLabel(selectedMonth)} {year}</p>
                                </div>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead>
                                        <tr className="border-b border-border text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                            <th className="py-3 pr-4">Transaction</th>
                                            <th className="py-3 pr-4">Category</th>
                                            <th className="py-3 pr-4">Department</th>
                                            <th className="py-3 pr-4">Attribution</th>
                                            <th className="py-3 pr-4">Date</th>
                                            <th className="py-3 pr-4">Status</th>
                                            <th className="py-3 pr-4 text-right">Action</th>
                                            <th className="py-3 text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {lineItems.length === 0 && (
                                            <tr>
                                                <td colSpan={8} className="py-12 text-center text-sm text-muted-foreground">
                                                    No entries yet. Use the form to add your first projection.
                                                </td>
                                            </tr>
                                        )}

                                        {lineItems.map((item) => {
                                            const status = item.is_estimated_date ? 'pending' : item.flow_type === 'in' ? 'confirmed' : 'projected';
                                            const statusLabel = status === 'confirmed' ? 'Confirmed' : status === 'pending' ? 'Pending' : 'Projected';
                                            const displayActionLabel = formatCategoryLabel(
                                                item.action_code,
                                                item.action_label,
                                                item.department_code
                                            );
                                            const Icon = resolveIcon(displayActionLabel);

                                            return (
                                                <tr key={item.id} className="border-b border-border/50 last:border-0">
                                                    <td className="py-3 pr-4">
                                                        <div className="flex items-center gap-3">
                                                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                                                <Icon className="h-4 w-4" />
                                                            </div>
                                                            <span className="font-medium text-foreground">{item.description || displayActionLabel}</span>
                                                        </div>
                                                    </td>
                                                    <td className="py-3 pr-4 text-muted-foreground">{displayActionLabel}</td>
                                                    <td className="py-3 pr-4 text-muted-foreground">
                                                        <div className="space-y-1">
                                                            <p>{item.business_unit_code} • {item.department_name}</p>
                                                            {item.business_unit_name && (
                                                                <p className="text-xs text-slate-400">{item.business_unit_name}</p>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="py-3 pr-4 text-muted-foreground">
                                                        <div className="space-y-1 text-xs">
                                                            {item.creator_name && item.creator_department_label && (
                                                                <p>Created by: {item.creator_name} ({item.creator_department_label})</p>
                                                            )}
                                                            {item.updater_name && item.updater_department_label && (
                                                                <p>Last edited by: {item.updater_name} ({item.updater_department_label})</p>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="py-3 pr-4 text-muted-foreground">{formatDate(item.transaction_date)}</td>
                                                    <td className="py-3 pr-4">
                                                        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase ${statusBadgeMap[status] || 'bg-slate-100 text-slate-700'}`}>
                                                            {statusLabel}
                                                        </span>
                                                    </td>
                                                    <td className="py-3 pr-4 text-right">
                                                        <button
                                                            type="button"
                                                            onClick={() => beginEdit(item.id)}
                                                            aria-label={`Edit ${item.description || item.action_label}`}
                                                            className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 transition hover:bg-slate-50"
                                                        >
                                                            <Pencil className="h-3.5 w-3.5" />
                                                            Edit
                                                        </button>
                                                    </td>
                                                    <td className={`py-3 text-right font-semibold ${item.flow_type === 'in' ? 'text-emerald-600' : 'text-red-500'}`}>
                                                        {item.flow_type === 'in' ? '+' : '-'}{formatCurrency(item.amount)}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </motion.section>
                    </div>
                </div>
            </div>
        </>
    );
}
