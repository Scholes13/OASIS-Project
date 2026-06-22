import { motion } from 'framer-motion';
import { Server, ShoppingBag, Users } from 'lucide-react';
import type { LineItem } from '../types';
import { formatCurrency } from '../utils';

interface RecentTransactionsTableProps {
    lineItems: LineItem[];
    title?: string;
    caption?: string;
}

function resolveStatus(item: LineItem): 'confirmed' | 'pending' {
    if (item.is_estimated_date) return 'pending';
    return 'confirmed';
}

function resolveStatusLabel(status: 'confirmed' | 'pending'): string {
    if (status === 'confirmed') return 'Confirmed';
    return 'Pending';
}

const statusBadgeMap: Record<string, string> = {
    confirmed: 'bg-emerald-100 text-emerald-700',
    pending: 'bg-amber-100 text-amber-700',
};

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

export default function RecentTransactionsTable({
    lineItems,
    title = 'Upcoming & Recent',
    caption = 'Latest transactions for the selected period.',
}: RecentTransactionsTableProps) {
    return (
        <motion.section
            className="rounded-xl border border-slate-200/60 bg-white shadow-sm overflow-hidden"
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.25, ease: 'easeOut', delay: 0.16 }}
        >
            <div className="flex items-center justify-between border-b border-slate-100 px-6 py-5 bg-white">
                <div>
                    <h2 className="text-base font-semibold text-slate-900">{title}</h2>
                    <p className="mt-1 text-sm text-slate-500">{caption}</p>
                </div>
                <span className="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-1 rounded-md">
                    {lineItems.length} item
                </span>
            </div>

            <div className="overflow-x-auto px-6 py-2">
                <table className="w-full text-left text-sm">
                    <thead>
                        <tr className="border-b border-slate-100 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th className="py-3 pr-4">Transaction</th>
                            <th className="py-3 pr-4">Category</th>
                            <th className="py-3 pr-4">Date</th>
                            <th className="py-3 pr-4">Status</th>
                            <th className="py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        {lineItems.length === 0 && (
                            <tr>
                                <td colSpan={5} className="py-8 text-center text-sm text-slate-500">
                                    Belum ada input line item.
                                </td>
                            </tr>
                        )}

                        {lineItems.map((item) => {
                            const status = resolveStatus(item);
                            const Icon = resolveIcon(item.action_label);

                            return (
                                <tr key={item.id} className="border-b border-slate-100 last:border-0">
                                    <td className="py-3 pr-4">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                                <Icon className="h-4 w-4" />
                                            </div>
                                            <span className="font-medium text-slate-900">{item.description || item.action_label}</span>
                                        </div>
                                    </td>
                                    <td className="py-3 pr-4 text-slate-500">{item.action_label}</td>
                                    <td className="py-3 pr-4 text-slate-500">{formatDate(item.transaction_date)}</td>
                                    <td className="py-3 pr-4">
                                        <span className={`inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-semibold uppercase ${statusBadgeMap[status] || 'bg-slate-100 text-slate-700'}`}>
                                            {resolveStatusLabel(status)}
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
    );
}
