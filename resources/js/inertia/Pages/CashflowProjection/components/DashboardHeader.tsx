import { Popover, Transition } from '@headlessui/react';
import { Link } from '@inertiajs/react';
import { ArrowRight, CalendarDays, ChevronDown, Download, RotateCcw, SlidersHorizontal } from 'lucide-react';
import { monthOptions } from '../constants';
import type { DashboardFilterMode } from '../types';
import { formatCurrency } from '../utils';
import { openDownloadInSameTab } from '@/lib/download';

interface DashboardHeaderProps {
    periodTitle: string;
    periodCaption: string;
    filteredEntryCount: number;
    financeIncome: number;
    draftMode: DashboardFilterMode;
    draftYear: string;
    draftMonth: string;
    draftStartDate: string;
    draftEndDate: string;
    draftPeriodTitle: string;
    availableYears: number[];
    filtersYear: number;
    filtersMonth: number;
    filtersStartDate: string;
    filtersEndDate: string;
    rangeSelectionIncomplete: boolean;
    hasLinkedUnits: boolean;
    scope: 'own' | 'consolidated';
    exportParams: Record<string, string | number>;
    formatIsoDate: (dateValue: string, options: Intl.DateTimeFormatOptions) => string;
    onDraftModeChange: (mode: DashboardFilterMode) => void;
    onDraftYearChange: (year: string) => void;
    onDraftMonthChange: (month: string) => void;
    onDraftStartDateChange: (date: string) => void;
    onDraftEndDateChange: (date: string) => void;
    onApplyFilters: (scope?: 'own' | 'consolidated') => void;
    onResetToCurrentMonth: () => void;
}

const filterModeOptions: Array<{ value: DashboardFilterMode; label: string }> = [
    { value: 'month', label: 'Month' },
    { value: 'year', label: 'Year' },
    { value: 'range', label: 'Date Range' },
];

const inputClasses =
    'h-10 w-full rounded-xl border border-slate-200 bg-white/90 px-3.5 text-sm text-slate-700 outline-none transition focus:border-[#16599c] focus:ring-4 focus:ring-[#16599c]/10';

export default function DashboardHeader({
    periodTitle,
    periodCaption,
    filteredEntryCount,
    financeIncome,
    draftMode,
    draftYear,
    draftMonth,
    draftStartDate,
    draftEndDate,
    draftPeriodTitle,
    availableYears,
    filtersYear,
    filtersMonth,
    filtersStartDate,
    filtersEndDate,
    rangeSelectionIncomplete,
    hasLinkedUnits,
    scope,
    exportParams,
    formatIsoDate,
    onDraftModeChange,
    onDraftYearChange,
    onDraftMonthChange,
    onDraftStartDateChange,
    onDraftEndDateChange,
    onApplyFilters,
    onResetToCurrentMonth,
}: DashboardHeaderProps) {
    return (
        <section className="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div className="max-w-3xl space-y-1.5">
                <p className="text-sm font-semibold text-slate-700">Finance</p>
                <h1 className="text-2xl font-bold tracking-tight text-slate-900">Cashflow Projection</h1>
                <p className="max-w-2xl text-sm text-slate-500">
                    {periodTitle} - {periodCaption}
                </p>
                <div className="mt-1 flex flex-wrap items-center gap-2">
                    <span className="inline-flex items-center rounded-md bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 border border-slate-200 shadow-sm">
                        {filteredEntryCount} filtered entries
                    </span>
                    <span className="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700 border border-emerald-100 shadow-sm">
                        Finance income {formatCurrency(financeIncome)}
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
                                                        onClick={() => onDraftModeChange(option.value)}
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
                                                    onChange={(event) => onDraftYearChange(event.target.value)}
                                                >
                                                    {availableYears.map((availableYear) => (
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
                                                            onChange={(event) => onDraftStartDateChange(event.target.value)}
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
                                                            onChange={(event) => onDraftEndDateChange(event.target.value)}
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
                                                        onChange={(event) => onDraftMonthChange(event.target.value)}
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
                                                    ? `${formatIsoDate(draftStartDate || filtersStartDate, { day: '2-digit', month: 'short', year: 'numeric' })} - ${formatIsoDate(draftEndDate || filtersEndDate, { day: '2-digit', month: 'short', year: 'numeric' })}`
                                                    : 'Seluruh dashboard akan ikut memakai periode ini setelah diterapkan.'}
                                            </p>
                                        </div>

                                        <div className="flex items-center justify-between gap-3">
                                            <button
                                                type="button"
                                                onClick={onResetToCurrentMonth}
                                                className="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                                            >
                                                <RotateCcw className="h-3.5 w-3.5" />
                                                Reset
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    onApplyFilters();
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
                            onClick={() => onApplyFilters('own')}
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
                            onClick={() => onApplyFilters('consolidated')}
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
                    href={route('cashflow-projection.entries', { year: filtersYear, month: filtersMonth })}
                    className="flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-600"
                >
                    <span>Add Entry</span>
                    <ArrowRight className="ml-2 h-4 w-4" />
                </Link>
            </div>
        </section>
    );
}
