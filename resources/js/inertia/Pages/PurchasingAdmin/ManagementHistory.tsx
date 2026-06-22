import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, RotateCcw, Users } from 'lucide-react';
import { HistoryFilters } from '@/components/purchasing-admin/history/HistoryFilters';
import { HistoryPagination } from '@/components/purchasing-admin/history/HistoryPagination';
import { HistoryStatsCards } from '@/components/purchasing-admin/history/HistoryStatsCards';
import { HistoryTable } from '@/components/purchasing-admin/history/HistoryTable';
import type { AdminOption, HistoryFiltersState, HistoryStats } from '@/components/purchasing-admin/history/historyUtils';
import type { AdminTask } from '@/components/purchasing-admin/types';
import type { PageProps } from '@/types';

interface ManagementHistoryProps extends PageProps {
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
    adminList: AdminOption[];
    filters: HistoryFiltersState;
}

export default function ManagementHistory({ tasks, statistics, adminList, filters }: ManagementHistoryProps) {
    const defaultFilters = {
        date_from: filters?.date_from || '',
        date_to: filters?.date_to || '',
        status: filters?.status || 'all',
        type: filters?.type || 'all',
        admin: filters?.admin || 'all',
    };
    const [localFilters, setLocalFilters] = useState(defaultFilters);

    const defaultStats = {
        total_completed: statistics?.total_completed || 0,
        avg_followup_time: statistics?.avg_followup_time || 0,
        avg_completion_time: statistics?.avg_completion_time || 0,
        total_savings: statistics?.total_savings || 0,
        avg_savings_percentage: statistics?.avg_savings_percentage || 0,
    };

    const resetFilters = () => {
        const reset = { date_from: '', date_to: '', status: 'all', type: 'all', admin: 'all' };
        setLocalFilters(reset);
        router.get(route('purchasing.admin.management-history'), reset, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleFilterChange = (key: keyof HistoryFiltersState, value: string) => {
        const newFilters = { ...localFilters, [key]: value };
        setLocalFilters(newFilters);
        router.get(route('purchasing.admin.management-history'), newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const goToPage = (page: number) => {
        router.get(route('purchasing.admin.management-history'), { ...localFilters, page }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Management History" />
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
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                <Users className="w-5 h-5 text-purple-600" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">Management History</h1>
                                <p className="text-gray-500">All tasks handled by all admins</p>
                            </div>
                        </div>
                    </div>

                    <HistoryStatsCards statistics={defaultStats} />

                    <div className="bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm">
                        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h3 className="text-base font-semibold text-gray-900">Task History</h3>
                            <button
                                onClick={resetFilters}
                                className="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors"
                            >
                                <RotateCcw className="w-4 h-4 mr-1.5" />
                                Reset Filters
                            </button>
                        </div>

                        <HistoryFilters filters={localFilters} admins={adminList || []} onChange={handleFilterChange} />
                        <HistoryTable tasks={tasks?.data || []} showAdmin />
                        <HistoryPagination pagination={tasks} onPageChange={goToPage} />
                    </div>
                </div>
            </div>
        </>
    );
}
