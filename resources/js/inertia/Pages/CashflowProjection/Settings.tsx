import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEvent, useEffect, useMemo } from 'react';
import { ArrowLeft, Banknote, ChevronDown, Save, CheckCircle2 } from 'lucide-react';
import { motion } from 'framer-motion';
import { monthOptions } from './constants';
import type { CashflowProjectionSettingsPageProps, FinanceFormData } from './types';
import { formatCurrency, formatMonthLabel } from './utils';

const inputClasses =
    'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-foreground outline-none transition-colors focus:border-primary focus:ring-1 focus:ring-primary [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none';

const financeFields: Array<{ key: keyof FinanceFormData; label: string; description: string }> = [
    { key: 'cash_on_hand', label: 'Cash On Hand (COH)', description: 'Saldo kas awal bulan yang tersedia' },
    { key: 'receivable_estimate', label: 'Estimasi Penerimaan Piutang', description: 'Perkiraan piutang yang akan masuk bulan ini' },
    { key: 'upcoming_event_revenue_estimate', label: 'Estimasi Revenue Upcoming Event', description: 'Perkiraan pendapatan dari event yang akan datang' },
    { key: 'capital_injection_estimate', label: 'Estimasi Suntikan Modal', description: 'Perkiraan suntikan modal dari pemilik/investor' },
    { key: 'other_income', label: 'Pendapatan Lain-lain', description: 'Pemasukan finance lainnya' },
];

