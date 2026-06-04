import { Popover, Transition } from '@headlessui/react';
import { Link, router } from '@inertiajs/react';
import { Fragment, type FormEvent, useState } from 'react';
import { motion } from 'framer-motion';
import { MoreHorizontal, Pencil, Search, Trash2 } from 'lucide-react';
import type { LineItem } from '../types';
import { formatCurrency } from '../utils';

interface EntriesTableProps {
    lineItems: LineItem[];
    pagination: {
        meta: { current_page: number; last_page: number; per_page: number; total: number };
        links: { prev: string | null; next: string | null };
    };
    year: number;
    selectedMonth: number;
    search: string;
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
    onBulkDelete: (lineItemIds: number[]) => void;
}

function formatDate(dateValue: string | null): string {
    if (!dateValue) return '-';
    const [year, month, day] = dateValue.split('-').map(Number);
    if (!year || !month || !day) return dateValue;

    return new Date(year, month - 1, day).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatMonth(dateValue: string): string {
    const [year, month, day] = dateValue.split('-').map(Number);
    if (!year || !month || !day) return '-';

    return new Date(year, month - 1, day).toLocaleDateString('en-US', { month: 'short' }).toUpperCase();
}

export default function EntriesTable({
    lineItems,
    pagination,
    year,
    selectedMonth,
    search,
    activeBusinessUnitId,
    formatCategoryLabel,
    onEdit,
    onDelete,
    onBulkDelete,
}: EntriesTableProps) {
    const [searchValue, setSearchValue] = useState(search);
    const [bulkMode, setBulkMode] = useState(false);
    const [selectedIds, setSelectedIds] = useState<number[]>([]);

    const submitSearch = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const submittedSearch = String(new FormData(event.currentTarget).get('search') ?? '').trim();
        router.get(
            route('cashflow-projection.entries'),
            { year, month: selectedMonth, search: submittedSearch || undefined },
            { preserveState: true, preserveScroll: true }
        );
    };

    const toggleBulkMode = () => {
        setBulkMode((current) => !current);
        setSelectedIds([]);
    };

    const toggleSelected = (lineItemId: number) => {
        setSelectedIds((current) => current.includes(lineItemId)
            ? current.filter((id) => id !== lineItemId)
            : [...current, lineItemId]);
    };

    return (
        <motion.section
            data-testid="cashflow-ledger-shell"
            className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_18px_60px_rgba(15,23,42,0.08)] ring-1 ring-slate-950/[0.02] xl:col-span-2"
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.25, delay: 0.06 }}
        >
            <div className="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 className="text-lg font-semibold tracking-tight text-slate-950">All Entries</h2>
                    <p className="mt-1 text-sm text-slate-500">
                        {pagination.meta.total} accessible cashflow entries. Showing {lineItems.length} on this page.
                    </p>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <form onSubmit={submitSearch} className="flex gap-2">
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input
                                type="search"
                                name="search"
                                aria-label="Search entries"
                                value={searchValue}
                                onChange={(event) => setSearchValue(event.target.value)}
                                placeholder="Search document, vendor, description..."
                                className="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/80 pl-9 pr-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/15 sm:w-80"
                            />
                        </div>
                        <button
                            type="submit"
                            className="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-primary/20 transition hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-primary/20"
                        >
                            Search
                        </button>
                    </form>
                    <button
                        type="button"
                        onClick={toggleBulkMode}
                        className="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                    >
                        {bulkMode ? 'Cancel Bulk' : 'Bulk Delete'}
                    </button>
                    {bulkMode && (
                        <button
                            type="button"
                            onClick={() => onBulkDelete(selectedIds)}
                            disabled={selectedIds.length === 0}
                            className="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Delete Selected ({selectedIds.length})
                        </button>
                    )}
                </div>
            </div>

            <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                <table className="min-w-[1260px] w-full table-fixed text-left text-[11px] leading-5">
                    <thead className="sticky top-0 z-10 bg-gradient-to-b from-slate-50 to-slate-100/90 backdrop-blur">
                        <tr className="border-b border-slate-200 font-bold uppercase tracking-[0.08em] text-slate-500">
                            <th className="w-[54px] px-3 py-3">BULAN</th>
                            <th className="w-[82px] px-3 py-3">TGL BAYAR</th>
                            <th className="w-[110px] px-3 py-3">NO DOKUMEN</th>
                            <th className="w-[170px] px-3 py-3">NAMA VENDOR</th>
                            <th className="w-[300px] px-3 py-3">DESKRIPSI</th>
                            <th className="w-[130px] px-3 py-3 text-right">NOMINAL</th>
                            <th className="w-[92px] px-3 py-3">DUE DATE</th>
                            <th className="w-[130px] px-3 py-3">KETERANGAN</th>
                            <th className="w-[76px] px-3 py-3">ENTITAS</th>
                            <th className="w-[150px] px-3 py-3">ACTION</th>
                            <th className="w-[54px] px-3 py-3 text-right" aria-label="Row actions" />
                        </tr>
                    </thead>
                    <tbody>
                        {lineItems.length === 0 && (
                            <tr>
                                <td colSpan={11} className="py-12 text-center text-sm text-muted-foreground">
                                    No entries found. Add a projection or adjust your search.
                                </td>
                            </tr>
                        )}

                        {lineItems.map((item) => {
                            const displayActionLabel = formatCategoryLabel(
                                item.action_code,
                                item.action_label,
                                item.department_code,
                                item.business_unit_code,
                                Boolean(activeBusinessUnitId && item.business_unit_id && item.business_unit_id !== activeBusinessUnitId)
                            );
                            const selected = selectedIds.includes(item.id);

                            return (
                                <tr key={item.id} className="group border-b border-slate-100 last:border-0 odd:bg-white even:bg-slate-50/40 hover:bg-blue-50/50">
                                    <td className="px-3 py-3 font-bold text-slate-700">{formatMonth(item.transaction_date)}</td>
                                    <td className="px-3 py-3 text-slate-600">{formatDate(item.transaction_date)}</td>
                                    <td className="px-3 py-3 font-semibold text-slate-800">
                                        <span className="block whitespace-normal break-words">{item.no_dokumen || '-'}</span>
                                    </td>
                                    <td className="px-3 py-3 text-slate-700">
                                        <span className="line-clamp-2">{item.nama_vendor || '-'}</span>
                                    </td>
                                    <td className="px-3 py-3 font-medium text-slate-950">
                                        <span className="line-clamp-2 leading-5">{item.description || '-'}</span>
                                    </td>
                                    <td className={`px-3 py-3 text-right font-semibold ${item.flow_type === 'in' ? 'text-emerald-600' : 'text-red-500'}`}>
                                        {item.flow_type === 'in' ? '+' : '-'}{formatCurrency(item.amount)}
                                    </td>
                                    <td className="px-3 py-3 text-slate-600">{formatDate(item.due_date)}</td>
                                    <td className="px-3 py-3 text-slate-600">
                                        <span className="line-clamp-2">{item.keterangan || '-'}</span>
                                    </td>
                                    <td className="px-3 py-3">
                                        <span className="inline-flex rounded-full bg-slate-100 px-2 py-1 font-bold text-slate-700 group-hover:bg-white">
                                            {item.business_unit_code ?? 'N/A'}
                                        </span>
                                    </td>
                                    <td className="px-3 py-3 text-slate-600">
                                        <span className="line-clamp-2">{displayActionLabel}</span>
                                    </td>
                                    <td className="px-3 py-3 text-right">
                                        {bulkMode ? (
                                            <input
                                                type="checkbox"
                                                aria-label={`Select ${item.description || item.action_label}`}
                                                checked={selected}
                                                onChange={() => toggleSelected(item.id)}
                                                className="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                                            />
                                        ) : (
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
                                        )}
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
            {pagination.meta.last_page > 1 && (
                <div className="mt-5 flex items-center justify-between border-t border-border pt-4 text-sm text-muted-foreground">
                    <span>Page {pagination.meta.current_page} of {pagination.meta.last_page}</span>
                    <div className="flex gap-2">
                        {pagination.links.prev ? (
                            <Link href={pagination.links.prev} className="rounded-lg border border-slate-200 px-3 py-1.5 font-medium text-slate-700 hover:bg-slate-50">Previous</Link>
                        ) : (
                            <span className="rounded-lg border border-slate-100 px-3 py-1.5 text-slate-300">Previous</span>
                        )}
                        {pagination.links.next ? (
                            <Link href={pagination.links.next} className="rounded-lg border border-slate-200 px-3 py-1.5 font-medium text-slate-700 hover:bg-slate-50">Next</Link>
                        ) : (
                            <span className="rounded-lg border border-slate-100 px-3 py-1.5 text-slate-300">Next</span>
                        )}
                    </div>
                </div>
            )}
        </motion.section>
    );
}
