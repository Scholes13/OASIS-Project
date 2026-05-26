import { Popover, Transition } from '@headlessui/react';
import { Fragment } from 'react';
import { motion } from 'framer-motion';
import { MoreHorizontal, Pencil, Server, ShoppingBag, Trash2, Users } from 'lucide-react';
import type { LineItem } from '../types';
import { formatCurrency, formatMonthLabel } from '../utils';

interface EntriesTableProps {
    lineItems: LineItem[];
    year: number;
    selectedMonth: number;
    activeBusinessUnitId?: number | null;
    formatCategoryLabel: (
        actionCode: string,
        actionLabel: string,
        departmentCode: string,
        businessUnitCode?: string,
        isLinkedBusinessUnit?: boolean
    ) => string;
    onEdit: (lineItemId: number) => void;
    onDelete: (lineItemId: number, label: string) => void;
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

function hasMeaningfulEdit(item: LineItem): boolean {
    return item.has_edit_history === true;
}

const statusBadgeMap: Record<string, string> = {
    confirmed: 'bg-emerald-100 text-emerald-700',
    pending: 'bg-amber-100 text-amber-700',
    projected: 'bg-blue-100 text-blue-700',
};

export default function EntriesTable({
    lineItems,
    year,
    selectedMonth,
    activeBusinessUnitId,
    formatCategoryLabel,
    onEdit,
    onDelete,
}: EntriesTableProps) {
    return (
        <motion.section
            className="rounded-2xl border border-border bg-card p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04)] xl:col-span-2"
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.25, delay: 0.06 }}
        >
            <div className="mb-5 flex items-center justify-between">
                <div>
                    <h2 className="text-xl font-semibold text-foreground">All Entries</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {lineItems.length} projection entries for {formatMonthLabel(selectedMonth)} {year}
                    </p>
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
                                Boolean(activeBusinessUnitId && item.business_unit_id && item.business_unit_id !== activeBusinessUnitId)
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
                                                                    onEdit(item.id);
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
                                                                    onDelete(item.id, item.description || item.action_label);
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
    );
}
