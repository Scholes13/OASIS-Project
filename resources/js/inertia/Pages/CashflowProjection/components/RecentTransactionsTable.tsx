import { motion } from 'framer-motion';
import { Server, ShoppingBag, Users } from 'lucide-react';
import type { LineItem } from '../types';
import { formatCurrency } from '../utils';

interface RecentTransactionsTableProps {
    lineItems: LineItem[];
}

function resolveStatus(item: LineItem): 'projected' | 'confirmed' | 'pending' {
    if (item.is_estimated_date) return 'pending';
    if (item.flow_type === 'in') return 'confirmed';
    return 'projected';
}

function resolveStatusLabel(status: 'projected' | 'confirmed' | 'pending'): string {
    if (status === 'confirmed') return 'Confirmed';
    if (status === 'pending') return 'Pending';
    return 'Projected';
}

const statusBadgeMap: Record<string, string> = {
    confirmed: 'bg-emerald-100 text-emerald-700',
    pending: 'bg-amber-100 text-amber-700',
    projected: 'bg-blue-100 text-blue-700',
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

export default function RecentTransactionsTable({ lineItems }: RecentTransactionsTableProps) {
    return (
        <motion.section
            className="rounded-2xl border border-border bg-card p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04)]"
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.25, ease: 'easeOut', delay: 0.16 }}
        >
            <div className="mb-4 flex items-center justify-between">
                <h2 className="text-xl font-semibold text-foreground">Upcoming & Recent</h2>
                <span className="text-xs text-muted-foreground">{lineItems.length} item</span>
            </div>

            <div className="overflow-x-auto">
                <table className="w-full text-left text-sm">
                    <thead>
                        <tr className="border-b border-border text-xs font-semibold uppercase tracking-wide text-muted-foreground">
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
                                <td colSpan={5} className="py-8 text-center text-sm text-muted-foreground">
                                    Belum ada input line item.
                                </td>
                            </tr>
                        )}

                        {lineItems.map((item) => {
                            const status = resolveStatus(item);
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
                                    <td className="py-3 pr-4 text-muted-foreground">{formatDate(item.transaction_date)}</td>
                                    <td className="py-3 pr-4">
                                        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase ${statusBadgeMap[status] || 'bg-slate-100 text-slate-700'}`}>
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
