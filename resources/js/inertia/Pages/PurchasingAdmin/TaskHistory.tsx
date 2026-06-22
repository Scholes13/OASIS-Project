import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Download, RotateCcw } from 'lucide-react';
import { HistoryFilters } from '@/components/purchasing-admin/history/HistoryFilters';
import { HistoryPagination } from '@/components/purchasing-admin/history/HistoryPagination';
import { HistoryStatsCards } from '@/components/purchasing-admin/history/HistoryStatsCards';
import { HistoryTable } from '@/components/purchasing-admin/history/HistoryTable';
import type { HistoryFiltersState, HistoryStats } from '@/components/purchasing-admin/history/historyUtils';
import type { AdminTask } from '@/components/purchasing-admin/types';
import type { PageProps } from '@/types';

interface TaskHistoryProps extends PageProps {
    tasks: {
        data: AdminTask[];
        links: unknown[];
        current_page: number;
        last_page: number;
        total: number;
        from: number;
        to: number;
    };
    statistics: HistoryStats;
    filters: HistoryFiltersState;
}

export default function TaskHistory({ tasks, statistics, filters }: TaskHistoryProps) {
    const [localFilters, setLocalFilters] = useState(filters);
    const [isExporting, setIsExporting] = useState(false);

    const resetFilters = () => {
        const reset = { date_from: '', date_to: '', status: 'all', type: 'all' };
        setLocalFilters(reset);
        router.get(route('purchasing.admin.task-history'), reset, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleFilterChange = (key: keyof HistoryFiltersState, value: string) => {
        const newFilters = { ...localFilters, [key]: value };
        setLocalFilters(newFilters);
        router.get(route('purchasing.admin.task-history'), newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const goToPage = (page: number) => {
        router.get(route('purchasing.admin.task-history'), { ...localFilters, page }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleExport = (format: 'excel' | 'csv') => {
        setIsExporting(true);
        window.location.href = route('purchasing.admin.task-history.export', { format, ...localFilters });
        setTimeout(() => setIsExporting(false), 2000);
    };

    return (
        <>
            <Head title="My Task History" />
            <div className="py-6">
                <div className="w-full px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link
                            href={route('purchasing.admin.tasks')}
                            className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4"
                        >
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Back to Tasks
                        </Link>
                        <h1 className="text-2xl font-bold text-gray-900">My Task History</h1>
                        <p className="text-gray-500 mt-1">All tasks you have handled</p>
                    </div>

                    <HistoryStatsCards statistics={statistics} />

                    <div className="bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm">
                        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h3 className="text-base font-semibold text-gray-900">Task History</h3>
                            <div className="flex items-center gap-2">
                                <div className="relative group">
                                    <button
                                        disabled={isExporting}
                                        className="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors border border-gray-200 disabled:opacity-50"
                                    >
                                        <Download className="w-4 h-4 mr-1.5" />
                                        Export
                                    </button>
                                    <div className="absolute right-0 top-full mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50 hidden group-hover:block">
                                        <button
                                            onClick={() => handleExport('excel')}
                                            className="w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 text-left"
                                        >
                                            Export to Excel
                                        </button>
                                        <button
                                            onClick={() => handleExport('csv')}
                                            className="w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 text-left"
                                        >
                                            Export to CSV
                                        </button>
                                    </div>
                                </div>
                                <button
                                    onClick={resetFilters}
                                    className="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors"
                                >
                                    <RotateCcw className="w-4 h-4 mr-1.5" />
                                    Reset Filters
                                </button>
                            </div>
                        </div>

                        <HistoryFilters filters={localFilters} onChange={handleFilterChange} />
                        <HistoryTable tasks={tasks.data} showPrices />
                        <HistoryPagination pagination={tasks} onPageChange={goToPage} />
                    </div>
                </div>
            </div>
        </>
    );
}
