import { motion } from 'framer-motion';
import { monthOptions } from '../constants';
import type { FinanceFormData, FinanceInput } from '../types';
import { formatCurrency, formatMonthLabel } from '../utils';

interface FinanceManagementCardProps {
    canManageFinance: boolean;
    financeInputs: FinanceInput[];
    financeData: FinanceFormData;
    financeProcessing: boolean;
    onFieldChange: <K extends keyof FinanceFormData>(field: K, value: FinanceFormData[K]) => void;
    onSubmit: (event: React.FormEvent<HTMLFormElement>) => void;
}

const inputClasses =
    'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-foreground outline-none transition-colors focus:border-primary focus:ring-1 focus:ring-primary [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none';

export default function FinanceManagementCard({
    canManageFinance,
    financeInputs,
    financeData,
    financeProcessing,
    onFieldChange,
    onSubmit,
}: FinanceManagementCardProps) {
    const financeFields: Array<{ key: keyof FinanceFormData; label: string }> = [
        { key: 'cash_on_hand', label: 'Cash On Hand' },
        { key: 'receivable_estimate', label: 'Estimasi Penerimaan Utang' },
        { key: 'upcoming_event_revenue_estimate', label: 'Estimasi Revenue Upcoming Event' },
        { key: 'capital_injection_estimate', label: 'Estimasi Suntikan Modal' },
        { key: 'other_income', label: 'Lain-lain' },
    ];

    return (
        <motion.section
            className="rounded-2xl border border-border bg-card p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04)]"
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.25, ease: 'easeOut', delay: 0.2 }}
        >
            <div className="mb-5">
                <h2 className="text-xl font-semibold text-foreground">Finance Inputs</h2>
                <p className="mt-1 text-sm text-muted-foreground">Cash on hand dan komponen pemasukan finance</p>
            </div>

            {!canManageFinance && (
                <p className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    Hanya Finance/CFC yang dapat menginput Cash On Hand dan komponen pemasukan finance.
                </p>
            )}

            {canManageFinance && (
                <form onSubmit={onSubmit} className="space-y-4">
                    <div>
                        <label className="mb-1.5 block text-sm font-medium text-slate-700">Month</label>
                        <select
                            className={inputClasses}
                            value={financeData.month}
                            onChange={(event) => onFieldChange('month', Number(event.target.value))}
                        >
                            {monthOptions.map((month) => (
                                <option key={month.value} value={month.value}>{month.label}</option>
                            ))}
                        </select>
                    </div>

                    {financeFields.map(({ key, label }) => (
                        <div key={key}>
                            <label className="mb-1.5 block text-sm font-medium text-slate-700">{label}</label>
                            <input
                                type="number"
                                min={0}
                                className={inputClasses}
                                value={financeData[key]}
                                onChange={(event) => onFieldChange(key, Number(event.target.value))}
                            />
                        </div>
                    ))}

                    <button
                        type="submit"
                        disabled={financeProcessing}
                        className="mt-2 flex w-full items-center justify-center gap-2 rounded-lg bg-[#16599c] px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-[#124a82] disabled:opacity-50"
                    >
                        {financeProcessing ? 'Saving...' : 'Save Finance Input'}
                    </button>
                </form>
            )}

            {canManageFinance && financeInputs.length > 0 && (
                <div className="mt-6 border-t border-border pt-5">
                    <h4 className="mb-3 text-sm font-semibold text-foreground">Saved Finance Inputs</h4>
                    <div className="space-y-3">
                        {financeInputs.map((input) => (
                            <div key={input.id} className="rounded-lg border border-border bg-slate-50/60 p-3">
                                <div className="mb-1.5 flex items-center justify-between">
                                    <p className="text-sm font-semibold text-foreground">Month {formatMonthLabel(input.month)}</p>
                                    <span className="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-blue-700">Finance</span>
                                </div>
                                <p className="text-xs text-muted-foreground">COH: {formatCurrency(input.cash_on_hand)}</p>
                                <p className="text-xs text-muted-foreground">
                                    Income: {formatCurrency(
                                        input.receivable_estimate +
                                            input.upcoming_event_revenue_estimate +
                                            input.capital_injection_estimate +
                                            input.other_income
                                    )}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </motion.section>
    );
}
