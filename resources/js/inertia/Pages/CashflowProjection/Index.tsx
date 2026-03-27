import { Popover, Transition } from '@headlessui/react';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { ArrowRight, CalendarDays, ChevronDown, Download, RotateCcw, SlidersHorizontal } from 'lucide-react';
import ProjectionChartCard from './components/ProjectionChartCard';
import RecentTransactionsTable from './components/RecentTransactionsTable';
import StatsCards, { type StatsCardItem } from './components/StatsCards';
import './cashflow-dashboard.css';
import { monthOptions } from './constants';
import type {
    CashflowProjectionPageProps,
    DashboardFilterMode,
    DailySummaryRow,
    MonthlySummaryRow,
} from './types';
import { openDownloadInSameTab } from '@/lib/download';
import { formatCurrency, formatMonthLabel } from './utils';

type ViewMode = 'day' | 'week' | 'month';

type ChartRow = {
    key: string;
    label: string;
    inflow: number;
    outflow: number;
    closingBalance?: number;
};

function parseIsoDateParts(dateValue: string): { year: number; month: number; day: number } {
    const [year, month, day] = dateValue.split('-').map(Number);

    return {
        year: Number.isFinite(year) ? year : 0,
        month: Number.isFinite(month) ? month : 0,
        day: Number.isFinite(day) ? day : 0,
    };
}

function formatIsoDate(dateValue: string, options: Intl.DateTimeFormatOptions): string {
    const { year, month, day } = parseIsoDateParts(dateValue);

    if (!year || !month || !day) {
        return dateValue;
    }

    return new Date(year, month - 1, day).toLocaleDateString('id-ID', options);
}

function formatPeriodTitle(mode: DashboardFilterMode, year: number, month: number, startDate: string, endDate: string): string {
    if (mode === 'year') {
        return `FY ${year}`;
    }

    if (mode === 'range') {
        return `${formatIsoDate(startDate, { day: '2-digit', month: 'short' })} - ${formatIsoDate(endDate, { day: '2-digit', month: 'short', year: 'numeric' })}`;
    }

    return `${formatMonthLabel(month)} ${year}`;
}

function formatPeriodCaption(mode: DashboardFilterMode, year: number, month: number, startDate: string, endDate: string): string {
    if (mode === 'year') {
        return `Yearly portfolio view across Jan-Dec ${year}.`;
    }

    if (mode === 'range') {
        return `Custom window from ${formatIsoDate(startDate, { day: '2-digit', month: 'long', year: 'numeric' })} to ${formatIsoDate(endDate, { day: '2-digit', month: 'long', year: 'numeric' })}.`;
    }

    return `${formatMonthLabel(month)} ${year} monthly focus with daily cash movement detail.`;
}

function monthsInScope(startDate: string, endDate: string): number[] {
    const start = parseIsoDateParts(startDate);
    const end = parseIsoDateParts(endDate);

    if (!start.year || !start.month || !end.year || !end.month) {
        return [];
    }

    const months: number[] = [];
    const cursor = new Date(start.year, start.month - 1, 1);
    const last = new Date(end.year, end.month - 1, 1);

    while (cursor <= last) {
        months.push(cursor.getMonth() + 1);
        cursor.setMonth(cursor.getMonth() + 1);
    }

    return months;
}

function formatSignedCurrency(value: number): string {
    if (value === 0) {
        return formatCurrency(0);
    }

    return `${value > 0 ? '+' : '-'}${formatCurrency(Math.abs(value))}`;
}

function buildDailyRows(dailySummary: DailySummaryRow[], monthlySummary: MonthlySummaryRow[], compact: boolean): ChartRow[] {
    let currentBalance = 0;
    let currentMonth = -1;

    return dailySummary.map((row) => {
        const { month } = parseIsoDateParts(row.date);

        if (month !== currentMonth) {
            currentMonth = month;
            const monthData = monthlySummary.find((m) => m.month === month);
            if (monthData) {
                currentBalance = monthData.opening_balance + monthData.finance_income;
            } else {
                currentBalance = 0;
            }
        }

        currentBalance += row.net;

        return {
            key: row.date,
            label: compact
                ? formatIsoDate(row.date, { day: '2-digit', month: 'short' })
                : formatIsoDate(row.date, { day: '2-digit' }),
            inflow: row.plus,
            outflow: row.minus,
            closingBalance: currentBalance,
        };
    });
}

