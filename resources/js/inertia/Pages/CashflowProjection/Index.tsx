import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { ArrowRight } from 'lucide-react';
import ProjectionChartCard from './components/ProjectionChartCard';
import RecentTransactionsTable from './components/RecentTransactionsTable';
import StatsCards from './components/StatsCards';
import './cashflow-dashboard.css';
import type { CashflowProjectionPageProps, DailySummaryRow } from './types';
import { formatCurrency, formatMonthLabel } from './utils';

type ViewMode = 'day' | 'week' | 'month';

function buildTrend(current: number, previous: number | null, positiveIsGood = true): { label: string; tone: 'up' | 'down' | 'neutral' } {
    if (!previous || previous === 0) {
        return { label: '0% vs baseline', tone: 'neutral' };
    }

    const deltaPct = ((current - previous) / Math.abs(previous)) * 100;
    const display = `${deltaPct >= 0 ? '+' : ''}${deltaPct.toFixed(1)}%`;

    if (deltaPct === 0) {
        return { label: `${display} no change`, tone: 'neutral' };
    }

    const improvement = positiveIsGood ? deltaPct > 0 : deltaPct < 0;
    return {
        label: improvement ? `${display} improved` : `${display} lower spend`,
        tone: improvement ? 'up' : 'down',
    };
}

function toIsoDate(year: number, month: number, day: number): string {
    const safeMonth = String(Math.max(1, Math.min(12, month))).padStart(2, '0');
    const safeDay = String(Math.max(1, day)).padStart(2, '0');
    return `${year}-${safeMonth}-${safeDay}`;
}

function parseIsoDateParts(dateValue: string): { year: number; month: number; day: number } {
    const [year, month, day] = dateValue.split('-').map(Number);
    return {
        year: Number.isFinite(year) ? year : 0,
        month: Number.isFinite(month) ? month : 0,
        day: Number.isFinite(day) ? day : 0,
    };
}

function formatDayPillLabel(dateValue: string): string {
    const { year, month, day } = parseIsoDateParts(dateValue);
    if (!year || !month || !day) return dateValue;

    const date = new Date(year, month - 1, day);
    const dayName = date.toLocaleDateString('en-US', { weekday: 'short' });
    const dayNum = String(day).padStart(2, '0');
    return `${dayName} ${dayNum}`;
}

