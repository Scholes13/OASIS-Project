import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    Activity,
    CheckCircle2,
    Clock,
    AlertTriangle,
    TrendingUp,
    Users,
    Calendar,
    ArrowRight,
    Plus,
    List,
    Layout,
    ChevronLeft,
    ChevronRight
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip as RechartsTooltip } from 'recharts';
import { format, isToday, isTomorrow, parseISO, differenceInDays } from 'date-fns';

interface TaskBasic {
    id: number;
    title: string;
    due_date: string;
    is_critical?: boolean;
    status?: string;
    task_description?: string;
    activity_type?: { name: string; color: string };
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

interface DashboardProps extends PageProps {
    personalStats: Stats;
    personalVisuals: PersonalVisuals;
    departmentStats: Stats | null;
    departmentVisuals: DepartmentVisuals | null;
    canViewReports?: boolean;
    queryParams?: { tab?: string; page?: string; dept_tab?: string; dept_page?: string };
}

const smoothTransition = { duration: 0.35, ease: "easeInOut" as const };
const COLORS = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

export default function ActivityDashboard({
    personalStats,
    personalVisuals,
    departmentStats,
    departmentVisuals,
    queryParams
}: DashboardProps) {
    const [viewMode, setViewMode] = useState<'personal' | 'department'>('personal');
    const [roadmapTab, setRoadmapTab] = useState<'todo' | 'inprogress' | 'review'>(
        (queryParams?.tab as 'todo' | 'inprogress' | 'review') || 'todo'
    );
    const [deptRoadmapTab, setDeptRoadmapTab] = useState<'todo' | 'inprogress' | 'review'>(
        (queryParams?.dept_tab as 'todo' | 'inprogress' | 'review') || 'todo'
    );

    const activeRoadmapList = personalVisuals?.roadmap?.data || [];
    const pagination = personalVisuals?.roadmap;
    const activeDeptRoadmapList = departmentVisuals?.roadmap?.data || [];
    const deptPagination = departmentVisuals?.roadmap;

    const handleTabChange = (tab: 'todo' | 'inprogress' | 'review') => {
        setRoadmapTab(tab);
        router.get(route('activity.dashboard'), { tab, page: 1 }, { preserveState: true, preserveScroll: true, only: ['personalVisuals', 'queryParams'] });
    };

    const handlePageChange = (url: string | null) => {
        if (!url) return;
        router.get(url, { tab: roadmapTab }, { preserveState: true, preserveScroll: true, only: ['personalVisuals', 'queryParams'] });
    };

    const handleDeptTabChange = (tab: 'todo' | 'inprogress' | 'review') => {
        setDeptRoadmapTab(tab);
        router.get(route('activity.dashboard'), { dept_tab: tab, dept_page: 1 }, { preserveState: true, preserveScroll: true, only: ['departmentVisuals', 'queryParams'] });
    };

    const handleDeptPageChange = (url: string | null) => {
        if (!url) return;
        router.get(url, { dept_tab: deptRoadmapTab }, { preserveState: true, preserveScroll: true, only: ['departmentVisuals', 'queryParams'] });
    };

    return (
        <>
            <Head title="Activity Dashboard" />
            <div className="min-h-screen bg-slate-50">
                <div className="w-full px-4 sm:px-6 lg:px-8 py-6">
                    {/* Header with Switcher */}
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                        <div>
                            <h1 className="text-3xl font-semibold text-gray-900">Activity Dashboard</h1>
                            <p className="mt-1 text-base text-gray-500">
                                {viewMode === 'personal' ? "Your personal task roadmap & insights" : "Department workload & performance overview"}
                            </p>
                        </div>
                        <div className="flex items-center gap-3">
                            <div className="bg-white p-1 rounded-lg border border-gray-200 flex shadow-sm">
                                <button
                                    onClick={() => setViewMode('personal')}
                                    className={cn(
                                        "px-4 py-1.5 text-base font-medium rounded-md transition-all flex items-center gap-2",
                                        viewMode === 'personal' ? "bg-indigo-50 text-indigo-700 shadow-sm" : "text-gray-500 hover:text-gray-900"
                                    )}
                                >
                                    <Users className="h-4 w-4" />
                                    Personal
                                </button>
                                {departmentStats && (
                                    <button
                                        onClick={() => setViewMode('department')}
                                        className={cn(
                                            "px-4 py-1.5 text-base font-medium rounded-md transition-all flex items-center gap-2",
                                            viewMode === 'department' ? "bg-indigo-50 text-indigo-700 shadow-sm" : "text-gray-500 hover:text-gray-900"
                                        )}
                                    >
                                        <Layout className="h-4 w-4" />
                                        Department
                                    </button>
                                )}
                            </div>
                            <Link href={route('activity.task.create')}>
                                <Button className="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm rounded-full px-4">
                                    <Plus className="h-4 w-4 mr-1.5" strokeWidth={2.5} />
                                    New Task
                                </Button>
                            </Link>
                        </div>
                    </div>

                    <AnimatePresence mode="wait">
                        {viewMode === 'personal' ? (
                            <motion.div
                                key="personal"
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                exit={{ opacity: 0, y: -10 }}
                                transition={smoothTransition}
                                className="space-y-6"
                            >
                                {/* Row 1: Stats Cards */}
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div className={cn(
                                        "rounded-xl p-5 border shadow-sm flex items-center justify-between",
                                        personalStats.overdue > 0 ? "bg-red-50 border-red-200" : "bg-white border-gray-200"
                                    )}>
                                        <div>
                                            <p className={cn("text-base font-semibold", personalStats.overdue > 0 ? "text-red-700" : "text-gray-500")}>Overdue Tasks</p>
                                            <p className={cn("text-3xl font-bold mt-1", personalStats.overdue > 0 ? "text-red-700" : "text-gray-900")}>{personalStats.overdue}</p>
                                            {personalStats.overdue > 0 && (
                                                <p className="text-sm text-red-600 mt-1 font-medium bg-red-100 px-2 py-0.5 rounded-full inline-block">Action Required</p>
                                            )}
                                        </div>
                                        <div className={cn("p-3 rounded-lg border", personalStats.overdue > 0 ? "bg-red-100 border-red-200" : "bg-gray-50 border-gray-100")}>
                                            <AlertTriangle className={cn("h-6 w-6", personalStats.overdue > 0 ? "text-red-600" : "text-gray-400")} />
                                        </div>
                                    </div>
                                    <div className="bg-white rounded-xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
                                        <div>
                                            <p className="text-base font-semibold text-gray-500">Due This Week</p>
                                            <p className="text-3xl font-bold text-gray-900 mt-1">{personalStats.in_progress + (personalStats.planned || 0)}</p>
                                            <p className="text-sm text-amber-700 mt-1 font-medium bg-amber-50 px-2 py-0.5 rounded-full inline-block border border-amber-100">Active Tasks</p>
                                        </div>
                                        <div className="p-3 bg-amber-50 rounded-lg border border-amber-100">
                                            <Clock className="h-6 w-6 text-amber-600" />
                                        </div>
                                    </div>
                                    <div className="bg-white rounded-xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
                                        <div>
                                            <p className="text-base font-semibold text-gray-500">Completion Rate</p>
                                            <div className="flex items-baseline gap-2 mt-1">
                                                <p className="text-3xl font-bold text-emerald-600">
                                                    {personalStats.total > 0 ? Math.round((personalStats.completed / personalStats.total) * 100) : 0}%
                                                </p>
                                                <span className="text-sm text-gray-400">this month</span>
                                            </div>
                                            <p className="text-sm text-emerald-700 mt-1 font-medium flex items-center">
                                                <TrendingUp className="h-3 w-3 mr-1" />
                                                {personalStats.completed} tasks done
                                            </p>
                                        </div>
                                        <div className="h-14 w-14 relative flex items-center justify-center">
                                            <svg className="h-full w-full transform -rotate-90">
                                                <circle cx="28" cy="28" r="22" stroke="#f3f4f6" strokeWidth="4" fill="none" />
                                                <circle cx="28" cy="28" r="22" stroke="#10b981" strokeWidth="4" fill="none" strokeDasharray={138} strokeDashoffset={138 - (138 * (personalStats.total > 0 ? (personalStats.completed / personalStats.total) : 0))} className="transition-all duration-1000 ease-out" strokeLinecap="round" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                {/* Row 2: Main Content */}
                                <div className="grid grid-cols-1 lg:grid-cols-10 gap-6 items-stretch">
                                    <div className="lg:col-span-7 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col">
                                        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                            <h2 className="text-xl font-bold text-gray-900 flex items-center gap-2">
                                                <List className="h-5 w-5 text-indigo-600" />
                                                My Task Roadmap
                                            </h2>
                                            <div className="flex bg-gray-100 p-1 rounded-lg">
                                                {(['todo', 'inprogress', 'review'] as const).map(tab => (
                                                    <button
                                                        key={tab}
                                                        onClick={() => handleTabChange(tab)}
                                                        className={cn(
                                                            "px-3 py-1.5 text-sm font-semibold rounded-md capitalize transition-all",
                                                            roadmapTab === tab ? "bg-white text-gray-900 shadow-sm" : "text-gray-500 hover:text-gray-700"
                                                        )}
                                                    >
                                                        {tab.replace('todo', 'To Do').replace('inprogress', 'In Progress')}
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                        <div className="p-0 flex-1 overflow-auto max-h-[520px]">
                                            {activeRoadmapList.length > 0 ? (
                                                <table className="w-full text-left">
                                                    <thead className="bg-gray-50 text-sm text-gray-400 uppercase tracking-wider sticky top-0 z-10">
                                                        <tr>
                                                            <th className="px-6 py-3 font-medium border-b border-gray-100">Task Name</th>
                                                            <th className="px-6 py-3 font-medium border-b border-gray-100">Category</th>
                                                            <th className="px-6 py-3 font-medium border-b border-gray-100">Deadline</th>
                                                            <th className="px-6 py-3 font-medium text-right border-b border-gray-100">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="divide-y divide-gray-100 text-base">
                                                        {activeRoadmapList.map((task: any) => {
                                                            const dueDate = parseISO(task.due_date);
                                                            const isCritical = differenceInDays(dueDate, new Date()) <= 2 && differenceInDays(dueDate, new Date()) >= -1;
                                                            return (
                                                                <tr key={task.id} className="hover:bg-gray-50/50 transition-colors group">
                                                                    <td className="px-6 py-3.5">
                                                                        <Link href={route('activity.task.show', task.id)} className="block">
                                                                            <span className="font-medium text-gray-900 group-hover:text-indigo-600 transition-colors">{task.task_title || task.title}</span>
                                                                            {task.task_description && <p className="text-sm text-gray-400 truncate max-w-[200px] mt-0.5">{task.task_description}</p>}
                                                                        </Link>
                                                                    </td>
                                                                    <td className="px-6 py-3.5">
                                                                        {task.activity_type ? (
                                                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium border" style={{ backgroundColor: `${task.activity_type.color}10`, color: task.activity_type.color, borderColor: `${task.activity_type.color}30` }}>
                                                                                {task.activity_type.name}
                                                                            </span>
                                                                        ) : <span className="text-gray-400">-</span>}
                                                                    </td>
                                                                    <td className="px-6 py-3.5">
                                                                        <div className="flex items-center text-gray-600">
                                                                            <Calendar className={cn("h-3.5 w-3.5 mr-1.5", isCritical ? "text-red-500" : "text-gray-400")} />
                                                                            <span className={cn(isCritical ? "text-red-600 font-semibold" : "")}>{format(dueDate, 'MMM d')}</span>
                                                                        </div>
                                                                        {isCritical && <span className="text-[10px] text-red-500 font-medium block mt-0.5 ml-5">Due soon</span>}
                                                                    </td>
                                                                    <td className="px-6 py-3.5 text-right">
                                                                        <Link href={route('activity.task.show', task.id)}>
                                                                            <Button variant="ghost" size="sm" className="h-8 w-8 p-0 hover:bg-indigo-50 hover:text-indigo-600">
                                                                                <ArrowRight className="h-4 w-4" />
                                                                            </Button>
                                                                        </Link>
                                                                    </td>
                                                                </tr>
                                                            );
                                                        })}
                                                    </tbody>
                                                </table>
                                            ) : (
                                                <div className="flex flex-col items-center justify-center h-64 text-center">
                                                    <div className="p-4 bg-gray-50 rounded-full mb-3 shadow-sm border border-gray-100">
                                                        <CheckCircle2 className="h-8 w-8 text-gray-300" />
                                                    </div>
                                                    <p className="text-gray-900 font-medium">No tasks in this stage</p>
                                                    <p className="text-base text-gray-500 mt-1">Check other tabs or create a new task</p>
                                                    <Link href={route('activity.task.create')} className="mt-4">
                                                        <Button variant="outline" size="sm">Create Task</Button>
                                                    </Link>
                                                </div>
                                            )}
                                        </div>
                                        {pagination && pagination.last_page > 1 && (
                                            <div className="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                                                <p className="text-sm text-gray-500">
                                                    Showing <span className="font-medium">{((pagination.current_page - 1) * pagination.per_page) + 1}</span> to <span className="font-medium">{Math.min(pagination.current_page * pagination.per_page, pagination.total)}</span> of <span className="font-medium">{pagination.total}</span>
                                                </p>
                                                <div className="flex items-center gap-2">
                                                    <Button variant="outline" size="sm" className="h-8 gap-1 px-2 text-sm" onClick={() => handlePageChange(pagination.prev_page_url)} disabled={!pagination.prev_page_url}>
                                                        <ChevronLeft className="h-3.5 w-3.5" /> Prev
                                                    </Button>
                                                    <Button variant="outline" size="sm" className="h-8 gap-1 px-2 text-sm" onClick={() => handlePageChange(pagination.next_page_url)} disabled={!pagination.next_page_url}>
                                                        Next <ChevronRight className="h-3.5 w-3.5" />
                                                    </Button>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                    <div className="lg:col-span-3 flex flex-col gap-6">
                                        <div className="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                                            <h3 className="text-base font-bold text-gray-900 mb-4">Task Distribution</h3>
                                            <div className="h-[180px] relative">
                                                <ResponsiveContainer width="100%" height="100%">
                                                    <PieChart>
                                                        <Pie data={personalVisuals.distribution} cx="50%" cy="50%" innerRadius={55} outerRadius={75} paddingAngle={4} dataKey="value" cornerRadius={4}>
                                                            {personalVisuals.distribution?.map((entry, index) => (
                                                                <Cell key={`cell-${index}`} fill={entry.color || COLORS[index % COLORS.length]} strokeWidth={0} />
                                                            ))}
                                                        </Pie>
                                                        <RechartsTooltip contentStyle={{ borderRadius: '8px', border: 'none', fontSize: '12px', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }} />
                                                    </PieChart>
                                                </ResponsiveContainer>
                                                <div className="absolute inset-0 flex items-center justify-center pointer-events-none flex-col">
                                                    <span className="text-3xl font-bold text-gray-900 leading-none">{personalStats.total}</span>
                                                    <span className="text-sm text-gray-500 mt-1">Total</span>
                                                </div>
                                            </div>
                                            <div className="mt-4 space-y-2">
                                                {personalVisuals.distribution?.slice(0, 4).map((d, i) => (
                                                    <div key={i} className="flex items-center justify-between text-sm">
                                                        <div className="flex items-center text-gray-600">
                                                            <div className="w-2.5 h-2.5 rounded-full mr-2" style={{ background: d.color }}></div>
                                                            <span className="truncate max-w-[120px]">{d.name}</span>
                                                        </div>
                                                        <span className="font-medium text-gray-900 bg-gray-50 px-1.5 py-0.5 rounded">{personalStats.total > 0 ? Math.round((d.value / personalStats.total) * 100) : 0}%</span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                        <div className="bg-white rounded-xl border border-gray-200 p-5 shadow-sm flex-1 flex flex-col">
                                            <h3 className="text-base font-bold text-gray-900 mb-4 flex items-center justify-between">
                                                Upcoming
                                                <span className="text-[10px] text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full font-medium">Next 7 Days</span>
                                            </h3>
                                            <div className="space-y-3">
                                                {personalVisuals.upcoming?.length > 0 ? (
                                                    personalVisuals.upcoming.map(task => (
                                                        <Link key={task.id} href={route('activity.task.show', { task: task.id })}>
                                                            <div className="flex items-start gap-3 p-2.5 rounded-lg hover:bg-gray-50 transition-all cursor-pointer border border-transparent hover:border-gray-100 group">
                                                                <div className={cn("mt-1.5 w-2 h-2 rounded-full flex-shrink-0 ring-2 ring-white shadow-sm", task.is_critical ? "bg-red-500" : "bg-amber-400")}></div>
                                                                <div className="min-w-0 flex-1">
                                                                    <p className="text-base font-medium text-gray-900 truncate group-hover:text-indigo-600 transition-colors">{task.title}</p>
                                                                    <p className={cn("text-sm font-medium mt-0.5", task.is_critical ? "text-red-500" : "text-gray-400")}>
                                                                        {isToday(parseISO(task.due_date)) ? 'Today' : isTomorrow(parseISO(task.due_date)) ? 'Tomorrow' : format(parseISO(task.due_date), 'EEE, MMM d')}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </Link>
                                                    ))
                                                ) : (
                                                    <div className="text-center py-6">
                                                        <div className="w-10 h-10 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-2">
                                                            <Calendar className="h-5 w-5 text-gray-300" />
                                                        </div>
                                                        <p className="text-sm text-gray-500">No deadlines this week 🎉</p>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </motion.div>

                        ) : (
                            <motion.div
                                key="department"
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                exit={{ opacity: 0, y: -10 }}
                                transition={smoothTransition}
                                className="space-y-6"
                            >
                                {/* Row 1: Department Stats Cards */}
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div className="bg-white rounded-xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
                                        <div>
                                            <p className="text-base font-semibold text-gray-500">Team Overdue</p>
                                            <p className={cn("text-3xl font-bold mt-1", (departmentVisuals?.bottleneck || 0) > 0 ? "text-red-600" : "text-gray-900")}>{departmentVisuals?.bottleneck || 0}</p>
                                            {(departmentVisuals?.bottleneck || 0) > 0 ? (
                                                <p className="text-sm text-red-600 mt-1 font-medium bg-red-50 px-2 py-0.5 rounded-full inline-block border border-red-100">Needs attention</p>
                                            ) : (
                                                <p className="text-sm text-emerald-700 mt-1 font-medium bg-emerald-50 px-2 py-0.5 rounded-full inline-block border border-emerald-100">All on track</p>
                                            )}
                                        </div>
                                        <div className={cn("p-3 rounded-lg border", (departmentVisuals?.bottleneck || 0) > 0 ? "bg-red-100 border-red-200" : "bg-gray-50 border-gray-100")}>
                                            <AlertTriangle className={cn("h-6 w-6", (departmentVisuals?.bottleneck || 0) > 0 ? "text-red-600" : "text-gray-400")} />
                                        </div>
                                    </div>
                                    <div className="bg-white rounded-xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
                                        <div>
                                            <p className="text-base font-semibold text-gray-500">Total Active</p>
                                            <p className="text-3xl font-bold text-gray-900 mt-1">{departmentStats?.total || 0}</p>
                                            <p className="text-sm text-indigo-700 mt-1 font-medium bg-indigo-50 px-2 py-0.5 rounded-full inline-block border border-indigo-100">Department Tasks</p>
                                        </div>
                                        <div className="p-3 bg-indigo-50 rounded-lg border border-indigo-100">
                                            <Users className="h-6 w-6 text-indigo-600" />
                                        </div>
                                    </div>
                                    <div className="bg-white rounded-xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
                                        <div>
                                            <p className="text-base font-semibold text-gray-500">Top Category</p>
                                            <p className="text-3xl font-bold text-gray-900 mt-1 truncate max-w-[180px]" title={departmentVisuals?.top_category}>{departmentVisuals?.top_category || '-'}</p>
                                            <p className="text-sm text-gray-500 mt-1">Most active work type</p>
                                        </div>
                                        <div className="p-3 bg-amber-50 rounded-lg border border-amber-100">
                                            <Activity className="h-6 w-6 text-amber-600" />
                                        </div>
                                    </div>
                                </div>

                                {/* Row 2: Main Content */}
                                <div className="grid grid-cols-1 lg:grid-cols-10 gap-6 items-stretch">
                                    <div className="lg:col-span-7 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col">
                                        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                            <h2 className="text-xl font-bold text-gray-900 flex items-center gap-2">
                                                <List className="h-5 w-5 text-indigo-600" />
                                                Department Task Roadmap
                                            </h2>
                                            <div className="flex bg-gray-100 p-1 rounded-lg">
                                                {(['todo', 'inprogress', 'review'] as const).map(tab => (
                                                    <button
                                                        key={tab}
                                                        onClick={() => handleDeptTabChange(tab)}
                                                        className={cn(
                                                            "px-3 py-1.5 text-sm font-semibold rounded-md capitalize transition-all",
                                                            deptRoadmapTab === tab ? "bg-white text-gray-900 shadow-sm" : "text-gray-500 hover:text-gray-700"
                                                        )}
                                                    >
                                                        {tab.replace('todo', 'To Do').replace('inprogress', 'In Progress')}
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                        <div className="p-0 flex-1 overflow-auto max-h-[520px]">
                                            {activeDeptRoadmapList.length > 0 ? (
                                                <table className="w-full text-left">
                                                    <thead className="bg-gray-50 text-sm text-gray-400 uppercase tracking-wider sticky top-0 z-10">
                                                        <tr>
                                                            <th className="px-6 py-3 font-medium border-b border-gray-100">Task Name</th>
                                                            <th className="px-6 py-3 font-medium border-b border-gray-100">Category</th>
                                                            <th className="px-6 py-3 font-medium border-b border-gray-100">Deadline</th>
                                                            <th className="px-6 py-3 font-medium text-right border-b border-gray-100">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="divide-y divide-gray-100 text-base">
                                                        {activeDeptRoadmapList.map((task: any) => {
                                                            const dueDate = parseISO(task.due_date);
                                                            const isCritical = differenceInDays(dueDate, new Date()) <= 2 && differenceInDays(dueDate, new Date()) >= -1;
                                                            return (
                                                                <tr key={task.id} className="hover:bg-gray-50/50 transition-colors group">
                                                                    <td className="px-6 py-3.5">
                                                                        <Link href={route('activity.task.show', task.id)} className="block">
                                                                            <span className="font-medium text-gray-900 group-hover:text-indigo-600 transition-colors">{task.task_title || task.title}</span>
                                                                            {task.task_description && <p className="text-sm text-gray-400 truncate max-w-[200px] mt-0.5">{task.task_description}</p>}
                                                                        </Link>
                                                                    </td>
                                                                    <td className="px-6 py-3.5">
                                                                        {task.activity_type ? (
                                                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium border" style={{ backgroundColor: `${task.activity_type.color}10`, color: task.activity_type.color, borderColor: `${task.activity_type.color}30` }}>
                                                                                {task.activity_type.name}
                                                                            </span>
                                                                        ) : <span className="text-gray-400">-</span>}
                                                                    </td>
                                                                    <td className="px-6 py-3.5">
                                                                        <div className="flex items-center text-gray-600">
                                                                            <Calendar className={cn("h-3.5 w-3.5 mr-1.5", isCritical ? "text-red-500" : "text-gray-400")} />
                                                                            <span className={cn(isCritical ? "text-red-600 font-semibold" : "")}>{format(dueDate, 'MMM d')}</span>
                                                                        </div>
                                                                        {isCritical && <span className="text-[10px] text-red-500 font-medium block mt-0.5 ml-5">Due soon</span>}
                                                                    </td>
                                                                    <td className="px-6 py-3.5 text-right">
                                                                        <Link href={route('activity.task.show', task.id)}>
                                                                            <Button variant="ghost" size="sm" className="h-8 w-8 p-0 hover:bg-indigo-50 hover:text-indigo-600">
                                                                                <ArrowRight className="h-4 w-4" />
                                                                            </Button>
                                                                        </Link>
                                                                    </td>
                                                                </tr>
                                                            );
                                                        })}
                                                    </tbody>
                                                </table>
                                            ) : (
                                                <div className="flex flex-col items-center justify-center h-64 text-center">
                                                    <div className="p-4 bg-gray-50 rounded-full mb-3 shadow-sm border border-gray-100">
                                                        <CheckCircle2 className="h-8 w-8 text-gray-300" />
                                                    </div>
                                                    <p className="text-gray-900 font-medium">No department tasks in this stage</p>
                                                    <p className="text-base text-gray-500 mt-1">Check other tabs or create a new task</p>
                                                </div>
                                            )}
                                        </div>
                                        {deptPagination && deptPagination.last_page > 1 && (
                                            <div className="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                                                <p className="text-sm text-gray-500">
                                                    Showing <span className="font-medium">{((deptPagination.current_page - 1) * deptPagination.per_page) + 1}</span> to <span className="font-medium">{Math.min(deptPagination.current_page * deptPagination.per_page, deptPagination.total)}</span> of <span className="font-medium">{deptPagination.total}</span>
                                                </p>
                                                <div className="flex items-center gap-2">
                                                    <Button variant="outline" size="sm" className="h-8 gap-1 px-2 text-sm" onClick={() => handleDeptPageChange(deptPagination.prev_page_url)} disabled={!deptPagination.prev_page_url}>
                                                        <ChevronLeft className="h-3.5 w-3.5" /> Prev
                                                    </Button>
                                                    <Button variant="outline" size="sm" className="h-8 gap-1 px-2 text-sm" onClick={() => handleDeptPageChange(deptPagination.next_page_url)} disabled={!deptPagination.next_page_url}>
                                                        Next <ChevronRight className="h-3.5 w-3.5" />
                                                    </Button>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                    <div className="lg:col-span-3 flex flex-col gap-6">
                                        <div className="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                                            <h3 className="text-base font-bold text-gray-900 mb-4">Task Distribution</h3>
                                            <div className="h-[180px] relative">
                                                <ResponsiveContainer width="100%" height="100%">
                                                    <PieChart>
                                                        <Pie data={departmentVisuals?.distribution || []} cx="50%" cy="50%" innerRadius={55} outerRadius={75} paddingAngle={4} dataKey="value" cornerRadius={4}>
                                                            {(departmentVisuals?.distribution || []).map((entry: any, index: number) => (
                                                                <Cell key={`cell-${index}`} fill={entry.color || COLORS[index % COLORS.length]} />
                                                            ))}
                                                        </Pie>
                                                        <RechartsTooltip formatter={(value: any, name: any) => [value, name]} />
                                                    </PieChart>
                                                </ResponsiveContainer>
                                                <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                                                    <div className="text-center">
                                                        <p className="text-3xl font-bold text-gray-900">{departmentStats?.total || 0}</p>
                                                        <p className="text-[10px] text-gray-400 font-medium">Total</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="mt-4 space-y-2">
                                                {(departmentVisuals?.distribution || []).slice(0, 4).map((item: any, i: number) => (
                                                    <div key={i} className="flex items-center justify-between text-sm">
                                                        <div className="flex items-center gap-2">
                                                            <span className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: item.color || COLORS[i % COLORS.length] }}></span>
                                                            <span className="text-gray-600 font-medium">{item.name}</span>
                                                        </div>
                                                        <span className="text-gray-400">{departmentStats?.total ? Math.round((item.value / departmentStats.total) * 100) : 0}%</span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                        <div className="bg-white rounded-xl border border-gray-200 p-5 shadow-sm flex-1 flex flex-col">
                                            <h3 className="text-base font-bold text-gray-900 mb-4 flex items-center justify-between">
                                                Upcoming
                                                <span className="text-[10px] text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full font-medium">Next 7 Days</span>
                                            </h3>
                                            <div className="space-y-3">
                                                {departmentVisuals?.upcoming && departmentVisuals.upcoming.length > 0 ? (
                                                    departmentVisuals.upcoming.map((task: any) => (
                                                        <Link href={route('activity.task.show', { task: task.id })} key={task.id} className="block group">
                                                            <div className="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-colors -mx-2">
                                                                <div className={cn("w-2 h-2 rounded-full flex-shrink-0", task.is_critical ? "bg-red-500" : "bg-amber-400")}></div>
                                                                <div className="flex-1 min-w-0">
                                                                    <p className="text-base font-medium text-gray-900 truncate group-hover:text-indigo-600 transition-colors">{task.title}</p>
                                                                    <p className={cn("text-[10px] font-medium", task.is_critical ? "text-red-500" : "text-gray-400")}>
                                                                        {task.is_critical ? 'Due soon' : format(parseISO(task.due_date), 'EEE, MMM d')}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </Link>
                                                    ))
                                                ) : (
                                                    <div className="text-center py-6">
                                                        <div className="w-10 h-10 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-2">
                                                            <Calendar className="h-5 w-5 text-gray-300" />
                                                        </div>
                                                        <p className="text-sm text-gray-500">No deadlines this week 🎉</p>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </motion.div>
                        )}
                    </AnimatePresence>
                </div>
            </div>
        </>
    );
}
