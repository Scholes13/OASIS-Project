import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { format, startOfMonth, startOfYear, subDays } from 'date-fns';
import { FileSpreadsheet, FileText } from 'lucide-react';
import { TicketDashboardDateFilter, type DashboardPeriodPreset } from '@/components/Ticket/dashboard/TicketDashboardDateFilter';
import ReportingCharts from '@/components/Ticket/reporting/ReportingCharts';
import ReportingMetricCards from '@/components/Ticket/reporting/ReportingMetricCards';
import ReportingSlaCompliance from '@/components/Ticket/reporting/ReportingSlaCompliance';
import type { ReportData } from '@/components/Ticket/reporting/types';
import type { PageProps } from '@/types';

interface ReportingProps extends PageProps {
    reportData: ReportData;
    filters: { date_from: string; date_to: string };
}

const periodPresets: DashboardPeriodPreset[] = [
    {
        label: 'Today',
        getRange: () => ({
            from: format(new Date(), 'yyyy-MM-dd'),
            to: format(new Date(), 'yyyy-MM-dd'),
        }),
    },
    {
        label: 'This Week',
        getRange: () => ({
            from: format(subDays(new Date(), 7), 'yyyy-MM-dd'),
            to: format(new Date(), 'yyyy-MM-dd'),
        }),
    },
    {
        label: 'This Month',
        getRange: () => ({
            from: format(startOfMonth(new Date()), 'yyyy-MM-dd'),
            to: format(new Date(), 'yyyy-MM-dd'),
        }),
    },
    {
        label: '30 Days',
        getRange: () => ({
            from: format(subDays(new Date(), 30), 'yyyy-MM-dd'),
            to: format(new Date(), 'yyyy-MM-dd'),
        }),
    },
    {
        label: '90 Days',
        getRange: () => ({
            from: format(subDays(new Date(), 90), 'yyyy-MM-dd'),
            to: format(new Date(), 'yyyy-MM-dd'),
        }),
    },
    {
        label: 'This Year',
        getRange: () => ({
            from: format(startOfYear(new Date()), 'yyyy-MM-dd'),
            to: format(new Date(), 'yyyy-MM-dd'),
        }),
    },
    {
        label: 'All Data',
        getRange: () => ({
            from: '1000-01-01',
            to: format(new Date(), 'yyyy-MM-dd'),
        }),
    },
];

export default function TicketReporting({ reportData, filters }: ReportingProps) {
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);
    const [isFiltering, setIsFiltering] = useState(false);

    const visitRange = (from: string, to: string) => {
        setIsFiltering(true);
        router.get(route('it-support.admin.reporting'), {
            date_from: from,
            date_to: to,
        }, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setIsFiltering(false),
        });
    };

    const applyFilters = (from = dateFrom, to = dateTo) => {
        setDateFrom(from);
        setDateTo(to);
        visitRange(from, to);
    };

    const handlePreset = (preset: DashboardPeriodPreset) => {
        const range = preset.getRange();
        setDateFrom(range.from);
        setDateTo(range.to);
        visitRange(range.from, range.to);
    };

    const exportUrl = (format: 'excel' | 'pdf') => {
        const routeName = format === 'excel' ? 'it-support.admin.reporting.exportExcel' : 'it-support.admin.reporting.exportPdf';

        return route(routeName) + `?date_from=${dateFrom}&date_to=${dateTo}`;
    };

    return (
        <>
            <Head title="IT Support Reporting" />

            <div className="w-full space-y-5 bg-gray-50/60 px-6 py-6 lg:px-8">
                <div className="flex flex-col gap-4 2xl:flex-row 2xl:items-end 2xl:justify-between">
                    <div className="flex flex-col gap-1.5">
                        <h1 className="text-2xl font-semibold tracking-tight text-gray-900">IT Support Reporting</h1>
                        <p className="text-sm text-gray-500">Analyze ticket flow, SLA 2 × 24h compliance, and team workload.</p>
                    </div>

                    <div className="flex w-full flex-col gap-2 xl:flex-row xl:items-center 2xl:w-auto">
                        <TicketDashboardDateFilter
                            presets={periodPresets}
                            dateFrom={dateFrom}
                            dateTo={dateTo}
                            isFiltering={isFiltering}
                            onPresetSelect={handlePreset}
                            onApply={applyFilters}
                        />

                        <div className="flex shrink-0 items-center gap-1 rounded-xl border border-gray-200 bg-white p-1.5 shadow-sm">
                            <a
                                href={exportUrl('excel')}
                                className="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-lg px-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 xl:flex-none"
                            >
                                <FileSpreadsheet className="h-4 w-4 text-emerald-600" />
                                Excel
                            </a>
                            <div className="h-7 w-px bg-gray-200" />
                            <a
                                href={exportUrl('pdf')}
                                className="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-lg px-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 xl:flex-none"
                            >
                                <FileText className="h-4 w-4 text-red-500" />
                                PDF
                            </a>
                        </div>
                    </div>
                </div>

                <ReportingMetricCards reportData={reportData} />

                {reportData.sla_compliance && (
                    <ReportingSlaCompliance sla={reportData.sla_compliance} />
                )}

                <ReportingCharts reportData={reportData} />

                {isFiltering && <span className="sr-only">Filtering report data</span>}
            </div>
        </>
    );
}