export default function CashflowProjectionIndex({
    year,
    selectedMonth,
    monthlySummary,
    dailySummary,
    lineItems,
}: CashflowProjectionPageProps) {
    const [viewMode, setViewMode] = useState<ViewMode>('day');
    const [selectedDayKey, setSelectedDayKey] = useState<string>('all');

    const daysInSelectedMonth = useMemo(() => new Date(year, selectedMonth, 0).getDate(), [year, selectedMonth]);

    const selectedMonthProjection = useMemo(() => {
        return monthlySummary.find((row) => row.month === selectedMonth) ?? null;
    }, [monthlySummary, selectedMonth]);

    const previousMonthProjection = useMemo(() => {
        return monthlySummary.find((row) => row.month === selectedMonth - 1) ?? null;
    }, [monthlySummary, selectedMonth]);

    const dailyRowsForMonth = useMemo(() => {
        const byDay = new Map<number, DailySummaryRow>();
        dailySummary.forEach((row) => {
            const { day } = parseIsoDateParts(row.date);
            if (day < 1 || day > daysInSelectedMonth) return;
            byDay.set(day, row);
        });

        return Array.from({ length: daysInSelectedMonth }, (_, index) => {
            const day = index + 1;
            const fallbackDate = toIsoDate(year, selectedMonth, day);
            const row = byDay.get(day);
            return {
                key: row?.date ?? fallbackDate,
                label: String(day).padStart(2, '0'),
                inflow: row?.plus ?? 0,
                outflow: row?.minus ?? 0,
            };
        });
    }, [dailySummary, daysInSelectedMonth, year, selectedMonth]);

    const dailyInflowTotal = useMemo(() => dailyRowsForMonth.reduce((sum, row) => sum + row.inflow, 0), [dailyRowsForMonth]);
    const dailyOutflowTotal = useMemo(() => dailyRowsForMonth.reduce((sum, row) => sum + row.outflow, 0), [dailyRowsForMonth]);
    const dailyNetTotal = dailyInflowTotal - dailyOutflowTotal;

    const totalBalanceTrend = buildTrend(selectedMonthProjection?.closing_balance ?? 0, previousMonthProjection?.closing_balance ?? null, true);
    const inflowTrend = buildTrend(selectedMonthProjection?.plus ?? 0, previousMonthProjection?.plus ?? null, true);
    const outflowTrend = buildTrend(selectedMonthProjection?.minus ?? 0, previousMonthProjection?.minus ?? null, false);

    const netTrendLabel = useMemo(() => {
        if (dailyNetTotal === 0) return 'Stable daily trend';
        return dailyNetTotal > 0 ? 'Positive daily trend' : 'Negative daily trend';
    }, [dailyNetTotal]);

    const dayPills = useMemo(() => {
        return dailySummary.map((row) => ({
            key: row.date,
            label: formatDayPillLabel(row.date),
        }));
    }, [dailySummary]);

    const monthlyChartRows = useMemo(() => {
        return monthlySummary.map((row) => ({
            label: formatMonthLabel(row.month),
            inflow: row.plus + row.finance_income,
            outflow: row.minus,
        }));
    }, [monthlySummary]);

    const weeklyChartRows = useMemo(() => {
        const weekCount = Math.ceil(daysInSelectedMonth / 7);
        const buckets = Array.from({ length: weekCount }, (_, index) => ({
            label: `W${index + 1}`,
            inflow: 0,
            outflow: 0,
        }));

        dailyRowsForMonth.forEach((row, index) => {
            const bucketIndex = Math.floor(index / 7);
            buckets[bucketIndex].inflow += row.inflow;
            buckets[bucketIndex].outflow += row.outflow;
        });

        return buckets;
    }, [dailyRowsForMonth, daysInSelectedMonth]);

    const dailyChartRows = useMemo(() => {
        if (selectedDayKey === 'all') {
            return dailyRowsForMonth.map((row) => ({
                label: row.label,
                inflow: row.inflow,
                outflow: row.outflow,
            }));
        }

        const selectedRow = dailyRowsForMonth.find((row) => row.key === selectedDayKey);
        if (!selectedRow) return [];

        return [{ label: selectedRow.label, inflow: selectedRow.inflow, outflow: selectedRow.outflow }];
    }, [dailyRowsForMonth, selectedDayKey]);

    const chartData = useMemo(() => {
        if (viewMode === 'day') return dailyChartRows;
        if (viewMode === 'week') return weeklyChartRows;
        return monthlyChartRows;
    }, [viewMode, dailyChartRows, weeklyChartRows, monthlyChartRows]);

    const chartMaxValue = useMemo(() => {
        const maxValue = chartData.reduce((max, row) => Math.max(max, row.inflow, row.outflow), 0);
        return Math.max(maxValue, 1);
    }, [chartData]);

    const recentLineItems = useMemo(() => lineItems.slice(0, 5), [lineItems]);

    return (
        <>
            <Head title="Cashflow Projection" />

            <div className="w-full px-6 py-8 lg:px-8 2xl:px-10">
                <div className="mx-auto w-full max-w-screen-2xl space-y-8">
                    <div className="flex items-start justify-between">
                        <div className="space-y-1">
                            <p className="text-sm font-semibold text-slate-700">Finance</p>
                            <h1 className="text-[2rem] font-bold text-foreground">Cashflow Projection</h1>
                            <p className="text-sm text-muted-foreground">
                                {formatMonthLabel(selectedMonth)} {year} &mdash; Monitor inflow, outflow, and projected balances.
                            </p>
                        </div>
                        <Link
                            href={route('cashflow-projection.entries')}
                            className="mt-2 inline-flex items-center gap-2 rounded-lg bg-[#16599c] px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-[#124a82]"
                        >
                            <span>Add Entry</span>
                            <ArrowRight className="h-4 w-4" />
                        </Link>
                    </div>

                    <StatsCards
                        totalBalanceLabel={formatCurrency(selectedMonthProjection?.closing_balance ?? 0)}
                        dailyInflowLabel={formatCurrency(dailyInflowTotal)}
                        dailyOutflowLabel={formatCurrency(dailyOutflowTotal)}
                        netCashflowLabel={`${dailyNetTotal >= 0 ? '+' : ''}${formatCurrency(dailyNetTotal)}`}
                        totalTrendLabel={`${totalBalanceTrend.label} vs last month`}
                        totalTrendTone={totalBalanceTrend.tone}
                        inflowTrendLabel={`${inflowTrend.label} vs average`}
                        inflowTrendTone={inflowTrend.tone}
                        outflowTrendLabel={`${outflowTrend.label} lower spend`}
                        outflowTrendTone={outflowTrend.tone}
                        netTrendLabel={netTrendLabel}
                    />

                    <ProjectionChartCard
                        chartData={chartData}
                        chartMaxValue={chartMaxValue}
                        viewMode={viewMode}
                        onViewModeChange={setViewMode}
                        dayPills={dayPills}
                        selectedDayKey={selectedDayKey}
                        onDayFilterChange={setSelectedDayKey}
                    />

                    <div>
                        <RecentTransactionsTable lineItems={recentLineItems} />
                        {lineItems.length > 5 && (
                            <div className="mt-3 flex justify-end">
                                <Link
                                    href={route('cashflow-projection.entries')}
                                    className="inline-flex items-center gap-1.5 text-sm font-medium text-primary transition-colors hover:text-primary/80"
                                >
                                    View all {lineItems.length} entries
                                    <ArrowRight className="h-3.5 w-3.5" />
                                </Link>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
