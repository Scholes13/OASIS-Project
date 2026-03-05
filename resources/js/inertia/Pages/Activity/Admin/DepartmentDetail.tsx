import { useState, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle,
    Clock,
    Download,
    ListTodo,
    Search,
    Users,
    PieChart,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/Badge';
import { toast } from '@/components/ui/toast';
import { cn } from '@/lib/utils';
import type { PageProps, Department, Task, ActivityType, PaginatedData } from '@/types';

interface UserBreakdown {
    created_by: number;
    created_by_name: string;
    total: number;
    completed: number;
    in_progress: number;
    planned: number;
}

interface ActivityTypeDistribution {
    name: string;
    color: string;
    count: number;
}

interface DepartmentDetailProps extends PageProps {
    department: Department;
    tasks: PaginatedData<Task>;
    stats: { total: number; completed: number; in_progress: number; planned: number };
    userBreakdown: UserBreakdown[];
    activityTypeDistribution: ActivityTypeDistribution[];
    activityTypes: ActivityType[];
    filters: {
        date_from: string;
        date_to: string;
        status: string;
        activity_type_id: string;
        search: string;
    };
}

const statusConfig: Record<string, { label: string; variant: 'success' | 'info' | 'warning' | 'danger' | 'default' }> = {
    planned: { label: 'Planned', variant: 'warning' },
    in_progress: { label: 'In Progress', variant: 'info' },
    completed: { label: 'Completed', variant: 'success' },
    cancelled: { label: 'Cancelled', variant: 'danger' },
};

function formatDate(d: string) {
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

export default function DepartmentDetail({
    department, tasks, stats, userBreakdown, activityTypeDistribution, activityTypes, filters,
}: DepartmentDetailProps) {
    const { flash } = usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.search);
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);

    useEffect(() => {
        if (flash.success) toast.success(flash.success);
        if (flash.error) toast.error(flash.error);
    }, [flash]);

    const applyFilters = (overrides: Record<string, string> = {}) => {
        router.get(
            route('activity.admin.department', { department: department.id }),
            { date_from: dateFrom, date_to: dateTo, status: filters.status, activity_type_id: filters.activity_type_id, search, ...overrides },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleExport = () => {
        const params = new URLSearchParams({
            department_id: String(department.id),
            date_from: dateFrom,
            date_to: dateTo,
            ...(filters.status && { status: filters.status }),
            ...(filters.activity_type_id && { activity_type_id: filters.activity_type_id }),
        });
        window.location.href = route('activity.admin.export') + '?' + params.toString();
    };

    return (
        <>
            <Head title={`Activity Admin - ${department.name}`} />

            <div className="w-full px-6 py-6 lg:px-8">
                {/* Header */}
                <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-4">
                        <Link href={route('activity.admin.dashboard') + `?date_from=${dateFrom}&date_to=${dateTo}`}>
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="w-4 h-4 mr-1" strokeWidth={1.5} />
                                Back
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">{department.name}</h1>
                            <p className="text-sm text-gray-500">{department.code}</p>
                        </div>
                    </div>
                    <Button onClick={handleExport} variant="outline">
                        <Download className="w-4 h-4 mr-2" strokeWidth={1.5} />
                        Export Excel
                    </Button>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    {[
                        { label: 'Total', value: stats.total, icon: ListTodo, color: 'text-primary bg-primary/10' },
                        { label: 'Completed', value: stats.completed, icon: CheckCircle, color: 'text-emerald-600 bg-emerald-50' },
                        { label: 'In Progress', value: stats.in_progress, icon: Clock, color: 'text-blue-600 bg-blue-50' },
                        { label: 'Planned', value: stats.planned, icon: Clock, color: 'text-amber-600 bg-amber-50' },
                    ].map((s) => (
                        <div key={s.label} className="bg-white rounded-xl border border-gray-200 p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-500">{s.label}</p>
                                    <p className="text-2xl font-bold text-gray-900">{s.value}</p>
                                </div>
                                <div className={cn('p-2.5 rounded-xl', s.color)}>
                                    <s.icon className="w-5 h-5" strokeWidth={1.5} />
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    {/* User Breakdown */}
                    <div className="bg-white rounded-xl border border-gray-200 p-5">
                        <h3 className="text-sm font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <Users className="w-4 h-4 text-primary" strokeWidth={1.5} />
                            Per User
                        </h3>
                        {userBreakdown.length === 0 ? (
                            <p className="text-sm text-gray-400">Tidak ada data.</p>
                        ) : (
                            <div className="space-y-3">
                                {userBreakdown.map((u) => (
                                    <div key={u.created_by} className="flex items-center justify-between">
                                        <div className="flex items-center gap-2 min-w-0">
                                            <div className="w-7 h-7 bg-blue-600 rounded-full flex items-center justify-center text-xs font-medium text-white flex-shrink-0">
                                                {(u.created_by_name || '?').charAt(0).toUpperCase()}
                                            </div>
                                            <span className="text-sm text-gray-900 truncate">{u.created_by_name || 'Unknown'}</span>
                                        </div>
                                        <div className="flex items-center gap-1.5 text-xs flex-shrink-0">
                                            <span className="text-emerald-600 font-medium">{u.completed}</span>
                                            <span className="text-gray-300">/</span>
                                            <span className="text-gray-600 font-medium">{u.total}</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Activity Type Distribution */}
                    <div className="bg-white rounded-xl border border-gray-200 p-5 lg:col-span-2">
                        <h3 className="text-sm font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <PieChart className="w-4 h-4 text-primary" strokeWidth={1.5} />
                            Activity Type Distribution
                        </h3>
                        {activityTypeDistribution.length === 0 ? (
                            <p className="text-sm text-gray-400">Tidak ada data.</p>
                        ) : (
                            <div className="grid grid-cols-2 gap-3">
                                {activityTypeDistribution.map((at) => (
                                    <div key={at.name} className="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50">
                                        <div className="w-3 h-3 rounded-full flex-shrink-0" style={{ backgroundColor: at.color || '#6366f1' }} />
                                        <span className="text-sm text-gray-700 truncate flex-1">{at.name}</span>
                                        <span className="text-sm font-semibold text-gray-900">{at.count}</span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-white rounded-xl border border-gray-100 p-4 mb-6">
                    <div className="flex flex-wrap items-center gap-3">
                        <input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" />
                        <span className="text-gray-400">—</span>
                        <input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" />
                        <select
                            value={filters.status}
                            onChange={(e) => applyFilters({ status: e.target.value })}
                            className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary"
                        >
                            <option value="">All Status</option>
                            <option value="planned">Planned</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <select
                            value={filters.activity_type_id}
                            onChange={(e) => applyFilters({ activity_type_id: e.target.value })}
                            className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary"
                        >
                            <option value="">All Activity Types</option>
                            {activityTypes.map((at) => (
                                <option key={at.id} value={at.id}>{at.name}</option>
                            ))}
                        </select>
                        <div className="relative flex-1 min-w-[200px]">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                placeholder="Search task title..."
                                className="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary"
                            />
                        </div>
                        <Button onClick={() => applyFilters()} size="sm">Filter</Button>
                    </div>
                </div>

                {/* Task List */}
                <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                {['Tanggal', 'Judul', 'Tipe', 'Status', 'Prioritas', 'Pembuat', 'Due Date'].map((h) => (
                                    <th key={h} className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{h}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {tasks.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="px-4 py-12 text-center text-gray-400">
                                        Tidak ada task ditemukan.
                                    </td>
                                </tr>
                            ) : tasks.data.map((task) => (
                                <tr key={task.id} className="hover:bg-gray-50 transition-colors">
                                    <td className="px-4 py-3 text-sm text-gray-900">{task.task_date ? formatDate(task.task_date) : '-'}</td>
                                    <td className="px-4 py-3">
                                        <Link
                                            href={route('activity.admin.task', { task: task.id })}
                                            className="text-sm font-medium text-primary hover:text-primary hover:underline"
                                        >
                                            {task.task_title}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">
                                        {task.activity_type && (
                                            <span className="inline-flex items-center gap-1.5 text-sm">
                                                <span className="w-2 h-2 rounded-full" style={{ backgroundColor: task.activity_type.color || '#6366f1' }} />
                                                {task.activity_type.name}
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge variant={statusConfig[task.status]?.variant || 'default'}>
                                            {statusConfig[task.status]?.label || task.status}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge variant={task.priority === 'high' ? 'danger' : task.priority === 'medium' ? 'warning' : 'default'}>
                                            {task.priority ? task.priority.charAt(0).toUpperCase() + task.priority.slice(1) : 'Medium'}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 text-sm text-gray-700">{task.creator?.name || '-'}</td>
                                    <td className="px-4 py-3 text-sm text-gray-500">{task.due_date ? formatDate(task.due_date) : '-'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    {/* Pagination */}
                    {tasks.meta && tasks.meta.last_page > 1 && (
                        <div className="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
                            <p className="text-sm text-gray-500">
                                Showing {tasks.meta.from} to {tasks.meta.to} of {tasks.meta.total}
                            </p>
                            <div className="flex gap-1">
                                {tasks.meta.links.map((link, i) => (
                                    <button
                                        key={i}
                                        disabled={!link.url}
                                        onClick={() => link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })}
                                        className={cn(
                                            'px-3 py-1 text-sm rounded-lg transition-colors',
                                            link.active ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-100',
                                            !link.url && 'opacity-40 cursor-not-allowed'
                                        )}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
