import { Suspense, lazy, useState, useEffect, useCallback, useRef } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, ArrowUpRight, CalendarRange, CheckCircle, Clock, Download, Search } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/Badge';
import { toast } from '@/components/ui/toast';
import { cn } from '@/lib/utils';
import type { PageProps, Department, Task, ActivityType, PaginatedData } from '@/types';

const TaskDetailModal = lazy(() => import('@/components/activity/TaskDetailModal'));

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

interface FlatPaginationLink {
    url: string | null;
    label: string;
    active: boolean;
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
        per_page: string;
    };
    selectedTask?: Task | null;
    selectedTaskModal?: 'detail' | null;
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

function formatPaginationLabel(label: string) {
    if (label.includes('&laquo;')) return 'Previous';
    if (label.includes('&raquo;')) return 'Next';

    return label
        .replace(/&amp;/g, '&')
        .replace(/&#039;/g, "'")
        .trim();
}

function resolvePageFromUrl(url: string | null) {
    if (!url) return null;

    try {
        return new URL(url).searchParams.get('page');
    } catch {
        return new URL(url, window.location.origin).searchParams.get('page');
    }
}

export default function DepartmentDetail({
    department, tasks, stats, userBreakdown, activityTypeDistribution, activityTypes, filters,
    selectedTask = null, selectedTaskModal = null,
}: DepartmentDetailProps) {
    const { flash } = usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.search);
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);
    const [status, setStatus] = useState(filters.status);
    const [activityTypeId, setActivityTypeId] = useState(filters.activity_type_id);
    const [perPage, setPerPage] = useState(filters.per_page);
    const [detailTask, setDetailTask] = useState<Task | null>(null);
    const [showDetailModal, setShowDetailModal] = useState(false);
    const handledModalQueryRef = useRef<string | null>(null);
    const paginator = tasks as PaginatedData<Task> & {
        current_page?: number;
        from?: number | null;
        last_page?: number;
        links?: FlatPaginationLink[] | PaginatedData<Task>['links'];
        per_page?: number;
        to?: number | null;
        total?: number;
    };

    const completionRate = stats.total > 0 ? Math.round((stats.completed / stats.total) * 100) : 0;
    const activeContributors = userBreakdown.length;
    const topActivityTypes = activityTypeDistribution.slice(0, 6);
    const dateRangeLabel = `${formatDate(filters.date_from)} - ${formatDate(filters.date_to)}`;
    const tableHeaders = ['Date', 'Task Title', 'Type', 'Status', 'Priority', 'Created By', 'Due Date'];
    const paginationLinks = Array.isArray(paginator.links) ? paginator.links : tasks.meta?.links ?? [];
    const currentPage = paginator.current_page ?? tasks.meta?.current_page ?? 1;
    const lastPage = paginator.last_page ?? tasks.meta?.last_page ?? 1;
    const currentFrom = paginator.from ?? tasks.meta?.from ?? (tasks.data.length > 0 ? 1 : null);
    const currentTo = paginator.to ?? tasks.meta?.to ?? tasks.data.length;
    const totalTasks = paginator.total ?? tasks.meta?.total ?? tasks.data.length;
    const currentPerPage = String(paginator.per_page ?? tasks.meta?.per_page ?? Number(filters.per_page || '10'));

    useEffect(() => {
        if (flash.success) toast.success(flash.success);
        if (flash.error) toast.error(flash.error);
    }, [flash]);

    useEffect(() => {
        setSearch(filters.search);
        setDateFrom(filters.date_from);
        setDateTo(filters.date_to);
        setStatus(filters.status);
        setActivityTypeId(filters.activity_type_id);
        setPerPage(filters.per_page);
    }, [filters.activity_type_id, filters.date_from, filters.date_to, filters.per_page, filters.search, filters.status]);

    const syncModalQueryState = useCallback((taskId: number) => {
        if (typeof window === 'undefined') return;
        const nextUrl = new URL(window.location.href);
        nextUrl.searchParams.set('modal', 'detail');
        nextUrl.searchParams.set('task', String(taskId));
        const nextSearch = nextUrl.searchParams.toString();
        window.history.replaceState({}, '', `${nextUrl.pathname}${nextSearch ? `?${nextSearch}` : ''}`);
    }, []);

    const clearModalQueryState = useCallback(() => {
        if (typeof window === 'undefined') return;
        const nextUrl = new URL(window.location.href);
        nextUrl.searchParams.delete('modal');
        nextUrl.searchParams.delete('task');
        const nextSearch = nextUrl.searchParams.toString();
        window.history.replaceState({}, '', `${nextUrl.pathname}${nextSearch ? `?${nextSearch}` : ''}`);
        handledModalQueryRef.current = null;
    }, []);

    const openTaskDetail = useCallback((task: Task, config?: { syncUrl?: boolean }) => {
        setDetailTask(task);
        setShowDetailModal(true);
        if (config?.syncUrl !== false) syncModalQueryState(task.id);
    }, [syncModalQueryState]);

    const closeTaskDetail = useCallback(() => {
        setShowDetailModal(false);
        setDetailTask(null);
        clearModalQueryState();
    }, [clearModalQueryState]);

    useEffect(() => {
        if (typeof window === 'undefined') return;
        const searchParams = new URLSearchParams(window.location.search);
        const modalType = searchParams.get('modal') ?? selectedTaskModal;
        const taskId = searchParams.get('task') ?? (selectedTask && modalType === 'detail' ? String(selectedTask.id) : null);
        if (modalType !== 'detail' || !taskId) return;
        const signature = `${taskId}:${modalType}`;
        if (handledModalQueryRef.current === signature) return;
        const matchedTask = (selectedTask && String(selectedTask.id) === taskId ? selectedTask : null)
            ?? tasks.data.find((task) => String(task.id) === taskId)
            ?? null;
        if (!matchedTask) return;
        handledModalQueryRef.current = signature;
        openTaskDetail(matchedTask, { syncUrl: false });
    }, [openTaskDetail, selectedTask, selectedTaskModal, tasks.data]);

    const applyFilters = (overrides: Record<string, string> = {}) => {
        router.get(
            route('activity.admin.department', { department: department.id }),
            { date_from: dateFrom, date_to: dateTo, status, activity_type_id: activityTypeId, search, per_page: perPage, ...overrides },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handlePaginationClick = (link: FlatPaginationLink) => {
        const targetPage = resolvePageFromUrl(link.url);

        if (!targetPage) return;

        applyFilters({ page: targetPage, per_page: perPage });
    };

    const handlePerPageChange = (value: string) => {
        setPerPage(value);
        applyFilters({ per_page: value, page: '1' });
    };

    const handleExport = () => {
        const params = new URLSearchParams({
            department_id: String(department.id),
            date_from: dateFrom,
            date_to: dateTo,
            ...(status && { status }),
            ...(activityTypeId && { activity_type_id: activityTypeId }),
        });
        window.location.href = route('activity.admin.export') + '?' + params.toString();
    };

    return (
        <>
            <Head title={`Activity Admin - ${department.name}`} />
            <div className="min-h-[calc(100vh-72px)] w-full bg-slate-50/70 px-4 py-5 lg:px-8 lg:py-7">
                <div className="mx-auto flex w-full max-w-[1440px] flex-col gap-5">
                    <section className="rounded-[28px] border border-slate-200/80 bg-gradient-to-br from-white via-slate-50 to-sky-50/50 p-5 shadow-[0_18px_48px_rgba(15,23,42,0.06)] lg:p-7">
                        <div className="flex flex-col gap-6">
                            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div className="flex items-start gap-3 lg:gap-4">
                                    <Link href={route('activity.admin.dashboard') + `?date_from=${dateFrom}&date_to=${dateTo}`}>
                                        <Button variant="ghost" size="sm" className="mt-0.5 h-9 rounded-full px-3 text-slate-600 hover:bg-white hover:text-slate-900">
                                            <ArrowLeft className="mr-1.5 h-4 w-4" strokeWidth={1.5} />
                                            Back
                                        </Button>
                                    </Link>
                                    <div className="min-w-0">
                                        <p className="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Department activity workspace</p>
                                        <h1 className="mt-2 text-2xl font-semibold tracking-tight text-slate-950 lg:text-[2rem]">{department.name}</h1>
                                        <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                                            Review operational output, contributor balance, and active workload in one clean management surface.
                                        </p>
                                        <div className="mt-4 flex flex-wrap items-center gap-2">
                                            <span className="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">{department.code}</span>
                                            <span className="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600">
                                                <CalendarRange className="h-3.5 w-3.5 text-slate-400" />
                                                {dateRangeLabel}
                                            </span>
                                            <span className="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600">{stats.total} tasks in scope</span>
                                        </div>
                                    </div>
                                </div>
                                <Button onClick={handleExport} variant="outline" className="h-11 rounded-xl border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50">
                                    <Download className="mr-2 h-4 w-4" strokeWidth={1.5} />
                                    Export Excel
                                </Button>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                {[
                                    { label: 'Completed', value: stats.completed, icon: CheckCircle, color: 'text-emerald-600 bg-emerald-50 ring-emerald-100' },
                                    { label: 'In Progress', value: stats.in_progress, icon: Clock, color: 'text-blue-600 bg-blue-50 ring-blue-100' },
                                    { label: 'Planned', value: stats.planned, icon: Clock, color: 'text-amber-600 bg-amber-50 ring-amber-100' },
                                    { label: 'Completion rate', value: `${completionRate}%`, icon: ArrowUpRight, color: 'text-sky-700 bg-sky-50 ring-sky-100' },
                                ].map((card) => (
                                    <div key={card.label} className="rounded-2xl border border-white/80 bg-white/90 p-4 shadow-sm">
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">{card.label}</p>
                                                <p className="mt-3 text-[30px] font-semibold leading-none tracking-tight text-slate-950">{card.value}</p>
                                            </div>
                                            <span className={cn('inline-flex h-10 w-10 items-center justify-center rounded-2xl ring-1', card.color)}>
                                                <card.icon className="h-5 w-5" strokeWidth={1.5} />
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section className="rounded-[24px] border border-slate-200/80 bg-white p-4 shadow-[0_12px_38px_rgba(15,23,42,0.04)] lg:p-5">
                        <div className="flex flex-col gap-4 xl:flex-row xl:items-center">
                            <div className="grid flex-1 gap-3 sm:grid-cols-2 xl:grid-cols-[160px_160px_180px_220px_minmax(260px,1fr)]">
                                <label className="flex flex-col gap-1.5">
                                    <span className="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">From</span>
                                    <input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="h-11 rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-sm text-slate-700 outline-none transition focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/10" />
                                </label>
                                <label className="flex flex-col gap-1.5">
                                    <span className="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">To</span>
                                    <input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="h-11 rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-sm text-slate-700 outline-none transition focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/10" />
                                </label>
                                <label className="flex flex-col gap-1.5">
                                    <span className="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Status</span>
                                    <select value={status} onChange={(e) => setStatus(e.target.value)} className="h-11 rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-sm text-slate-700 outline-none transition focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/10">
                                        <option value="">All Status</option>
                                        <option value="planned">Planned</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </label>
                                <label className="flex flex-col gap-1.5">
                                    <span className="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Activity Type</span>
                                    <select value={activityTypeId} onChange={(e) => setActivityTypeId(e.target.value)} className="h-11 rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-sm text-slate-700 outline-none transition focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/10">
                                        <option value="">All Activity Types</option>
                                        {activityTypes.map((at) => <option key={at.id} value={at.id}>{at.name}</option>)}
                                    </select>
                                </label>
                                <label className="flex flex-col gap-1.5">
                                    <span className="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Search</span>
                                    <div className="relative">
                                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                        <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && applyFilters({ page: '1' })} placeholder="Search task title..." className="h-11 w-full rounded-xl border border-slate-200 bg-slate-50/70 pl-10 pr-3 text-sm text-slate-700 outline-none transition focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/10" />
                                    </div>
                                </label>
                            </div>
                            <div className="flex items-end">
                                <Button onClick={() => applyFilters({ page: '1' })} className="h-11 rounded-xl px-5" size="sm">Apply Filters</Button>
                            </div>
                        </div>
                    </section>

                    <section className="grid gap-5 xl:grid-cols-[1.1fr_1fr]">
                        <div className="rounded-[24px] border border-slate-200/80 bg-white shadow-[0_12px_38px_rgba(15,23,42,0.04)]">
                            <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                                <div>
                                    <h2 className="text-sm font-semibold text-slate-900">Contributor balance</h2>
                                    <p className="mt-1 text-sm text-slate-500">See output distribution across active contributors.</p>
                                </div>
                                <span className="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">{activeContributors} active contributors</span>
                            </div>
                            <div className="px-5 py-4">
                                {userBreakdown.length === 0 ? (
                                    <p className="text-sm text-slate-400">No contributor data available.</p>
                                ) : (
                                    <div className="space-y-4">
                                        {userBreakdown.map((u) => {
                                            const contributorCompletion = u.total > 0 ? Math.round((u.completed / u.total) * 100) : 0;
                                            return (
                                                <div key={u.created_by} className="grid gap-2">
                                                    <div className="flex items-center justify-between gap-3">
                                                        <div className="min-w-0">
                                                            <div className="flex items-center gap-3">
                                                                <span className="inline-flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold uppercase text-white">{(u.created_by_name || '?').charAt(0)}</span>
                                                                <div className="min-w-0">
                                                                    <p className="truncate text-sm font-medium text-slate-900">{u.created_by_name || 'Unknown'}</p>
                                                                    <p className="text-xs text-slate-500">{u.completed} completed of {u.total} tasks</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="text-right">
                                                            <p className="text-sm font-semibold text-slate-900">{contributorCompletion}%</p>
                                                            <p className="text-xs text-slate-400">completion</p>
                                                        </div>
                                                    </div>
                                                    <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                                                        <div className="h-full rounded-full bg-emerald-500" style={{ width: `${contributorCompletion}%` }} />
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="rounded-[24px] border border-slate-200/80 bg-white shadow-[0_12px_38px_rgba(15,23,42,0.04)]">
                            <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                                <div>
                                    <h2 className="text-sm font-semibold text-slate-900">Activity mix</h2>
                                    <p className="mt-1 text-sm text-slate-500">Top categories shaping this department workload.</p>
                                </div>
                                <span className="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">{activityTypeDistribution.length} tracked types</span>
                            </div>
                            <div className="px-5 py-4">
                                {activityTypeDistribution.length === 0 ? (
                                    <p className="text-sm text-slate-400">No activity mix data available.</p>
                                ) : (
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        {topActivityTypes.map((at) => (
                                            <div key={at.name} className="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50/70 px-4 py-3">
                                                <div className="min-w-0">
                                                    <div className="flex items-center gap-2">
                                                        <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: at.color || '#6366f1' }} />
                                                        <span className="truncate text-sm font-medium text-slate-700">{at.name}</span>
                                                    </div>
                                                </div>
                                                <span className="text-sm font-semibold text-slate-900">{at.count}</span>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </section>

                    <section className="overflow-hidden rounded-[24px] border border-slate-200/80 bg-white shadow-[0_12px_38px_rgba(15,23,42,0.04)]">
                        <div className="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <h2 className="text-sm font-semibold text-slate-900">Task register</h2>
                                <p className="mt-1 text-sm text-slate-500">Review the current task inventory without leaving the department workspace.</p>
                            </div>
                            <div className="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                <span className="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 font-medium text-slate-600">{totalTasks} tasks</span>
                                <span className="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 font-medium text-slate-600">{dateRangeLabel}</span>
                            </div>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="min-w-[920px] divide-y divide-slate-100">
                                <thead className="bg-slate-50/80">
                                    <tr>
                                        {tableHeaders.map((h) => <th key={h} className="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">{h}</th>)}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {tasks.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={7} className="px-5 py-16 text-center text-sm text-slate-400">No tasks found for the selected filters.</td>
                                        </tr>
                                    ) : tasks.data.map((task) => (
                                        <tr key={task.id} className="transition-colors hover:bg-slate-50/80">
                                            <td className="px-5 py-3 text-sm text-slate-700">{task.task_date ? formatDate(task.task_date) : '-'}</td>
                                            <td className="px-5 py-3">
                                                <button
                                                    type="button"
                                                    aria-label={`Open task details for ${task.task_title}`}
                                                    onClick={() => openTaskDetail(task)}
                                                    className="text-left text-sm font-semibold text-primary transition hover:text-primary/80"
                                                >
                                                    {task.task_title}
                                                </button>
                                            </td>
                                            <td className="px-5 py-3">
                                                {task.activity_type && (
                                                    <span className="inline-flex items-center gap-2 text-sm text-slate-700">
                                                        <span className="h-2 w-2 rounded-full" style={{ backgroundColor: task.activity_type.color || '#6366f1' }} />
                                                        {task.activity_type.name}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3">
                                                <Badge variant={statusConfig[task.status]?.variant || 'default'}>{statusConfig[task.status]?.label || task.status}</Badge>
                                            </td>
                                            <td className="px-5 py-3">
                                                <Badge variant={task.priority === 'high' ? 'danger' : task.priority === 'medium' ? 'warning' : 'default'}>
                                                    {task.priority ? task.priority.charAt(0).toUpperCase() + task.priority.slice(1) : 'Medium'}
                                                </Badge>
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-700">{task.creator?.name || '-'}</td>
                                            <td className="px-5 py-3 text-sm text-slate-500">{task.due_date ? formatDate(task.due_date) : '-'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {totalTasks > 0 && (
                            <div className="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 text-sm text-slate-500 lg:flex-row lg:items-center lg:justify-between">
                                <div className="flex flex-col gap-1 sm:flex-row sm:flex-wrap sm:items-center sm:gap-3">
                                    <p>Showing {currentFrom ?? 0} to {currentTo ?? 0} of {totalTasks}</p>
                                    <span className="hidden text-slate-300 sm:inline">|</span>
                                    <p>Page {currentPage} of {lastPage}</p>
                                </div>
                                <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                                    <label className="flex items-center gap-2 text-sm text-slate-600">
                                        <span>Rows</span>
                                        <select
                                            aria-label="Rows per page"
                                            value={perPage}
                                            onChange={(e) => handlePerPageChange(e.target.value)}
                                            className="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10"
                                        >
                                            {['10', '20', '50'].map((option) => (
                                                <option key={option} value={option}>{option} / page</option>
                                            ))}
                                        </select>
                                    </label>
                                    <div className="flex flex-wrap gap-1.5">
                                        {paginationLinks.map((link, i) => {
                                            const label = formatPaginationLabel(link.label);

                                            return (
                                                <button
                                                    key={`${label}-${i}`}
                                                    type="button"
                                                    disabled={!link.url || link.active}
                                                    onClick={() => handlePaginationClick(link)}
                                                    className={cn(
                                                        'rounded-lg border px-3 py-1.5 text-sm transition-colors',
                                                        link.active
                                                            ? 'border-primary bg-primary text-white shadow-sm'
                                                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50',
                                                        !link.url && 'cursor-not-allowed opacity-40',
                                                    )}
                                                >
                                                    {label}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            </div>
                        )}
                    </section>
                </div>

                {showDetailModal && (
                    <Suspense fallback={null}>
                        <TaskDetailModal open={showDetailModal} task={detailTask} onClose={closeTaskDetail} mode="admin-readonly" />
                    </Suspense>
                )}
            </div>
        </>
    );
}
