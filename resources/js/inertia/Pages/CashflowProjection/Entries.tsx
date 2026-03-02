import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEvent, useEffect, useMemo, useState } from 'react';
import { ArrowLeft, Server, ShoppingBag, Users, ChevronDown, PlusCircle } from 'lucide-react';
import { motion } from 'framer-motion';
import './cashflow-dashboard.css';
import type { CashflowProjectionEntriesPageProps, LineItemFormData } from './types';
import { formatCurrency, formatMonthLabel } from './utils';

type FlowType = 'in' | 'out';

type CategoryOption = {
    value: string;
    label: string;
    flow_type: FlowType;
    department_id: number;
};

const inputClasses =
    'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-foreground outline-none transition-colors focus:border-primary focus:ring-1 focus:ring-primary [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none';

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
    const [flowType, setFlowType] = useState<FlowType>('in');

    const defaultTransactionDate = useMemo(() => {
        const now = new Date();
        if (now.getFullYear() === year && now.getMonth() + 1 === selectedMonth) {
            return toIsoDate(year, selectedMonth, now.getDate());
        }
        return toIsoDate(year, selectedMonth, 1);
    }, [year, selectedMonth]);

    const allCategoryOptions = useMemo<CategoryOption[]>(() => {
        return departments.flatMap((department) =>
            department.actions.map((action) => ({
                value: action.code,
                label: action.label,
                flow_type: action.flow_type,
                department_id: department.id,
            }))
        );
    }, [departments]);

    const filteredCategoryOptions = useMemo(() => {
        return allCategoryOptions.filter((option) => option.flow_type === flowType);
    }, [allCategoryOptions, flowType]);

    const lineItemForm = useForm<LineItemFormData>({
        year,
        department_id: filteredCategoryOptions[0]?.department_id ?? departments[0]?.id ?? 0,
        action_code: filteredCategoryOptions[0]?.value ?? '',
        transaction_date: defaultTransactionDate,
        due_date: '',
        is_estimated_date: false,
        amount: 0,
        description: '',
        notes: '',
    });

    useEffect(() => {
        if (!filteredCategoryOptions.length) {
            lineItemForm.setData('action_code', '');
            return;
        }
        const selectedCategory = filteredCategoryOptions.find((option) => option.value === lineItemForm.data.action_code);
        if (!selectedCategory) {
            const fallback = filteredCategoryOptions[0];
            lineItemForm.setData('action_code', fallback.value);
            lineItemForm.setData('department_id', fallback.department_id);
            return;
        }
        lineItemForm.setData('department_id', selectedCategory.department_id);
    }, [flowType, filteredCategoryOptions]);

    useEffect(() => {
        lineItemForm.setData('year', year);
        lineItemForm.setData('transaction_date', defaultTransactionDate);
    }, [year, selectedMonth, defaultTransactionDate]);

    const handleLineItemSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        lineItemForm.post(route('cashflow-projection.line-items.store'), { preserveScroll: true });
    };

    const handleCategoryChange = (categoryCode: string) => {
        const category = allCategoryOptions.find((option) => option.value === categoryCode);
        if (!category) return;
        lineItemForm.setData('action_code', category.value);
        lineItemForm.setData('department_id', category.department_id);
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
                            <motion.section
                                className="rounded-2xl border border-border bg-card p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04)]"
                                initial={{ opacity: 0, y: 12 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.25 }}
                            >
                                <div className="mb-5">
                                    <h2 className="text-xl font-semibold text-foreground">Add Projection</h2>
                                    <p className="mt-1 text-sm text-muted-foreground">Simulate future cashflows</p>
                                </div>

                                <form onSubmit={handleLineItemSubmit} className="space-y-4">
                                    <div>
                                        <label className="mb-1.5 block text-sm font-medium text-slate-700">Entry Name</label>
                                        <input
                                            type="text"
                                            className={inputClasses}
                                            placeholder="e.g. Q4 Server Costs"
                                            value={lineItemForm.data.description}
                                            onChange={(e) => lineItemForm.setData('description', e.target.value)}
                                        />
                                        {lineItemForm.errors.description && <p className="mt-1 text-xs text-red-500">{lineItemForm.errors.description}</p>}
                                    </div>

                                    <div>
                                        <label className="mb-1.5 block text-sm font-medium text-slate-700">Amount</label>
                                        <input
                                            type="number"
                                            min={0}
                                            className={inputClasses}
                                            placeholder="0"
                                            value={lineItemForm.data.amount}
                                            onChange={(e) => lineItemForm.setData('amount', Number(e.target.value))}
                                        />
                                        {lineItemForm.errors.amount && <p className="mt-1 text-xs text-red-500">{lineItemForm.errors.amount}</p>}
                                    </div>

                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <label className="mb-1.5 block text-sm font-medium text-slate-700">Date</label>
                                            <input
                                                type="date"
                                                className={inputClasses}
                                                value={lineItemForm.data.transaction_date}
                                                onChange={(e) => lineItemForm.setData('transaction_date', e.target.value)}
                                            />
                                            {lineItemForm.errors.transaction_date && <p className="mt-1 text-xs text-red-500">{lineItemForm.errors.transaction_date}</p>}
                                        </div>
                                        <div>
                                            <label className="mb-1.5 block text-sm font-medium text-slate-700">Type</label>
                                            <div className="relative">
                                                <select
                                                    className={`${inputClasses} appearance-none pr-8`}
                                                    value={flowType}
                                                    onChange={(e) => setFlowType(e.target.value as FlowType)}
                                                >
                                                    <option value="in">Inflow</option>
                                                    <option value="out">Outflow</option>
                                                </select>
                                                <ChevronDown className="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label className="mb-1.5 block text-sm font-medium text-slate-700">Category</label>
                                        <div className="relative">
                                            <select
                                                className={`${inputClasses} appearance-none pr-8`}
                                                value={lineItemForm.data.action_code}
                                                disabled={filteredCategoryOptions.length === 0}
                                                onChange={(e) => handleCategoryChange(e.target.value)}
                                            >
                                                {filteredCategoryOptions.length === 0 && <option value="">No category available</option>}
                                                {filteredCategoryOptions.map((option) => (
                                                    <option key={option.value} value={option.value}>{option.label}</option>
                                                ))}
                                            </select>
                                            <ChevronDown className="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        </div>
                                        {lineItemForm.errors.action_code && <p className="mt-1 text-xs text-red-500">{lineItemForm.errors.action_code}</p>}
                                    </div>

                                    <div>
                                        <label className="mb-1.5 block text-sm font-medium text-slate-700">Due Date (Optional)</label>
                                        <input
                                            type="date"
                                            className={inputClasses}
                                            value={lineItemForm.data.due_date}
                                            onChange={(e) => lineItemForm.setData('due_date', e.target.value)}
                                        />
                                    </div>

                                    <div>
                                        <label className="mb-1.5 block text-sm font-medium text-slate-700">Notes (Optional)</label>
                                        <textarea
                                            className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-foreground outline-none transition-colors focus:border-primary focus:ring-1 focus:ring-primary"
                                            rows={2}
                                            value={lineItemForm.data.notes}
                                            onChange={(e) => lineItemForm.setData('notes', e.target.value)}
                                        />
                                    </div>

                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-medium text-slate-700">Estimated Date</span>
                                        <button
                                            type="button"
                                            className={`cfp-switch ${lineItemForm.data.is_estimated_date ? 'active' : ''}`}
                                            onClick={() => lineItemForm.setData('is_estimated_date', !lineItemForm.data.is_estimated_date)}
                                        />
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={lineItemForm.processing}
                                        className="mt-2 flex w-full items-center justify-center gap-2 rounded-lg bg-[#16599c] px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-[#124a82] disabled:opacity-50"
                                    >
                                        <PlusCircle className="h-4 w-4" />
                                        {lineItemForm.processing ? 'Saving...' : 'Add to Projection'}
                                    </button>
                                </form>
                            </motion.section>
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
                                            <th className="py-3 pr-4">Date</th>
                                            <th className="py-3 pr-4">Status</th>
                                            <th className="py-3 text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {lineItems.length === 0 && (
                                            <tr>
                                                <td colSpan={6} className="py-12 text-center text-sm text-muted-foreground">
                                                    No entries yet. Use the form to add your first projection.
                                                </td>
                                            </tr>
                                        )}

                                        {lineItems.map((item) => {
                                            const status = item.is_estimated_date ? 'pending' : item.flow_type === 'in' ? 'confirmed' : 'projected';
                                            const statusLabel = status === 'confirmed' ? 'Confirmed' : status === 'pending' ? 'Pending' : 'Projected';
                                            const Icon = resolveIcon(item.action_label);

                                            return (
                                                <tr key={item.id} className="border-b border-border/50 last:border-0">
                                                    <td className="py-3 pr-4">
                                                        <div className="flex items-center gap-3">
                                                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                                                <Icon className="h-4 w-4" />
                                                            </div>
                                                            <span className="font-medium text-foreground">{item.description || item.action_label}</span>
                                                        </div>
                                                    </td>
                                                    <td className="py-3 pr-4 text-muted-foreground">{item.action_label}</td>
                                                    <td className="py-3 pr-4 text-muted-foreground">{item.department_name}</td>
                                                    <td className="py-3 pr-4 text-muted-foreground">{formatDate(item.transaction_date)}</td>
                                                    <td className="py-3 pr-4">
                                                        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase ${statusBadgeMap[status] || 'bg-slate-100 text-slate-700'}`}>
                                                            {statusLabel}
                                                        </span>
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
