import { Head, router, usePage } from '@inertiajs/react';
import { DashboardCharts } from '@/components/purchasing-admin/dashboard/DashboardCharts';
import { DashboardMetrics } from '@/components/purchasing-admin/dashboard/DashboardMetrics';
import { RecentTasksList } from '@/components/purchasing-admin/dashboard/RecentTasksList';
import type { DashboardMetricsData, DashboardStats, DepartmentBreakdownItem, RecentAdminTask } from '@/components/purchasing-admin/dashboard/dashboardTypes';
import type { PageProps } from '@/types';

interface DashboardProps extends PageProps {
    stats: DashboardStats;
    recentTasks: RecentAdminTask[];
    metrics: DashboardMetricsData;
    savingsTrend: {
        labels: string[];
        data: number[];
    };
    departmentBreakdown: DepartmentBreakdownItem[];
    datePreset: string;
    dateRange: {
        from: string | null;
        to: string | null;
    };
    userRole: {
        is_purchasing_admin: boolean;
        is_management: boolean;
    };
}

const Dashboard: React.FC = () => {
    const { stats, recentTasks, metrics, savingsTrend, departmentBreakdown, datePreset, userRole } = usePage<DashboardProps>().props;

    const handleFilterChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        router.get(
            route('purchasing.admin.dashboard'),
            { date_preset: e.target.value },
            { preserveState: true, preserveScroll: true }
        );
    };

    return (
        <>
            <Head title="Purchasing Admin Dashboard" />

            <div className="py-6">
                <div className="w-full px-4 sm:px-6 lg:px-8">
                    <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Purchasing Admin Dashboard</h1>
                            <p className="mt-1 text-sm text-gray-600">
                                {userRole.is_management ? 'Management Overview' : 'Your Tasks & Performance'}
                            </p>
                        </div>

                        <div className="flex items-center space-x-2">
                            <select
                                value={datePreset}
                                onChange={handleFilterChange}
                                className="block w-full sm:w-auto rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm pl-3 pr-10 py-2"
                            >
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="this_year">This Year</option>
                                <option value="all_time">All Time</option>
                            </select>
                        </div>
                    </div>

                    <DashboardMetrics stats={stats} metrics={metrics} />
                    <DashboardCharts
                        stats={stats}
                        savingsTrend={savingsTrend}
                        departmentBreakdown={departmentBreakdown}
                    />
                    <RecentTasksList recentTasks={recentTasks} />
                </div>
            </div>
        </>
    );
};

export default Dashboard;
