import { Popover, Transition } from '@headlessui/react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { type FormEvent, Fragment, useEffect, useMemo, useState } from 'react';
import { ArrowLeft, Download, MoreHorizontal, Pencil, Server, ShoppingBag, Trash2, Upload, Users } from 'lucide-react';
import { motion } from 'framer-motion';
import './cashflow-dashboard.css';
import AddProjectionCard from './components/AddProjectionCard';
import ImportEntriesDialog from './components/ImportEntriesDialog';
import type { CashflowProjectionEntriesPageProps, LineItemFormData } from './types';
import { formatCurrency, formatMonthLabel } from './utils';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Button } from '@/components/ui/button';
import { showToast } from '@/components/ui/toast';
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

function normalizeActionLabel(actionLabel: string): string {
    const prefixedParts = actionLabel.split(' - ');
    const coreLabel = prefixedParts.length >= 2
        ? prefixedParts.slice(prefixedParts.length >= 3 ? 2 : 1).join(' - ')
        : actionLabel;

    if (coreLabel.toLowerCase().startsWith('operational department')) {
        return 'Operational';
    }

    if (prefixedParts.length >= 2) {
        return coreLabel;
    }

    return actionLabel;
}

function formatCategoryLabel(
    actionCode: string,
    actionLabel: string,
    departmentCode: string,
    businessUnitCode?: string,
    isLinkedBusinessUnit: boolean = false
): string {
    const normalizedDepartmentCode = extractDepartmentCode(actionCode, departmentCode);
    const normalizedLabel = normalizeActionLabel(actionLabel);

    const labelParts = [normalizedDepartmentCode];

    if (isLinkedBusinessUnit && businessUnitCode) {
        labelParts.push(businessUnitCode);
    }

    labelParts.push(normalizedLabel);

    return labelParts.join(' - ');
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

function hasMeaningfulEdit(item: {
    has_edit_history?: boolean;
}): boolean {
    return item.has_edit_history === true;
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
    const pageProps = usePage<PageProps>().props;
    const { currentBusinessUnit: activeBusinessUnit, flash } = pageProps;
    const cashflowImportFlash = flash?.cashflow_import;
    const [flowType, setFlowType] = useState<FlowType>('in');
    const [editingLineItemId, setEditingLineItemId] = useState<number | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [isImportDialogOpen, setIsImportDialogOpen] = useState(false);
    const [deleteDialogState, setDeleteDialogState] = useState<{
        isOpen: boolean;
        lineItemId: number | null;
        label: string;
    }>({
        isOpen: false,
        lineItemId: null,
        label: '',
    });

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
    const { data, setData, post: submitLineItem, patch: updateExistingLineItem, processing, errors } = lineItemForm;

    const importForm = useForm<{
        file: File | null;
        context_year: number;
        context_month: number;
    }>({
        file: null,
        context_year: year,
        context_month: selectedMonth,
    });
    const {
        data: importData,
        setData: setImportData,
        post: submitImport,
        processing: importProcessing,
        errors: importErrors,
    } = importForm;

    const selectedDepartment = useMemo(() => {
        return departments.find((department) => department.id === data.department_id) ?? null;
    }, [data.department_id, departments]);

    const filteredCategoryOptions = useMemo<CategoryOption[]>(() => {
        if (!selectedDepartment) {
            return [];
        }

        const uniqueActions = selectedDepartment.actions.reduce<typeof selectedDepartment.actions>((actions, action) => {
            if (actions.some((existingAction) => existingAction.code === action.code)) {
                return actions;
            }

            return [...actions, action];
        }, []);

        return uniqueActions
            .filter((action) => action.flow_type === flowType)
            .sort((left, right) => {
                const leftPrefix = extractDepartmentCode(left.code, selectedDepartment.code);
                const rightPrefix = extractDepartmentCode(right.code, selectedDepartment.code);
                const prefixComparison = leftPrefix.localeCompare(rightPrefix);

                if (prefixComparison !== 0) {
                    return prefixComparison;
                }

                const leftLabel = normalizeActionLabel(left.label);
                const rightLabel = normalizeActionLabel(right.label);
                const labelComparison = leftLabel.localeCompare(rightLabel);

                if (labelComparison !== 0) {
                    return labelComparison;
                }

                return left.code.localeCompare(right.code);
            })
            .map((action) => ({
                value: action.code,
                label: formatCategoryLabel(
                    action.code,
                    action.label,
                    selectedDepartment.code,
                    selectedDepartment.business_unit_code,
                    Boolean(activeBusinessUnit && selectedDepartment.business_unit_id && selectedDepartment.business_unit_id !== activeBusinessUnit.id)
                ),
                flow_type: action.flow_type,
                department_id: selectedDepartment.id,
            }));
    }, [activeBusinessUnit, flowType, selectedDepartment]);

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

    useEffect(() => {
        setImportData('context_year', year);
        setImportData('context_month', selectedMonth);
    }, [selectedMonth, setImportData, year]);

    const handleLineItemSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editingLineItemId !== null) {
            updateExistingLineItem(route('cashflow-projection.line-items.update', { lineItem: editingLineItemId }), {
                preserveScroll: true,
            });

            return;
        }

        submitLineItem(route('cashflow-projection.line-items.store'), { preserveScroll: true });
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

    const resetImportForm = () => {
        setImportData({
            file: null,
            context_year: year,
            context_month: selectedMonth,
        });
    };

    const closeImportDialog = () => {
        setIsImportDialogOpen(false);
        resetImportForm();
    };

    const handleImportSubmit = () => {
        if (!importData.file) {
            return;
        }

        submitImport(route('cashflow-projection.entries.import'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                setIsImportDialogOpen(false);
                resetImportForm();
            },
        });
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

    const openDeleteDialog = (lineItemId: number, label: string) => {
        setDeleteDialogState({
            isOpen: true,
            lineItemId,
            label,
        });
    };

    const closeDeleteDialog = () => {
        setIsDeleting(false);
        setDeleteDialogState({
            isOpen: false,
            lineItemId: null,
            label: '',
        });
    };

    const confirmDelete = () => {
        if (!deleteDialogState.lineItemId || isDeleting) {
            return;
        }

        setIsDeleting(true);
        router.delete(route('cashflow-projection.line-items.destroy', { lineItem: deleteDialogState.lineItemId }), {
            preserveScroll: true,
            onSuccess: (page) => {
                closeDeleteDialog();
                const pageProps = page.props as unknown as PageProps;
                showToast.success(pageProps.flash?.success ?? 'Line item cashflow berhasil dihapus.');
            },
            onFinish: () => setIsDeleting(false),
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

                        <div className="flex items-center gap-3">
                            <a
                                href={route('cashflow-projection.entries.import-template', { year, month: selectedMonth })}
                                className="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50"
                            >
                                <Download className="h-4 w-4" />
                                Download Template
                            </a>
                            <Button type="button" variant="primary" onClick={() => setIsImportDialogOpen(true)}>
                                <Upload className="h-4 w-4" />
                                Import Excel
                            </Button>
                        </div>
                    </div>

                    {cashflowImportFlash && (
                        <section
                            className={`rounded-2xl border p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] ${
                                cashflowImportFlash.status === 'success'
                                    ? 'border-emerald-200 bg-emerald-50/80'
                                    : 'border-amber-200 bg-amber-50/90'
                            }`}
                        >
                            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div className="space-y-1">
                                    <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Import Summary</p>
                                    <h2 className="text-lg font-semibold text-slate-900">{cashflowImportFlash.summary}</h2>
                                    <p className="text-sm text-slate-600">
                                        Source file: <span className="font-medium text-slate-800">{cashflowImportFlash.file_name}</span>
                                    </p>
                                </div>
                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <div className="rounded-2xl border border-white/70 bg-white/80 px-4 py-3">
                                        <p className="text-[11px] uppercase tracking-[0.16em] text-slate-500">Processed</p>
                                        <p className="mt-1 text-lg font-semibold text-slate-900">{cashflowImportFlash.processed_rows}</p>
                                    </div>
                                    <div className="rounded-2xl border border-white/70 bg-white/80 px-4 py-3">
                                        <p className="text-[11px] uppercase tracking-[0.16em] text-slate-500">Created</p>
                                        <p className="mt-1 text-lg font-semibold text-emerald-700">{cashflowImportFlash.created_rows}</p>
                                    </div>
                                    <div className="rounded-2xl border border-white/70 bg-white/80 px-4 py-3">
                                        <p className="text-[11px] uppercase tracking-[0.16em] text-slate-500">Updated</p>
                                        <p className="mt-1 text-lg font-semibold text-blue-700">{cashflowImportFlash.updated_rows}</p>
                                    </div>
                                    <div className="rounded-2xl border border-white/70 bg-white/80 px-4 py-3">
                                        <p className="text-[11px] uppercase tracking-[0.16em] text-slate-500">Failed Rows</p>
                                        <p className="mt-1 text-lg font-semibold text-amber-700">{cashflowImportFlash.failed_rows}</p>
                                    </div>
                                </div>
                            </div>

                            {cashflowImportFlash.errors.length > 0 && (
                                <div className="mt-5 rounded-2xl border border-amber-200/80 bg-white/85 p-4">
                                    <p className="text-sm font-semibold text-slate-900">Validation details</p>
                                    <ul className="mt-3 space-y-2 text-sm text-slate-700">
                                        {cashflowImportFlash.errors.map((error, index) => {
                                            const prefix = error.row ? `Row ${error.row}` : 'Template';
                                            const valueText = error.value !== undefined && error.value !== null && `${error.value}` !== ''
                                                ? ` (${error.value})`
                                                : '';

                                            return (
                                                <li key={`${error.column}-${error.row ?? 'template'}-${index}`}>
                                                    {prefix} - {error.column}: {error.message}{valueText}
                                                </li>
                                            );
                                        })}
                                    </ul>

                                    {cashflowImportFlash.truncated && (
                                        <p className="mt-3 text-xs font-medium uppercase tracking-[0.14em] text-amber-700">
                                            Additional errors were truncated for readability.
                                        </p>
                                    )}
                                </div>
                            )}
                        </section>
                    )}

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
                                            <th className="py-3 pr-4">Business Unit</th>
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
                                            const status: 'confirmed' | 'pending' = item.is_estimated_date ? 'pending' : 'confirmed';
                                            const statusLabel = status === 'confirmed' ? 'Confirmed' : 'Pending';
                                            const displayActionLabel = formatCategoryLabel(
                                                item.action_code,
                                                item.action_label,
                                                item.department_code,
                                                item.business_unit_code,
                                                Boolean(activeBusinessUnit && item.business_unit_id && item.business_unit_id !== activeBusinessUnit.id)
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
                                                    <td className="py-3 pr-4 font-medium text-muted-foreground">{item.business_unit_code ?? 'N/A'}</td>
                                                    <td className="py-3 pr-4 text-muted-foreground">
                                                        <div className="space-y-1 text-xs">
                                                            {item.creator_name && item.creator_department_label && (
                                                                <p>Created by: {item.creator_name} ({item.creator_department_label})</p>
                                                            )}
                                                            {hasMeaningfulEdit(item) && item.updater_name && item.updater_department_label && (
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
                                                        <Popover className="relative inline-flex justify-end">
                                                            {({ close }) => (
                                                                <>
                                                                    <Popover.Button
                                                                        type="button"
                                                                        aria-label={`More actions for ${item.description || item.action_label}`}
                                                                        className="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/20"
                                                                    >
                                                                        <MoreHorizontal className="h-4 w-4" />
                                                                    </Popover.Button>
                                                                    <Transition
                                                                        as={Fragment}
                                                                        enter="transition ease-out duration-150"
                                                                        enterFrom="opacity-0 translate-y-1"
                                                                        enterTo="opacity-100 translate-y-0"
                                                                        leave="transition ease-in duration-100"
                                                                        leaveFrom="opacity-100 translate-y-0"
                                                                        leaveTo="opacity-0 translate-y-1"
                                                                    >
                                                                        <Popover.Panel className="absolute right-0 top-full z-20 mt-2 w-40 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
                                                                            <button
                                                                                type="button"
                                                                                onClick={() => {
                                                                                    beginEdit(item.id);
                                                                                    close();
                                                                                }}
                                                                                className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                                                                            >
                                                                                <Pencil className="h-4 w-4" />
                                                                                Edit entry
                                                                            </button>
                                                                            <button
                                                                                type="button"
                                                                                onClick={() => {
                                                                                    openDeleteDialog(item.id, item.description || item.action_label);
                                                                                    close();
                                                                                }}
                                                                                className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-rose-600 transition hover:bg-rose-50"
                                                                            >
                                                                                <Trash2 className="h-4 w-4" />
                                                                                Delete entry
                                                                            </button>
                                                                        </Popover.Panel>
                                                                    </Transition>
                                                                </>
                                                            )}
                                                        </Popover>
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

            <ConfirmDialog
                isOpen={deleteDialogState.isOpen}
                onClose={closeDeleteDialog}
                onConfirm={confirmDelete}
                title="Delete Entry"
                message={deleteDialogState.label ? `Delete this entry "${deleteDialogState.label}"? This action cannot be undone.` : 'Delete this entry? This action cannot be undone.'}
                confirmText="Delete"
                variant="danger"
                isLoading={isDeleting}
            />

            <ImportEntriesDialog
                open={isImportDialogOpen}
                processing={importProcessing}
                selectedFileName={importData.file?.name ?? null}
                flashImport={cashflowImportFlash}
                fileError={importErrors.file}
                onClose={closeImportDialog}
                onFileChange={(file) => setImportData('file', file)}
                onSubmit={handleImportSubmit}
            />
        </>
    );
}