function buildWeeklyRows(dailyRows: ChartRow[]): ChartRow[] {
    const buckets: ChartRow[] = [];

    for (let index = 0; index < dailyRows.length; index += 7) {
        const slice = dailyRows.slice(index, index + 7);

        if (slice.length === 0) {
            continue;
        }

        const first = slice[0];
        const last = slice[slice.length - 1];

        buckets.push({
            key: `${first.key}-${last.key}`,
            label: `${formatIsoDate(first.key, { day: '2-digit', month: 'short' })} - ${formatIsoDate(last.key, { day: '2-digit', month: 'short' })}`,
            inflow: slice.reduce((sum, row) => sum + row.inflow, 0),
            outflow: slice.reduce((sum, row) => sum + row.outflow, 0),
            closingBalance: last.closingBalance,
        });
    }

    return buckets;
}

function buildMonthlyRows(monthlySummary: MonthlySummaryRow[], visibleMonths: number[]): ChartRow[] {
    return monthlySummary
        .filter((row) => visibleMonths.includes(row.month))
        .map((row) => ({
            key: String(row.month),
            label: formatMonthLabel(row.month),
            inflow: row.plus + row.finance_income,
            outflow: row.minus,
            closingBalance: row.closing_balance,
        }));
}

function resolveDefaultViewMode(mode: DashboardFilterMode): ViewMode {
    return mode === 'year' ? 'month' : 'day';
}

const filterModeOptions: Array<{ value: DashboardFilterMode; label: string }> = [
    { value: 'month', label: 'Month' },
    { value: 'year', label: 'Year' },
    { value: 'range', label: 'Date Range' },
];

const inputClasses =
    'h-10 w-full rounded-xl border border-slate-200 bg-white/90 px-3.5 text-sm text-slate-700 outline-none transition focus:border-[#16599c] focus:ring-4 focus:ring-[#16599c]/10';

