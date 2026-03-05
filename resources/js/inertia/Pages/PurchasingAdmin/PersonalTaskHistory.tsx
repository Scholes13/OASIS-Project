import React, { useState } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import {
    Clock,
    Calendar,
    DollarSign,
    TrendingUp,
    CheckCircle2,
    RotateCcw,
    Download,
    ChevronLeft,
    ChevronRight,
    ExternalLink,
    ArrowLeft,
    User,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';
import type { AdminTask } from '@/components/purchasing-admin/types';

interface PersonalTaskHistoryProps extends PageProps {
    tasks: {
        data: AdminTask[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
        from: number;
        to: number;
    };
    statistics: {
        total_completed: number | string;
        avg_followup_time: number | string | null;
        avg_completion_time: number | string | null;
        total_savings: number | string | null;
        avg_savings_percentage: number | string | null;
    };
    filters: {
        date_from: string;
        date_to: string;
        status: string;
        type: string;
    };
}

// Format currency
const formatCurrency = (amount: number | string | null | undefined) => {
    if (amount === null || amount === undefined) return '-';
    const val = typeof amount === 'string' ? parseFloat(amount) : amount;
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(val);
};

// Format time (minutes to human readable)
const formatTime = (minutes: number | string | null | undefined) => {
    if (minutes === null || minutes === undefined) return '-';
    const val = typeof minutes === 'string' ? parseFloat(minutes) : minutes;
    if (val >= 60) {
        return `${(val / 60).toFixed(1)} hrs`;
    } else if (val >= 1) {
        return `${Math.round(val)} min`;
    } else {
        return `${Math.max(1, Math.round(val * 60))} sec`;
    }
};

// Format date
const formatDate = (dateString: string | null | undefined) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('id-ID', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Status badge component
function StatusBadge({ status }: { status: string }) {
    const styles: Record<string, { bg: string; text: string; label: string }> = {
        pending_followup: { bg: 'bg-amber-100', text: 'text-amber-700', label: 'Pending' },
        in_progress: { bg: 'bg-blue-100', text: 'text-blue-700', label: 'In Progress' },
        done: { bg: 'bg-emerald-100', text: 'text-emerald-700', label: 'Completed' },
    };
    const style = styles[status] || styles.pending_followup;

    return (
        <span className={cn('inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium', style.bg, style.text)}>
            {style.label}
        </span>
    );
}

export default function PersonalTaskHistory({ tasks, statistics, filters }: PersonalTaskHistoryProps) {
    const defaultFilters = {
        date_from: filters?.date_from || '',
        date_to: filters?.date_to || '',
        status: filters?.status || 'all',
        type: filters?.type || 'all',
    };
    const [localFilters, setLocalFilters] = useState(defaultFilters);

    const defaultStats = {
        total_completed: statistics?.total_completed || 0,
        avg_followup_time: statistics?.avg_followup_time || 0,
        avg_completion_time: statistics?.avg_completion_time || 0,
        total_savings: statistics?.total_savings || 0,
        avg_savings_percentage: statistics?.avg_savings_percentage || 0,
    };

    // Reset filters
    const resetFilters = () => {
        const reset = { date_from: '', date_to: '', status: 'all', type: 'all' };
        setLocalFilters(reset);
        router.get(route('purchasing.admin.personal-task-history'), reset, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle filter change
    const handleFilterChange = (key: string, value: string) => {
        const newFilters = { ...localFilters, [key]: value };
        setLocalFilters(newFilters);
        router.get(route('purchasing.admin.personal-task-history'), newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Navigate to page
    const goToPage = (page: number) => {
        router.get(route('purchasing.admin.personal-task-history'), { ...localFilters, page }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const taskData = tasks?.data || [];

    return (
        <>
            <Head title="Personal Task History" />
            <div className="py-6">
                <div className="w-full px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <Link
                            href={route('purchasing.admin.tasks')}
                            className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4"
                        >
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Back to Tasks
                        </Link>
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                                <User className="w-5 h-5 text-primary" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">Personal Task History</h1>
                                <p className="text-gray-500">All tasks you have handled</p>
                            </div>
                        </div>
                    </div>

                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                        {/* Total Completed */}
                        <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                                    <CheckCircle2 className="w-5 h-5 text-primary" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Total Completed</p>
                                    <p className="text-2xl font-bold text-gray-900">{defaultStats.total_completed}</p>
                                </div>
                            </div>
                        </div>

                        {/* Avg Follow-up Time */}
                        <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center">
                                    <Clock className="w-5 h-5 text-amber-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Avg Follow-up Time</p>
                                    <p className="text-2xl font-bold text-gray-900">{formatTime(defaultStats.avg_followup_time)}</p>
                                </div>
                            </div>
                        </div>

                        {/* Avg Completion Time */}
                        <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                                    <Calendar className="w-5 h-5 text-blue-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Avg Completion Time</p>
                                    <p className="text-2xl font-bold text-gray-900">{formatTime(defaultStats.avg_completion_time)}</p>
                                </div>
                            </div>
                        </div>

                        {/* Total Savings */}
                        <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <DollarSign className="w-5 h-5 text-emerald-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Total Savings</p>
                                    <p className="text-2xl font-bold text-emerald-600">{formatCurrency(defaultStats.total_savings)}</p>
                                </div>
                            </div>
                        </div>

                        {/* Avg Savings % */}
                        <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <TrendingUp className="w-5 h-5 text-emerald-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Avg Savings %</p>
                                    <p className="text-2xl font-bold text-emerald-600">
                                        {defaultStats.avg_savings_percentage ? `${Number(defaultStats.avg_savings_percentage).toFixed(1)}%` : '0%'}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Main Card */}
                    <div className="bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm">
                        {/* Header */}
                        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <h3 className="text-base font-semibold text-gray-900">Task History</h3>
                            </div>
                            <button
                                onClick={resetFilters}
                                className="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors"
                            >
                                <RotateCcw className="w-4 h-4 mr-1.5" />
                                Reset Filters
                            </button>
                        </div>

                        {/* Filters */}
                        <div className="px-5 py-4 bg-gray-50 border-b border-gray-100">
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                {/* Date From */}
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 mb-1">From Date</label>
                                    <input
                                        type="date"
                                        value={localFilters.date_from}
                                        onChange={(e) => handleFilterChange('date_from', e.target.value)}
                                        className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    />
                                </div>

                                {/* Date To */}
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 mb-1">To Date</label>
                                    <input
                                        type="date"
                                        value={localFilters.date_to}
                                        onChange={(e) => handleFilterChange('date_to', e.target.value)}
                                        className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    />
                                </div>

                                {/* Status Filter */}
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 mb-1">Status</label>
                                    <select
                                        value={localFilters.status}
                                        onChange={(e) => handleFilterChange('status', e.target.value)}
                                        className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    >
                                        <option value="all">All Statuses</option>
                                        <option value="pending_followup">Pending</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="done">Completed</option>
                                    </select>
                                </div>

                                {/* Type Filter */}
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 mb-1">Type</label>
                                    <select
                                        value={localFilters.type}
                                        onChange={(e) => handleFilterChange('type', e.target.value)}
                                        className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    >
                                        <option value="all">All Types</option>
                                        <option value="purchase_request">Purchase Request</option>
                                        <option value="stock_request">Stock Request</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {/* Table */}
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business Unit</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entered</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Follow-up Time</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion Time</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Savings</th>
                                        <th className="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-100">
                                    {taskData.length === 0 ? (
                                        <tr>
                                            <td colSpan={8} className="px-5 py-8 text-center text-sm text-gray-500">
                                                No task history found for the selected filters.
                                            </td>
                                        </tr>
                                    ) : (
                                        taskData.map((task) => {
                                            const isPR = task.taskable_type?.includes('PurchaseRequest');
                                            const number = isPR ? task.taskable?.pr_number : task.taskable?.st_number;

                                            return (
                                                <tr key={task.id} className="hover:bg-gray-50 transition-colors">
                                                    <td className="px-5 py-4 whitespace-nowrap text-sm font-medium text-gray-900 font-mono">
                                                        {number || 'N/A'}
                                                    </td>
                                                    <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {task.business_unit?.name || 'N/A'}
                                                    </td>
                                                    <td className="px-5 py-4 whitespace-nowrap text-sm">
                                                        <StatusBadge status={task.status} />
                                                    </td>
                                                    <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {formatDate(task.entered_at)}
                                                    </td>
                                                    <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {formatTime(task.followup_time_minutes)}
                                                    </td>
                                                    <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {formatTime(task.completion_time_minutes)}
                                                    </td>
                                                    <td className="px-5 py-4 whitespace-nowrap text-sm">
                                                        {task.savings_amount !== null ? (
                                                            <div className="flex flex-col">
                                                                <span className={cn(
                                                                    "font-medium",
                                                                    Number(task.savings_amount) >= 0 ? "text-emerald-600" : "text-red-600"
                                                                )}>
                                                                    {formatCurrency(task.savings_amount)}
                                                                </span>
                                                                <span className="text-xs text-gray-500">
                                                                    ({task.savings_percentage ? Number(task.savings_percentage).toFixed(1) : '0'}%)
                                                                </span>
                                                            </div>
                                                        ) : (
                                                            <span className="text-gray-400">-</span>
                                                        )}
                                                    </td>
                                                    <td className="px-5 py-4 whitespace-nowrap text-sm text-center">
                                                        <Link
                                                            href={route('purchasing.admin.tasks.show', { taskId: task.id })}
                                                            className="inline-flex items-center px-3 py-1.5 text-sm text-primary hover:text-primary hover:bg-blue-600 rounded-md transition-colors font-medium"
                                                        >
                                                            <ExternalLink className="w-4 h-4 mr-1" />
                                                            Detail
                                                        </Link>
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {tasks && tasks.last_page > 1 && (
                            <div className="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
                                <p className="text-sm text-gray-500">
                                    Showing {tasks.from} to {tasks.to} of {tasks.total} results
                                </p>
                                <div className="flex items-center gap-2">
                                    <button
                                        onClick={() => goToPage(tasks.current_page - 1)}
                                        disabled={tasks.current_page === 1}
                                        className="p-2 text-gray-400 hover:text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <ChevronLeft className="w-5 h-5" />
                                    </button>
                                    {Array.from({ length: Math.min(5, tasks.last_page) }, (_, i) => {
                                        let page = i + 1;
                                        if (tasks.current_page > 3 && tasks.last_page > 5) {
                                            page = tasks.current_page - 2 + i;
                                            if (page > tasks.last_page) page = tasks.last_page - 4 + i;
                                        }
                                        return (
                                            <button
                                                key={page}
                                                onClick={() => goToPage(page)}
                                                className={cn(
                                                    "px-3 py-1 text-sm rounded-md",
                                                    page === tasks.current_page
                                                        ? "bg-primary text-white"
                                                        : "text-gray-600 hover:bg-gray-100"
                                                )}
                                            >
                                                {page}
                                            </button>
                                        );
                                    })}
                                    <button
                                        onClick={() => goToPage(tasks.current_page + 1)}
                                        disabled={tasks.current_page === tasks.last_page}
                                        className="p-2 text-gray-400 hover:text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <ChevronRight className="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
