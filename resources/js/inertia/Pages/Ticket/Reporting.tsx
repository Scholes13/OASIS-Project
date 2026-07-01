import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { format, startOfMonth, subDays } from 'date-fns';
import { FileText } from 'lucide-react';
import { Button } from '@/components/ui/button';
import ReportingCharts from '@/components/Ticket/reporting/ReportingCharts';
import ReportingFilters from '@/components/Ticket/reporting/ReportingFilters';
import ReportingMetricCards from '@/components/Ticket/reporting/ReportingMetricCards';
import ReportingSlaCompliance from '@/components/Ticket/reporting/ReportingSlaCompliance';
import type { ReportData } from '@/components/Ticket/reporting/types';
import type { PageProps } from '@/types';

interface ReportingProps extends PageProps {
    reportData: ReportData;
    filters: { date_from: string; date_to: string };
}

const periodPresets = [
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
];

export default function TicketReporting({ reportData, filters }: ReportingProps) {
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);
    const [isFiltering, setIsFiltering] = useState(false);

    const applyFilters = () => {
        setIsFiltering(true);
        router.get(route('it-support.admin.reporting'), {
            date_from: dateFrom,
            date_to: dateTo,
        }, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setIsFiltering(false),
        });
    };

    const handlePreset = (preset: typeof periodPresets[number]) => {
        const range = preset.getRange();
        setDateFrom(range.from);
        setDateTo(range.to);
    };

    const handleExport = (format: 'excel' | 'pdf') => {
        const routeName = format === 'excel' ? 'it-support.admin.reporting.exportExcel' : 'it-support.admin.reporting.exportPdf';
        const url = route(routeName) + `?date_from=${dateFrom}&date_to=${dateTo}`;
        window.location.href = url;
    };

    return (
        <>
            <Head title="IT Support Reporting" />

            <div className="w-full space-y-5 bg-gray-50/60 px-6 py-6 lg:px-8">
                <div className="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div className="flex flex-col gap-1.5">
                        <h1 className="text-2xl font-semibold tracking-tight text-gray-900">IT Support Reporting</h1>
                        <p className="text-sm text-gray-500">Analyze ticket flow, SLA 2 × 24h compliance, and team workload.</p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleExport('excel')}
                        >
                            <FileText className="w-4 h-4 mr-2" />
                            Export Excel
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleExport('pdf')}
                        >
                            <FileText className="w-4 h-4 mr-2" />
                            Export PDF
                        </Button>
                    </div>
                </div>

                <ReportingFilters
                    dateFrom={dateFrom}
                    dateTo={dateTo}
                    periodPresets={periodPresets}
                    onDateFromChange={setDateFrom}
                    onDateToChange={setDateTo}
                    onPreset={handlePreset}
                    onApply={applyFilters}
                />

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