export default function CashflowProjectionIndex({
    filters,
    summary,
    dailySummary,
    monthlySummary,
    lineItems,
    minimumBalanceGlobal,
    scope,
    linkedBusinessUnits,
}: CashflowProjectionPageProps) {
    const minimumBalanceThreshold = minimumBalanceGlobal > 0 ? minimumBalanceGlobal : 200_000_000;
    const hasLinkedUnits = linkedBusinessUnits && linkedBusinessUnits.length > 0;
    const [viewMode, setViewMode] = useState<ViewMode>(resolveDefaultViewMode(filters.mode));
    const [selectedDayKey, setSelectedDayKey] = useState<string>('all');
    const [draftMode, setDraftMode] = useState<DashboardFilterMode>(filters.mode);
    const [draftYear, setDraftYear] = useState<string>(String(filters.year));
    const [draftMonth, setDraftMonth] = useState<string>(String(filters.month));
    const [draftStartDate, setDraftStartDate] = useState<string>(filters.start_date);
    const [draftEndDate, setDraftEndDate] = useState<string>(filters.end_date);

    useEffect(() => {
        setDraftMode(filters.mode);
        setDraftYear(String(filters.year));
        setDraftMonth(String(filters.month));
        setDraftStartDate(filters.start_date);
        setDraftEndDate(filters.end_date);
        setViewMode(resolveDefaultViewMode(filters.mode));
        setSelectedDayKey('all');
    }, [filters.end_date, filters.mode, filters.month, filters.start_date, filters.year]);

    const periodTitle = useMemo(() => {
        return formatPeriodTitle(filters.mode, filters.year, filters.month, filters.start_date, filters.end_date);
    }, [filters.end_date, filters.mode, filters.month, filters.start_date, filters.year]);

    const periodCaption = useMemo(() => {
        return formatPeriodCaption(filters.mode, filters.year, filters.month, filters.start_date, filters.end_date);
    }, [filters.end_date, filters.mode, filters.month, filters.start_date, filters.year]);

    const draftPeriodTitle = useMemo(() => {
        return formatPeriodTitle(draftMode, Number(draftYear) || filters.year, Number(draftMonth) || filters.month, draftStartDate || filters.start_date, draftEndDate || filters.end_date);
    }, [draftEndDate, draftMode, draftMonth, draftStartDate, draftYear, filters.end_date, filters.month, filters.start_date, filters.year]);

    const visibleMonths = useMemo(() => monthsInScope(filters.start_date, filters.end_date), [filters.end_date, filters.start_date]);

    const dailyRows = useMemo(() => {
        const compact = filters.mode !== 'month';

        return buildDailyRows(dailySummary, monthlySummary, compact);
    }, [dailySummary, monthlySummary, filters.mode]);

    const weeklyRows = useMemo(() => buildWeeklyRows(dailyRows), [dailyRows]);
    const monthlyRows = useMemo(() => buildMonthlyRows(monthlySummary, visibleMonths), [monthlySummary, visibleMonths]);

    const dayPills = useMemo(() => {
        if (dailyRows.length > 45) {
            return [];
        }

        return dailyRows
            .filter((row) => row.inflow > 0 || row.outflow > 0)
            .map((row) => ({
                key: row.key,
                label: formatIsoDate(row.key, { weekday: 'short', day: '2-digit' }),
            }));
    }, [dailyRows]);

    const dailyChartRows = useMemo(() => {
        if (selectedDayKey === 'all') {
            return dailyRows;
        }

        const selectedRow = dailyRows.find((row) => row.key === selectedDayKey);

        return selectedRow ? [selectedRow] : [];
    }, [dailyRows, selectedDayKey]);

    const chartData = useMemo(() => {
        if (viewMode === 'day') {
            return dailyChartRows;
        }

        if (viewMode === 'week') {
            return weeklyRows;
        }

        return monthlyRows;
    }, [dailyChartRows, monthlyRows, viewMode, weeklyRows]);

    const cards = useMemo<StatsCardItem[]>(() => {
        const isBelowMinimumBalance = summary.total_balance < minimumBalanceThreshold;

        return [
            {
                label: 'Balance Snapshot',
                value: formatCurrency(summary.total_balance),
                caption: isBelowMinimumBalance
                    ? `Projected closing balance is below the ${formatCurrency(minimumBalanceThreshold)} minimum threshold for this period.`
                    : 'Projected closing balance at the end of the selected period.',
                tone: isBelowMinimumBalance ? 'negative' : 'positive',
            },
            {
                label: 'Period Inflow',
                value: formatCurrency(summary.inflow),
                caption: 'Operational cash inflow captured from projection entries.',
                tone: summary.inflow > 0 ? 'positive' : 'neutral',
            },
            {
                label: 'Period Outflow',
                value: formatCurrency(summary.outflow),
                caption: 'Committed spend and outgoing cash for the selected window.',
                tone: summary.outflow > summary.inflow ? 'negative' : 'neutral',
            },
            {
                label: 'Net Cashflow',
                value: formatSignedCurrency(summary.net_cashflow),
                caption: `${formatCurrency(summary.finance_income)} finance income included in this net position.`,
                tone: summary.net_cashflow > 0 ? 'positive' : summary.net_cashflow < 0 ? 'negative' : 'neutral',
            },
        ];
    }, [minimumBalanceThreshold, summary.finance_income, summary.inflow, summary.net_cashflow, summary.outflow, summary.total_balance]);

    const recentLineItems = useMemo(() => lineItems.slice(0, 5), [lineItems]);
    const rangeSelectionIncomplete = draftMode === 'range' && (!draftStartDate || !draftEndDate);
    const exportParams = useMemo(() => {
        const params: Record<string, string | number> = {
            filter: filters.mode,
            year: filters.year,
        };

        if (filters.mode === 'month') {
            params.month = filters.month;
        }

        if (filters.mode === 'range') {
            params.start_date = filters.start_date;
            params.end_date = filters.end_date;
        }

        if (hasLinkedUnits) {
            params.scope = scope;
        }

        return params;
    }, [filters.end_date, filters.mode, filters.month, filters.start_date, filters.year, hasLinkedUnits, scope]);

    const applyFilters = (overrideScope?: 'own' | 'consolidated') => {
        const params: Record<string, number | string> = {
            filter: draftMode,
            year: Number(draftYear) || filters.year,
        };

        if (draftMode === 'month') {
            params.month = Number(draftMonth) || filters.month;
        }

        if (draftMode === 'range') {
            if (rangeSelectionIncomplete) {
                return;
            }

            params.start_date = draftStartDate;
            params.end_date = draftEndDate;
        }

        if (hasLinkedUnits) {
            params.scope = overrideScope ?? scope;
        }

        router.get(route('cashflow-projection.index'), params, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const resetToCurrentMonth = () => {
        const now = new Date();

        router.get(route('cashflow-projection.index'), {
            filter: 'month',
            year: now.getFullYear(),
            month: now.getMonth() + 1,
        }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    return (
        <>
            <Head title="Cashflow Projection" />

            <div className="w-full font-sans text-slate-900 pb-12">
                <main className="w-full px-6 py-6 lg:px-8">
                    <div className="mx-auto w-full max-w-screen-2xl space-y-6">
                    <section className="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                        <div className="max-w-3xl space-y-1.5">
                            <p className="text-sm font-semibold text-slate-700">Finance</p>
                            <h1 className="text-2xl font-bold tracking-tight text-slate-900">Cashflow Projection</h1>
                            <p className="max-w-2xl text-sm text-slate-500">
                                {periodTitle} - {periodCaption}
                            </p>
                            <div className="mt-1 flex flex-wrap items-center gap-2">
                                <span className="inline-flex items-center rounded-md bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 border border-slate-200 shadow-sm">
                                    {lineItems.length} filtered entries
                                </span>
                                <span className="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700 border border-emerald-100 shadow-sm">
                                    Finance income {formatCurrency(summary.finance_income)}
                                </span>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            <Popover className="relative">
                                {({ close }) => (
                                    <>
                                        <Popover.Button className="flex items-center justify-center gap-2 rounded-lg bg-white border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                                            <span>{periodTitle}</span>
                                            <ChevronDown className="h-3.5 w-3.5 text-slate-400" />
                                        </Popover.Button>

                                        <Transition
                                            enter="transition duration-150 ease-out"
                                            enterFrom="translate-y-1 opacity-0"
                                            enterTo="translate-y-0 opacity-100"
                                            leave="transition duration-100 ease-in"
                                            leaveFrom="translate-y-0 opacity-100"
                                            leaveTo="translate-y-1 opacity-0"
                                        >
                                            <Popover.Panel className="absolute right-0 z-20 mt-2.5 w-[min(92vw,360px)] rounded-xl border border-slate-200 bg-white p-4 shadow-xl">
                                                <div className="space-y-4">
                                                    <div className="space-y-1">
                                                        <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Filter Period</p>
                                                        <h2 className="text-base font-semibold text-slate-950">Focus the dashboard</h2>
                                                        <p className="text-[13px] leading-5 text-slate-500">Pilih periode utama, lalu seluruh stats, chart, dan transaksi akan ikut sinkron.</p>
                                                    </div>

                                                    <div>
                                                        <label className="mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                                            <SlidersHorizontal className="h-3 w-3" />
                                                            View
                                                        </label>
                                                        <div className="grid grid-cols-3 rounded-lg bg-slate-100/80 p-1 border border-slate-200/50">
                                                            {filterModeOptions.map((option) => (
                                                                <button
                                                                    key={option.value}
                                                                    type="button"
                                                                    onClick={() => setDraftMode(option.value)}
                                                                    className={`rounded-md px-2.5 py-1.5 text-sm font-medium transition-all duration-200 ${
                                                                        draftMode === option.value
                                                                            ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-900/5'
                                                                            : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'
                                                                    }`}
                                                                >
                                                                    {option.label}
                                                                </button>
                                                            ))}
                                                        </div>
                                                    </div>

                                                    <div className="grid gap-3.5">
                                                        <div>
                                                            <label htmlFor="cashflow-dashboard-year" className="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                                                Year
                                                            </label>
                                                            <select
                                                                id="cashflow-dashboard-year"
                                                                className={inputClasses}
                                                                value={draftYear}
                                                                onChange={(event) => setDraftYear(event.target.value)}
                                                            >
                                                                {filters.available_years.map((availableYear) => (
                                                                    <option key={availableYear} value={availableYear}>
                                                                        {availableYear}
                                                                    </option>
                                                                ))}
                                                            </select>
                                                        </div>

                                                        {draftMode === 'range' ? (
                                                            <div className="grid gap-3 sm:grid-cols-2">
                                                                <div>
                                                                    <label htmlFor="cashflow-dashboard-start-date" className="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                                                        Start Date
                                                                    </label>
                                                                    <input
                                                                        id="cashflow-dashboard-start-date"
                                                                        type="date"
                                                                        className={inputClasses}
                                                                        value={draftStartDate}
                                                                        onChange={(event) => setDraftStartDate(event.target.value)}
                                                                    />
                                                                </div>
                                                                <div>
                                                                    <label htmlFor="cashflow-dashboard-end-date" className="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                                                        End Date
                                                                    </label>
                                                                    <input
                                                                        id="cashflow-dashboard-end-date"
                                                                        type="date"
                                                                        className={inputClasses}
                                                                        value={draftEndDate}
                                                                        onChange={(event) => setDraftEndDate(event.target.value)}
                                                                    />
                                                                </div>
                                                            </div>
                                                        ) : (
                                                            <div>
                                                                <label htmlFor="cashflow-dashboard-month" className="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                                                    Month
                                                                </label>
                                                                <select
                                                                    id="cashflow-dashboard-month"
                                                                    className={inputClasses}
                                                                    value={draftMonth}
                                                                    onChange={(event) => setDraftMonth(event.target.value)}
                                                                    disabled={draftMode === 'year'}
                                                                >
                                                                    {monthOptions.map((monthOption) => (
                                                                        <option key={monthOption.value} value={monthOption.value}>
                                                                            {monthOption.label}
                                                                        </option>
                                                                    ))}
                                                                </select>
                                                            </div>
                                                        )}
                                                    </div>

                                                    <div className="rounded-lg bg-slate-50 px-3.5 py-3 border border-slate-200/60">
                                                        <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Preview</p>
                                                        <p className="mt-1 text-[13px] font-semibold text-slate-900">{draftPeriodTitle}</p>
                                                        <p className="mt-1 text-[12px] leading-5 text-slate-600">
                                                            {draftMode === 'range'
                                                                ? `${formatIsoDate(draftStartDate || filters.start_date, { day: '2-digit', month: 'short', year: 'numeric' })} - ${formatIsoDate(draftEndDate || filters.end_date, { day: '2-digit', month: 'short', year: 'numeric' })}`
                                                                : 'Seluruh dashboard akan ikut memakai periode ini setelah diterapkan.'}
                                                        </p>
                                                    </div>

                                                    <div className="flex items-center justify-between gap-3">
                                                        <button
                                                            type="button"
                                                            onClick={resetToCurrentMonth}
                                                            className="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                                                        >
                                                            <RotateCcw className="h-3.5 w-3.5" />
                                                            Reset
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => {
                                                                applyFilters();
                                                                close();
                                                            }}
                                                            disabled={rangeSelectionIncomplete}
                                                            className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-600 disabled:cursor-not-allowed disabled:opacity-50"
                                                        >
                                                            <CalendarDays className="h-3.5 w-3.5" />
                                                            Apply period
                                                        </button>
                                                    </div>
                                                </div>
                                            </Popover.Panel>
                                        </Transition>
                                    </>
                                )}
                            </Popover>

                            {hasLinkedUnits && (
                                <div className="flex items-center rounded-lg border border-slate-200 bg-white shadow-sm">
                                    <button
                                        type="button"
                                        onClick={() => applyFilters('own')}
                                        className={`rounded-l-lg px-3 py-2 text-sm font-medium transition ${
                                            scope === 'own'
                                                ? 'bg-primary text-white'
                                                : 'text-slate-600 hover:bg-slate-50'
                                        }`}
                                    >
                                        BU Only
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => applyFilters('consolidated')}
                                        className={`rounded-r-lg px-3 py-2 text-sm font-medium transition ${
                                            scope === 'consolidated'
                                                ? 'bg-primary text-white'
                                                : 'text-slate-600 hover:bg-slate-50'
                                        }`}
                                    >
                                        Consolidated
                                    </button>
                                </div>
                            )}

                            <button
                                type="button"
                                onClick={() => openDownloadInSameTab(route('cashflow-projection.export', exportParams))}
                                className="flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                            >
                                <Download className="mr-2 h-4 w-4" />
                                <span>Export Excel</span>
                            </button>

                            <Link
                                href={route('cashflow-projection.entries', { year: filters.year, month: filters.month })}
                                className="flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-600"
                            >
                                <span>Add Entry</span>
                                <ArrowRight className="ml-2 h-4 w-4" />
                            </Link>
                        </div>
                    </section>

                    <StatsCards cards={cards} />

                    <ProjectionChartCard
                        title="Cashflow Projection"
                        subtitle={`Granular trend for ${periodTitle}. Switch between day, week, or month to inspect movement density.`}
                        chartData={chartData}
                        viewMode={viewMode}
                        onViewModeChange={setViewMode}
                        dayPills={dayPills}
                        selectedDayKey={selectedDayKey}
                        onDayFilterChange={setSelectedDayKey}
                        minimumBalanceThreshold={minimumBalanceThreshold}
                    />

                    <div>
                        <RecentTransactionsTable
                            lineItems={recentLineItems}
                            title="Selected Transactions"
                            caption="Most recent entries that match the active dashboard filter."
                        />
                        {lineItems.length > 5 && (
                            <div className="mt-3 flex justify-end">
                                <Link
                                    href={route('cashflow-projection.entries', { year: filters.year, month: filters.month })}
                                    className="inline-flex items-center gap-1.5 text-sm font-medium text-primary transition-colors hover:text-primary/80"
                                >
                                    View all {lineItems.length} entries
                                    <ArrowRight className="h-3.5 w-3.5" />
                                </Link>
                            </div>
                        )}
                    </div>
                </div>
                </main>
            </div>
        </>
    );
}