export default function CashflowProjectionSettings({
    year,
    selectedMonth,
    financeInputs,
}: CashflowProjectionSettingsPageProps) {
    const selectedFinanceInput = useMemo(() => {
        return financeInputs.find((input) => input.month === selectedMonth) ?? null;
    }, [financeInputs, selectedMonth]);

    const financeForm = useForm<FinanceFormData>({
        year,
        month: selectedMonth,
        cash_on_hand: selectedFinanceInput?.cash_on_hand ?? 0,
        receivable_estimate: selectedFinanceInput?.receivable_estimate ?? 0,
        upcoming_event_revenue_estimate: selectedFinanceInput?.upcoming_event_revenue_estimate ?? 0,
        capital_injection_estimate: selectedFinanceInput?.capital_injection_estimate ?? 0,
        other_income: selectedFinanceInput?.other_income ?? 0,
    });

    useEffect(() => {
        financeForm.setData('year', year);
        financeForm.setData('month', selectedMonth);
        financeForm.setData('cash_on_hand', selectedFinanceInput?.cash_on_hand ?? 0);
        financeForm.setData('receivable_estimate', selectedFinanceInput?.receivable_estimate ?? 0);
        financeForm.setData('upcoming_event_revenue_estimate', selectedFinanceInput?.upcoming_event_revenue_estimate ?? 0);
        financeForm.setData('capital_injection_estimate', selectedFinanceInput?.capital_injection_estimate ?? 0);
        financeForm.setData('other_income', selectedFinanceInput?.other_income ?? 0);
    }, [year, selectedMonth, selectedFinanceInput]);

    const handleFinanceSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        financeForm.post(route('cashflow-projection.finance-inputs.upsert'), { preserveScroll: true });
    };

    const totalFinanceIncome = useMemo(() => {
        return (
            financeForm.data.receivable_estimate +
            financeForm.data.upcoming_event_revenue_estimate +
            financeForm.data.capital_injection_estimate +
            financeForm.data.other_income
        );
    }, [financeForm.data]);

    return (
        <>
            <Head title="Finance Settings" />

            <div className="w-full px-6 py-8 lg:px-8 2xl:px-10">
                <div className="mx-auto w-full max-w-screen-2xl space-y-8">
                    {/* Page Header */}
                    <div className="space-y-1">
                        <Link
                            href={route('cashflow-projection.index')}
                            className="mb-2 inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-primary"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back to Dashboard
                        </Link>
                        <h1 className="text-[2rem] font-bold text-foreground">Finance Settings</h1>
                        <p className="text-sm text-muted-foreground">
                            {year} &mdash; Set saldo awal (Cash On Hand) dan estimasi pemasukan finance per bulan.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 gap-6 xl:grid-cols-3">
                        {/* Left column — Finance Input Form */}
                        <motion.section
                            className="rounded-2xl border border-border bg-card p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04)]"
                            initial={{ opacity: 0, y: 12 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.25 }}
                        >
                            <div className="mb-5 flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#16599c]/10 text-[#16599c]">
                                    <Banknote className="h-5 w-5" />
                                </div>
                                <div>
                                    <h2 className="text-xl font-semibold text-foreground">Finance Input</h2>
                                    <p className="text-sm text-muted-foreground">Nominal awal & estimasi pemasukan</p>
                                </div>
                            </div>

                            <form onSubmit={handleFinanceSubmit} className="space-y-5">
                                <div>
                                    <label className="mb-1.5 block text-sm font-medium text-slate-700">Bulan</label>
                                    <div className="relative">
                                        <select
                                            className={`${inputClasses} appearance-none pr-8`}
                                            value={financeForm.data.month}
                                            onChange={(e) => financeForm.setData('month', Number(e.target.value))}
                                        >
                                            {monthOptions.map((month) => (
                                                <option key={month.value} value={month.value}>{month.label}</option>
                                            ))}
                                        </select>
                                        <ChevronDown className="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    {financeFields.map(({ key, label, description }) => (
                                        <div key={key}>
                                            <label className="mb-0.5 block text-sm font-medium text-slate-700">{label}</label>
                                            <p className="mb-1.5 text-xs text-muted-foreground">{description}</p>
                                            <input
                                                type="number"
                                                min={0}
                                                className={inputClasses}
                                                value={financeForm.data[key]}
                                                onChange={(e) => financeForm.setData(key, Number(e.target.value))}
                                            />
                                        </div>
                                    ))}
                                </div>

                                {/* Summary preview */}
                                <div className="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-muted-foreground">Cash On Hand</span>
                                        <span className="font-semibold text-foreground">{formatCurrency(financeForm.data.cash_on_hand)}</span>
                                    </div>
                                    <div className="mt-1 flex items-center justify-between text-sm">
                                        <span className="text-muted-foreground">Total Estimasi Pemasukan</span>
                                        <span className="font-semibold text-emerald-600">+{formatCurrency(totalFinanceIncome)}</span>
                                    </div>
                                    <div className="mt-2 border-t border-slate-200 pt-2">
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="font-medium text-foreground">Total Tersedia</span>
                                            <span className="text-base font-bold text-[#16599c]">
                                                {formatCurrency(financeForm.data.cash_on_hand + totalFinanceIncome)}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    disabled={financeForm.processing}
                                    className="flex w-full items-center justify-center gap-2 rounded-lg bg-[#16599c] px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-[#124a82] disabled:opacity-50"
                                >
                                    <Save className="h-4 w-4" />
                                    {financeForm.processing ? 'Saving...' : 'Save Finance Input'}
                                </button>
                            </form>
                        </motion.section>

                        {/* Right column — Saved Finance Inputs Overview */}
                        <motion.section
                            className="rounded-2xl border border-border bg-card p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04)] xl:col-span-2"
                            initial={{ opacity: 0, y: 12 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.25, delay: 0.06 }}
                        >
                            <div className="mb-5">
                                <h2 className="text-xl font-semibold text-foreground">Overview per Bulan</h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {financeInputs.length} dari 12 bulan sudah diisi untuk tahun {year}
                                </p>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead>
                                        <tr className="border-b border-border text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                            <th className="py-3 pr-4">Bulan</th>
                                            <th className="py-3 pr-4 text-right">Cash On Hand</th>
                                            <th className="py-3 pr-4 text-right">Est. Piutang</th>
                                            <th className="py-3 pr-4 text-right">Est. Event</th>
                                            <th className="py-3 pr-4 text-right">Est. Modal</th>
                                            <th className="py-3 pr-4 text-right">Lain-lain</th>
                                            <th className="py-3 text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {financeInputs.length === 0 ? (
                                            <tr>
                                                <td colSpan={7} className="py-12 text-center text-sm text-muted-foreground">
                                                    Belum ada finance input. Gunakan form di samping untuk mengisi data bulan pertama.
                                                </td>
                                            </tr>
                                        ) : (
                                            financeInputs.map((input) => {
                                                const totalIncome =
                                                    input.receivable_estimate +
                                                    input.upcoming_event_revenue_estimate +
                                                    input.capital_injection_estimate +
                                                    input.other_income;
                                                const grandTotal = input.cash_on_hand + totalIncome;

                                                return (
                                                    <tr key={input.id} className="border-b border-border/50 last:border-0">
                                                        <td className="py-3 pr-4">
                                                            <div className="flex items-center gap-2">
                                                                <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                                                                <span className="font-medium text-foreground">
                                                                    {formatMonthLabel(input.month)}
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td className="py-3 pr-4 text-right font-medium text-foreground">
                                                            {formatCurrency(input.cash_on_hand)}
                                                        </td>
                                                        <td className="py-3 pr-4 text-right text-muted-foreground">
                                                            {formatCurrency(input.receivable_estimate)}
                                                        </td>
                                                        <td className="py-3 pr-4 text-right text-muted-foreground">
                                                            {formatCurrency(input.upcoming_event_revenue_estimate)}
                                                        </td>
                                                        <td className="py-3 pr-4 text-right text-muted-foreground">
                                                            {formatCurrency(input.capital_injection_estimate)}
                                                        </td>
                                                        <td className="py-3 pr-4 text-right text-muted-foreground">
                                                            {formatCurrency(input.other_income)}
                                                        </td>
                                                        <td className="py-3 text-right font-semibold text-[#16599c]">
                                                            {formatCurrency(grandTotal)}
                                                        </td>
                                                    </tr>
                                                );
                                            })
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {/* Unfilled months indicator */}
                            {financeInputs.length > 0 && financeInputs.length < 12 && (
                                <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                                    <p className="text-sm text-amber-700">
                                        <span className="font-medium">{12 - financeInputs.length} bulan</span> belum diisi.
                                        Bulan tanpa finance input akan menggunakan nilai 0 pada proyeksi cashflow.
                                    </p>
                                </div>
                            )}
                        </motion.section>
                    </div>
                </div>
            </div>
        </>
    );
}
