import { useMemo, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    Activity,
    AlertTriangle,
    Building2,
    ChevronRight,
    Clock,
    TrendingUp,
    Users,
    Briefcase,
    BarChart3,
    Download,
    Filter,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';

type PeriodFilter = 'today' | 'week' | 'month' | 'year' | 'all';

const periodLabels: Record<PeriodFilter, string> = {
    today: 'Today',
    week: 'This Week',
    month: 'This Month',
    year: 'This Year',
    all: 'All Time',
};

interface TaskBasic {
    id: number;
    title?: string;
    task_title?: string;
    due_date?: string | null;
    is_critical?: boolean;
    status?: string;
    task_description?: string;
    activity_type?: { name: string; color: string };
    duration_minutes?: number;
    participants?: Array<{ id?: number; user_id?: number; name?: string; user?: { name?: string }; primary_position?: { name?: string } }>;
}

interface Stats {
    total: number;
    completed: number;
    in_progress: number;
    overdue: number;
    planned?: number;
    completed_this_month?: number;
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface PersonalVisuals {
    roadmap: PaginatedData<TaskBasic>;
    upcoming: TaskBasic[];
    distribution: { name: string; color: string; value: number }[];
}

interface DepartmentVisuals {
    roadmap: PaginatedData<TaskBasic>;
    upcoming: TaskBasic[];
    distribution: { name: string; color: string; value: number }[];
    bottleneck: number;
    top_category: string;
}

interface ExecutiveBusinessUnit {
    id: number;
    code: string;
    name: string;
    logo: string | null;
    total: number;
    completed: number;
    in_progress: number;
    planned: number;
    overdue: number;
    completed_this_month: number;
    completion_rate: number;
}

interface ExecutiveStats {
    aggregate: Stats & { total_business_units: number };
    businessUnits: ExecutiveBusinessUnit[];
    topOverdueDepartments: Array<{
        departmentId: number;
        department: string;
        businessUnitId: number;
        businessUnit: string;
        overdueCount: number;
    }>;
}

interface DashboardProps extends PageProps {
    personalStats: Stats;
    personalVisuals: PersonalVisuals;
    departmentStats: Stats | null;
    departmentVisuals: DepartmentVisuals | null;
    canViewReports?: boolean;
    executiveStats?: ExecutiveStats | null;
    queryParams?: {
        tab?: string;
        page?: string;
        dept_tab?: string;
        dept_page?: string;
        distribution_period?: PeriodFilter;
        dept_distribution_period?: PeriodFilter;
    };
}

const smoothTransition = { duration: 0.35, ease: "easeInOut" as const };
const FOCUS_COLORS = ['#3b82f6', '#8b5cf6', '#f59e0b', '#10b981'];

export default function ActivityDashboard({
    personalStats,
    personalVisuals,
    departmentStats,
    departmentVisuals,
    canViewReports,
    executiveStats,
    queryParams
}: DashboardProps) {
    const hasExecutive = !!(canViewReports && executiveStats);
    const [viewMode, setViewMode] = useState<'personal' | 'department' | 'executive'>(
        hasExecutive ? 'executive' : departmentStats ? 'department' : 'personal'
    );
    const [distributionPeriod, setDistributionPeriod] = useState<PeriodFilter>(
        (queryParams?.distribution_period as PeriodFilter) || 'all'
    );
    const [deptDistributionPeriod, setDeptDistributionPeriod] = useState<PeriodFilter>(
        (queryParams?.dept_distribution_period as PeriodFilter) || 'all'
    );
    const [isDistributionLoading, setIsDistributionLoading] = useState(false);
    const [isDeptDistributionLoading, setIsDeptDistributionLoading] = useState(false);

    /** Navigate to admin dashboard for a specific BU, switching context first */
    const navigateToAdminDashboard = (businessUnitId: number, departmentId?: number) => {
        // Switch BU context then redirect to admin dashboard
        router.post(route('api.business-unit.switch'), { business_unit_id: businessUnitId }, {
            preserveState: false,
            onSuccess: () => {
                const params: Record<string, string> = {};
                if (departmentId) {
                    params.department_id = String(departmentId);
                }
                router.visit(route('activity.admin.dashboard', params));
            },
        });
    };

    const periodQuickFilters: Array<{ key: PeriodFilter; label: string }> = [
        { key: 'today', label: 'Day' },
        { key: 'week', label: 'Week' },
        { key: 'month', label: 'Month' },
    ];

    const activeStats = viewMode === 'department' && departmentStats ? departmentStats : personalStats;
    const activeVisuals = viewMode === 'department' && departmentVisuals ? departmentVisuals : personalVisuals;
    const roadmapData = activeVisuals?.roadmap?.data || [];

    const completionRate = activeStats.total > 0
        ? Math.round((activeStats.completed / activeStats.total) * 100)
        : 0;

    const trackedDurationMinutes = roadmapData.reduce((sum, task) => {
        const duration = Number(task.duration_minutes || 0);
        return sum + (Number.isFinite(duration) ? duration : 0);
    }, 0);

    const fallbackDurationMinutes =
        (activeStats.completed * 95) +
        (activeStats.in_progress * 60) +
        (Math.max(activeStats.total - activeStats.completed - activeStats.in_progress, 0) * 30);

    const totalDurationMinutes = trackedDurationMinutes > 0 ? trackedDurationMinutes : fallbackDurationMinutes;
    const totalHours = Math.floor(totalDurationMinutes / 60);
    const totalMinutes = totalDurationMinutes % 60;
    const activeProjects = activeVisuals?.distribution?.length || 0;

    const statusVerbMap: Record<string, string> = {
        in_progress: 'Working on',
        planned: 'Planning',
        completed: 'Completed',
    };

    const memberActivityRows = useMemo(() => {
        const bucket = new Map<string, {
            id: string;
            name: string;
            role: string;
            activity: string;
            status: string;
            totalMinutes: number;
        }>();

        roadmapData.forEach((task, taskIndex) => {
            const participants = Array.isArray(task.participants) && task.participants.length > 0
                ? task.participants
                : [{ id: `self-${task.id}`, name: 'You' }];
            const duration = Number(task.duration_minutes || 0) || (task.status === 'completed' ? 90 : 45);
            const title = task.task_title || task.title || 'Task';
            const verb = statusVerbMap[task.status || 'planned'] || 'Working on';
            const activityLabel = `${verb}: ${title}`;

            participants.forEach((member: any, index) => {
                const memberName = member.name || member.user?.name || `Member ${taskIndex + index + 1}`;
                const memberRole = member.primary_position?.name || 'Team Member';
                const memberId = String(member.id || member.user_id || `${task.id}-${memberName}`);
                const existing = bucket.get(memberId);

                if (!existing) {
                    bucket.set(memberId, {
                        id: memberId,
                        name: memberName,
                        role: memberRole,
                        activity: activityLabel,
                        status: task.status || 'planned',
                        totalMinutes: duration,
                    });
                    return;
                }

                bucket.set(memberId, {
                    ...existing,
                    totalMinutes: existing.totalMinutes + duration,
                    activity: activityLabel,
                    status: task.status || existing.status,
                });
            });
        });

        return Array.from(bucket.values())
            .sort((a, b) => b.totalMinutes - a.totalMinutes)
            .slice(0, 5);
    }, [roadmapData]);

    const totalDistribution = (activeVisuals?.distribution || []).reduce((sum, item) => sum + Number(item.value || 0), 0);
    const focusItems = [...(activeVisuals?.distribution || [])]
        .sort((a, b) => Number(b.value || 0) - Number(a.value || 0))
        .slice(0, 4)
        .map((item, index) => ({
            ...item,
            color: item.color || FOCUS_COLORS[index % FOCUS_COLORS.length],
            percentage: totalDistribution > 0 ? Math.round((Number(item.value || 0) / totalDistribution) * 100) : 0,
        }));

    const leadFocus = focusItems[0];

    const generateInsight = (focus: typeof leadFocus, viewMode: 'personal' | 'department' | 'executive') => {
        if (!focus || focus.percentage === 0) {
            return 'No insight available yet. Complete more tasks to build recommendations.';
        }

        const name = focus.name;
        const p = focus.percentage;

        if (viewMode === 'department') {
            if (p > 60) return `Warning: Department workload is heavily concentrated on ${name} (${p}%). High risk of bottleneck, consider reassigning tasks to balance capacity.`;
            if (p > 40) return `${name} is currently dominating the department's focus (${p}%). Ensure other strategic priorities are not being neglected.`;
            if (p > 25) return `Department focus is relatively balanced, with ${name} leading slightly at ${p}%. This indicates healthy task distribution.`;
            return `Department workload is highly diversified. ${name} leads with only ${p}%, indicating a wide spread of active projects.`;
        } else {
            if (p > 60) return `Warning: Your personal workload is heavily focused on ${name} (${p}%). Be careful of burnout in this area and consider delegating if possible.`;
            if (p > 40) return `You are currently dedicating ${p}% of your effort to ${name}. Ensure this aligns with your primary objectives for this period.`;
            if (p > 25) return `Your time is fairly balanced, with ${name} taking up ${p}%. This is a good mix of responsibilities.`;
            return `You are juggling multiple priorities. ${name} is your top focus but only takes ${p}%, indicating highly fragmented attention.`;
        }
    };

    // Handlers for period changes
    const handleDistributionPeriodChange = (period: PeriodFilter) => {
        setDistributionPeriod(period);
        setIsDistributionLoading(true);
        router.get(
            route('activity.dashboard'),
            { distribution_period: period },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['personalVisuals', 'personalStats', 'queryParams'],
                onFinish: () => setIsDistributionLoading(false)
            }
        );
    };

    const handleDeptDistributionPeriodChange = (period: PeriodFilter) => {
        setDeptDistributionPeriod(period);
        setIsDeptDistributionLoading(true);
        router.get(
            route('activity.dashboard'),
            { dept_distribution_period: period },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['departmentVisuals', 'departmentStats', 'queryParams'],
                onFinish: () => setIsDeptDistributionLoading(false)
            }
        );
    };

    return (
        <>
            <Head title="Activity Dashboard" />
            <div className="w-full font-sans text-slate-900 pb-12">
                <main className="w-full px-6 py-6 lg:px-8">

                    {/* Filter Action Bar - Modern clean card */}
                    <div className="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between bg-white border border-slate-200/60 shadow-sm rounded-xl px-4 py-3">
                        <div className="flex flex-wrap items-center gap-4">
                            {/* Context Switch Segmented Control */}
                            <div className="flex bg-slate-100/80 p-1 rounded-lg border border-slate-200/50">
                                {hasExecutive && (
                                    <button
                                        onClick={() => setViewMode('executive')}
                                        className={cn(
                                            'rounded-md px-4 py-1.5 text-sm font-medium transition-all duration-200',
                                            viewMode === 'executive'
                                                ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-900/5'
                                                : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'
                                        )}
                                    >
                                        <span className="flex items-center gap-1.5">
                                            <Building2 className="h-3.5 w-3.5" />
                                            Executive
                                        </span>
                                    </button>
                                )}
                                <button
                                    onClick={() => setViewMode('personal')}
                                    className={cn(
                                        'rounded-md px-4 py-1.5 text-sm font-medium transition-all duration-200',
                                        viewMode === 'personal'
                                            ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-900/5'
                                            : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'
                                    )}
                                >
                                    Personal
                                </button>
                                {departmentStats && (
                                    <button
                                        onClick={() => setViewMode('department')}
                                        className={cn(
                                            'rounded-md px-4 py-1.5 text-sm font-medium transition-all duration-200',
                                            viewMode === 'department'
                                                ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-900/5'
                                                : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'
                                        )}
                                    >
                                        Department
                                    </button>
                                )}
                            </div>

                            {/* Divider line for larger screens */}
                            {viewMode !== 'executive' && (
                                <div className="hidden md:block w-px h-6 bg-slate-200" />
                            )}

                            {/* Period Filter Segmented Control - hidden in executive mode */}
                            {viewMode !== 'executive' && (
                            <div className="flex bg-slate-100/80 p-1 rounded-lg border border-slate-200/50">
                                {periodQuickFilters.map((period) => {
                                    const activePeriod = viewMode === 'personal' ? distributionPeriod : deptDistributionPeriod;
                                    return (
                                        <button
                                            key={period.key}
                                            onClick={() => viewMode === 'personal' ? handleDistributionPeriodChange(period.key) : handleDeptDistributionPeriodChange(period.key)}
                                            className={cn(
                                                'rounded-md px-4 py-1.5 text-sm font-medium transition-all duration-200',
                                                activePeriod === period.key
                                                    ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-900/5'
                                                    : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'
                                            )}
                                        >
                                            {period.label}
                                        </button>
                                    );
                                })}
                            </div>
                            )}
                        </div>

                        {viewMode !== 'executive' && (
                        <div className="flex items-center gap-3">
                            <button className="flex items-center justify-center rounded-lg bg-white border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                                <Filter className="mr-2 h-4 w-4 text-slate-500" />
                                Filter
                            </button>
                            <Link href={route('activity.task.export', { scope: viewMode === 'personal' ? 'my' : 'department' })}>
                                <button className="flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-600">
                                    <Download className="mr-2 h-4 w-4" />
                                    Export Report
                                </button>
                            </Link>
                        </div>
                        )}
                    </div>

                    {/* Dashboard Content */}
                    <AnimatePresence mode="wait" initial={false}>
                        <motion.div
                            key={viewMode}
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            exit={{ opacity: 0, y: -10 }}
                            transition={smoothTransition}
                            className="space-y-6"
                        >

                        {/* ═══════ EXECUTIVE VIEW ═══════ */}
                        {viewMode === 'executive' && executiveStats && (
                            <>
                                {/* Aggregate KPI Cards */}
                                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                                    {/* Total Tasks */}
                                    <div className="rounded-xl border border-slate-200/60 bg-white p-6 shadow-sm flex flex-col">
                                        <div className="flex items-center gap-3 mb-4">
                                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                                <BarChart3 className="h-5 w-5" strokeWidth={2} />
                                            </div>
                                            <h3 className="text-sm font-medium text-slate-500">Total Tasks</h3>
                                        </div>
                                        <div className="text-3xl font-bold tracking-tight text-slate-900 mb-2">
                                            {executiveStats.aggregate.total.toLocaleString('id-ID')}
                                        </div>
                                        <div className="mt-auto text-sm text-slate-500">
                                            Across <span className="font-medium text-slate-700">{executiveStats.aggregate.total_business_units}</span> business units
                                        </div>
                                    </div>

                                    {/* Completion Rate */}
                                    <div className="rounded-xl border border-slate-200/60 bg-white p-6 shadow-sm flex flex-col">
                                        <div className="flex items-center gap-3 mb-4">
                                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                                <TrendingUp className="h-5 w-5" strokeWidth={2} />
                                            </div>
                                            <h3 className="text-sm font-medium text-slate-500">Completion Rate</h3>
                                        </div>
                                        <div className="text-3xl font-bold tracking-tight text-slate-900 mb-2">
                                            {executiveStats.aggregate.total > 0
                                                ? Math.round((executiveStats.aggregate.completed / executiveStats.aggregate.total) * 100)
                                                : 0}%
                                        </div>
                                        <div className="mt-auto text-sm text-slate-500">
                                            <span className="font-medium text-emerald-600">{executiveStats.aggregate.completed.toLocaleString('id-ID')}</span> of {executiveStats.aggregate.total.toLocaleString('id-ID')} completed
                                        </div>
                                    </div>

                                    {/* Overdue Alert */}
                                    <div className={cn(
                                        "rounded-xl border p-6 shadow-sm flex flex-col",
                                        executiveStats.aggregate.overdue > 0
                                            ? "border-red-200/60 bg-red-50/30"
                                            : "border-slate-200/60 bg-white"
                                    )}>
                                        <div className="flex items-center gap-3 mb-4">
                                            <div className={cn(
                                                "flex h-10 w-10 items-center justify-center rounded-lg",
                                                executiveStats.aggregate.overdue > 0
                                                    ? "bg-red-100 text-red-600"
                                                    : "bg-slate-50 text-slate-400"
                                            )}>
                                                <AlertTriangle className="h-5 w-5" strokeWidth={2} />
                                            </div>
                                            <h3 className="text-sm font-medium text-slate-500">Overdue Tasks</h3>
                                        </div>
                                        <div className={cn(
                                            "text-3xl font-bold tracking-tight mb-2",
                                            executiveStats.aggregate.overdue > 0 ? "text-red-600" : "text-slate-900"
                                        )}>
                                            {executiveStats.aggregate.overdue.toLocaleString('id-ID')}
                                        </div>
                                        <div className="mt-auto text-sm text-slate-500">
                                            {executiveStats.aggregate.overdue > 0
                                                ? 'Requires immediate attention'
                                                : 'All tasks are on schedule'}
                                        </div>
                                    </div>

                                    {/* In Progress */}
                                    <div className="rounded-xl border border-slate-200/60 bg-white p-6 shadow-sm flex flex-col">
                                        <div className="flex items-center gap-3 mb-4">
                                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                                                <Activity className="h-5 w-5" strokeWidth={2} />
                                            </div>
                                            <h3 className="text-sm font-medium text-slate-500">In Progress</h3>
                                        </div>
                                        <div className="text-3xl font-bold tracking-tight text-slate-900 mb-2">
                                            {executiveStats.aggregate.in_progress.toLocaleString('id-ID')}
                                        </div>
                                        <div className="mt-auto text-sm text-slate-500">
                                            <span className="font-medium text-slate-700">{executiveStats.aggregate.planned}</span> planned tasks pending
                                        </div>
                                    </div>
                                </div>

                                {/* Per-BU Breakdown */}
                                <div className="rounded-xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                                    <div className="flex items-center justify-between border-b border-slate-100 px-6 py-5 bg-white">
                                        <div className="flex items-center gap-3">
                                            <Building2 className="h-5 w-5 text-slate-400" />
                                            <h3 className="text-base font-semibold text-slate-900">Business Unit Overview</h3>
                                        </div>
                                        <span className="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-1 rounded-md">
                                            {executiveStats.businessUnits.length} units
                                        </span>
                                    </div>

                                    <div className="divide-y divide-slate-100">
                                        {executiveStats.businessUnits.length > 0 ? executiveStats.businessUnits.map((bu) => (
                                            <div
                                                key={bu.id}
                                                onClick={() => navigateToAdminDashboard(bu.id)}
                                                className="group flex flex-col gap-4 px-6 py-5 transition-colors hover:bg-blue-50/50 cursor-pointer sm:flex-row sm:items-center"
                                                title={`View ${bu.name} activity details`}
                                            >
                                                {/* BU Identity */}
                                                <div className="flex items-center gap-4 sm:w-[200px] shrink-0">
                                                    {bu.logo ? (
                                                        <img
                                                            src={bu.logo}
                                                            alt={bu.code}
                                                            className="h-10 w-10 shrink-0 rounded-lg object-contain bg-white border border-slate-200 p-1"
                                                        />
                                                    ) : (
                                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white text-xs font-bold">
                                                            {bu.code}
                                                        </div>
                                                    )}
                                                    <div className="min-w-0">
                                                        <p className="text-sm font-semibold text-slate-900 truncate">{bu.name}</p>
                                                        <p className="text-xs text-slate-500 mt-0.5">{bu.total.toLocaleString('id-ID')} total tasks</p>
                                                    </div>
                                                </div>

                                                {/* Completion Bar */}
                                                <div className="flex-1 flex items-center gap-4">
                                                    <div className="flex-1">
                                                        <div className="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                                                            <motion.div
                                                                initial={{ width: 0 }}
                                                                animate={{ width: `${bu.completion_rate}%` }}
                                                                transition={{ duration: 0.8, ease: "easeOut" }}
                                                                className={cn(
                                                                    "h-full rounded-full",
                                                                    bu.completion_rate >= 80 ? "bg-emerald-500" :
                                                                    bu.completion_rate >= 50 ? "bg-amber-500" : "bg-red-500"
                                                                )}
                                                            />
                                                        </div>
                                                    </div>
                                                    <span className="text-sm font-semibold text-slate-900 w-12 text-right tabular-nums">
                                                        {bu.completion_rate}%
                                                    </span>
                                                </div>

                                                {/* Stats Badges */}
                                                <div className="flex items-center gap-2 sm:w-auto shrink-0">
                                                    <span className="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/10">
                                                        ✓ {bu.completed}
                                                    </span>
                                                    <span className="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/10">
                                                        ⏳ {bu.in_progress}
                                                    </span>
                                                    {bu.overdue > 0 && (
                                                        <span className="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">
                                                            ⚠ {bu.overdue}
                                                        </span>
                                                    )}
                                                </div>

                                                {/* Navigate Arrow */}
                                                <ChevronRight className="h-4 w-4 text-slate-300 group-hover:text-blue-500 transition-colors shrink-0" />
                                            </div>
                                        )) : (
                                            <div className="flex flex-col items-center justify-center py-16 text-center">
                                                <div className="rounded-full bg-slate-50 p-4 border border-slate-100 mb-4">
                                                    <Building2 className="h-6 w-6 text-slate-400" />
                                                </div>
                                                <h4 className="text-sm font-semibold text-slate-900">No business unit data</h4>
                                                <p className="mt-1 text-sm text-slate-500">Activity data will appear here once tasks are logged</p>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Top Overdue Departments - Attention List */}
                                {executiveStats.topOverdueDepartments.length > 0 && (
                                    <div className="rounded-xl border border-red-200/60 bg-red-50/20 shadow-sm overflow-hidden">
                                        <div className="flex items-center gap-3 border-b border-red-100 px-6 py-4 bg-red-50/40">
                                            <AlertTriangle className="h-4 w-4 text-red-500" />
                                            <h3 className="text-sm font-semibold text-red-900">Departments Requiring Attention</h3>
                                        </div>
                                        <div className="divide-y divide-red-100/60">
                                            {executiveStats.topOverdueDepartments.map((dept, index) => (
                                                <div
                                                    key={`${dept.departmentId}-${dept.businessUnitId}`}
                                                    onClick={() => navigateToAdminDashboard(dept.businessUnitId, dept.departmentId)}
                                                    className="group flex items-center justify-between px-6 py-3.5 hover:bg-red-50/60 cursor-pointer transition-colors"
                                                    title={`View ${dept.department} details in ${dept.businessUnit}`}
                                                >
                                                    <div className="flex items-center gap-3">
                                                        <span className="flex h-6 w-6 items-center justify-center rounded-full bg-red-100 text-xs font-bold text-red-700">
                                                            {index + 1}
                                                        </span>
                                                        <div>
                                                            <p className="text-sm font-medium text-slate-900">{dept.department}</p>
                                                            <p className="text-xs text-slate-500">{dept.businessUnit}</p>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="inline-flex items-center rounded-md bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                                            {dept.overdueCount} overdue
                                                        </span>
                                                        <ChevronRight className="h-4 w-4 text-red-300 group-hover:text-red-500 transition-colors" />
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </>
                        )}

                        {/* ═══════ PERSONAL / DEPARTMENT VIEW ═══════ */}
                        {viewMode !== 'executive' && (
                            <>
                            {/* Key Metrics Grid */}
                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {/* Total Hours Card */}
                                <div className="rounded-xl border border-slate-200/60 bg-white p-6 shadow-sm flex flex-col">
                                    <div className="flex items-center gap-3 mb-4">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                            <Clock className="h-5 w-5" strokeWidth={2} />
                                        </div>
                                        <h3 className="text-sm font-medium text-slate-500">
                                            {viewMode === 'department' ? 'Team Total Hours' : 'My Total Hours'}
                                        </h3>
                                    </div>
                                    <div className="text-3xl font-bold tracking-tight text-slate-900 mb-2">
                                        {totalHours}h <span className="text-2xl text-slate-500 font-semibold">{String(totalMinutes).padStart(2, '0')}m</span>
                                    </div>
                                    <div className="mt-auto flex items-center text-sm">
                                        <span className="font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded mr-2">
                                            +{Math.max(activeStats.completed_this_month || 0, 0)} tasks
                                        </span>
                                        <span className="text-slate-500">completed this period</span>
                                    </div>
                                </div>

                                {/* Active Projects Card */}
                                <div className="rounded-xl border border-slate-200/60 bg-white p-6 shadow-sm flex flex-col">
                                    <div className="flex items-center gap-3 mb-4">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                                            <Briefcase className="h-5 w-5" strokeWidth={2} />
                                        </div>
                                        <h3 className="text-sm font-medium text-slate-500">Active Projects</h3>
                                    </div>
                                    <div className="text-3xl font-bold tracking-tight text-slate-900 mb-2">
                                        {activeProjects}
                                    </div>
                                    <div className="mt-auto flex items-center text-sm text-slate-500">
                                        <span className="font-medium text-slate-700 mr-1">{activeStats.in_progress}</span> task(s) currently in progress
                                    </div>
                                </div>

                                {/* Avg Efficiency Card */}
                                <div className="rounded-xl border border-slate-200/60 bg-white p-6 shadow-sm flex flex-col relative overflow-hidden">
                                    <div className="flex items-center gap-3 mb-4 relative z-10">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                                            <TrendingUp className="h-5 w-5" strokeWidth={2} />
                                        </div>
                                        <h3 className="text-sm font-medium text-slate-500">Avg. Efficiency</h3>
                                    </div>
                                    <div className="text-3xl font-bold tracking-tight text-slate-900 mb-2 relative z-10">
                                        {completionRate}%
                                    </div>
                                    <div className="mt-auto flex items-center text-sm text-slate-500 relative z-10">
                                        <span className="font-medium text-slate-700 mr-1">{activeStats.completed}</span> of {activeStats.total} tasks completed
                                    </div>
                                    
                                    {/* Decorative mini bar chart in background */}
                                    <div className="absolute right-6 bottom-6 flex items-end gap-1.5 opacity-40">
                                        {[45, 60, 75, 50, completionRate, 80, 65].map((val, i) => (
                                            <div
                                                key={i}
                                                className={cn(
                                                    "w-2 rounded-t-sm transition-all",
                                                    i === 4 ? "bg-blue-500" : "bg-slate-200"
                                                )}
                                                style={{ height: `${Math.max(val / 2, 10)}px` }}
                                            />
                                        ))}
                                    </div>
                                </div>
                            </div>

                            {/* Detailed Sections Grid */}
                            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                                {/* Member Activity - Takes up 2 columns */}
                                <div className="rounded-xl border border-slate-200/60 bg-white shadow-sm lg:col-span-2 overflow-hidden flex flex-col">
                                    <div className="flex items-center justify-between border-b border-slate-100 px-6 py-5 bg-white">
                                        <h3 className="text-base font-semibold text-slate-900">
                                            {viewMode === 'department' ? 'Team Activity' : 'My Activity'}
                                        </h3>
                                        <span className="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-1 rounded-md">Real-time status</span>
                                    </div>

                                    <div className="flex-1 flex flex-col">
                                        {memberActivityRows.length > 0 ? (
                                            <div className="flex flex-col">
                                                {memberActivityRows.map((member) => {
                                                    const statusColor = member.status === 'completed'
                                                        ? 'bg-emerald-500' // Green
                                                        : member.status === 'in_progress'
                                                            ? 'bg-amber-500' // Orange
                                                            : 'bg-slate-300'; // Gray for planned
                                                    const memberHours = Math.floor(member.totalMinutes / 60);

                                                    return (
                                                        <div key={member.id} className="group flex flex-col gap-3 px-6 py-4 transition-colors hover:bg-slate-50/70 border-b border-slate-100 last:border-0 sm:flex-row sm:items-center sm:justify-between">
                                                            
                                                            {/* User Info */}
                                                            <div className="flex items-center gap-4 sm:w-[40%]">
                                                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white text-sm font-semibold ring-2 ring-white">
                                                                    {member.name.charAt(0).toUpperCase()}
                                                                </div>
                                                                <div className="min-w-0">
                                                                    <p className="truncate text-sm font-semibold text-slate-900">{member.name}</p>
                                                                    <p className="text-xs text-slate-500 mt-0.5">{member.role}</p>
                                                                </div>
                                                            </div>

                                                            {/* Activity & Duration */}
                                                            <div className="flex flex-1 items-center justify-between sm:justify-end sm:gap-8 mt-2 sm:mt-0">
                                                                <div className="flex items-center gap-2.5">
                                                                    <span className={cn('h-2 w-2 shrink-0 rounded-full', statusColor)} />
                                                                    <span className="truncate text-sm text-slate-600 max-w-[200px] sm:max-w-[260px]">{member.activity}</span>
                                                                </div>
                                                                <div className="text-sm font-semibold text-slate-900 w-16 text-right tabular-nums">
                                                                    {memberHours}h {String(member.totalMinutes % 60).padStart(2, '0')}m
                                                                </div>
                                                            </div>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        ) : (
                                            <div className="flex flex-1 flex-col items-center justify-center py-16 text-center">
                                                <div className="rounded-full bg-slate-50 p-4 border border-slate-100 mb-4">
                                                    <Activity className="h-6 w-6 text-slate-400" />
                                                </div>
                                                <h4 className="text-sm font-semibold text-slate-900">No activity recorded yet</h4>
                                                <p className="mt-1 text-sm text-slate-500">Track tasks to see member updates here</p>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Department Focus - Takes up 1 column */}
                                <div className="flex flex-col gap-6">
                                    <div className="rounded-xl border border-slate-200/60 bg-white p-6 shadow-sm">
                                        <div className="mb-6 flex items-center justify-between">
                                            <h3 className="text-base font-semibold text-slate-900">
                                            {viewMode === 'department' ? 'Department Focus' : 'Personal Focus'}
                                        </h3>
                                            <span className="text-xs font-medium text-slate-500">Workload allocation</span>
                                        </div>

                                        <div className={cn('space-y-6', isDistributionLoading && 'opacity-50 transition-opacity')}>
                                            {focusItems.length > 0 ? focusItems.map((item, index) => (
                                                <div key={`${item.name}-${index}`}>
                                                    <div className="mb-2.5 flex items-center justify-between">
                                                        <span className="text-sm font-medium text-slate-700">{item.name}</span>
                                                        <span className="text-sm font-semibold text-slate-900">{item.percentage}%</span>
                                                    </div>
                                                    <div className="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                                        <motion.div
                                                            initial={{ width: 0 }}
                                                            animate={{ width: `${item.percentage}%` }}
                                                            transition={{ duration: 0.8, ease: "easeOut" }}
                                                            className="h-full rounded-full"
                                                            style={{
                                                                backgroundColor: item.color || FOCUS_COLORS[index % FOCUS_COLORS.length],
                                                            }}
                                                        />
                                                    </div>
                                                </div>
                                            )) : (
                                                <div className="rounded-lg border border-dashed border-slate-200 bg-slate-50 py-10 text-center">
                                                    <p className="text-sm text-slate-500">No distribution data available</p>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Productivity Insight */}
                                    <div className="rounded-xl bg-blue-50 border border-blue-100 p-5">
                                        <div className="flex items-start gap-3">
                                            <div className="mt-0.5 text-blue-600 bg-blue-100 p-1.5 rounded-md">
                                                <TrendingUp className="h-4 w-4" strokeWidth={2.5} />
                                            </div>
                                            <div>
                                                <h4 className="text-sm font-semibold text-blue-900">Productivity Insight</h4>
                                                <p className="mt-1.5 text-sm leading-relaxed text-blue-700">
                                                    {generateInsight(leadFocus, viewMode)}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </>
                        )}
                        </motion.div>
                    </AnimatePresence>
                </main>
            </div>
        </>
    );
}