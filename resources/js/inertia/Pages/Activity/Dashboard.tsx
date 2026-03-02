import { lazy, Suspense, useState, useEffect, useCallback } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    Plus,
    List,
    Columns3,
    Calendar as CalendarIcon,
    Clock,
    LayoutDashboard,
    PieChart,
    ArrowUpRight,
    CheckCircle2,
    Clock as ClockIcon,
    AlertCircle,
    MoreHorizontal
} from 'lucide-react';
import { useBusinessUnit } from '@/hooks/useBusinessUnit';
import FilterDropdown from '@/components/activity/FilterDropdown';
import { LoadingOverlay } from '@/components/ui/LoadingSpinner';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { TaskFormModal } from '@/components/activity/TaskFormModal';
import type { PageProps, Task, TaskStats, TaskFilters, ActivityType, PaginatedData } from '@/types';

const ActivityDataTable = lazy(() => import('@/components/activity/ActivityDataTable'));
const KanbanBoard = lazy(() => import('@/components/activity/KanbanBoard'));
const ActivityCalendar = lazy(() => import('@/components/activity/ActivityCalendar'));
const ActivityTimeline = lazy(() => import('@/components/activity/ActivityTimeline'));

interface DashboardProps extends PageProps {
    stats: TaskStats;
    tasks: PaginatedData<Task>;
    activityTypes: any;
    filters: TaskFilters;
    departmentUsers?: any[];
    backdatePermission?: any;
    allowedDateRange?: any;
    backdateEnabled?: boolean;
    prioritizedActivityTypes?: any;
}

type ViewType = 'list' | 'board' | 'calendar' | 'timeline';

const viewConfig: { id: ViewType; icon: React.ReactNode; tooltip: string }[] = [
    { id: 'list', icon: <List className="h-4 w-4" strokeWidth={1.5} />, tooltip: 'List View' },
    { id: 'board', icon: <Columns3 className="h-4 w-4" strokeWidth={1.5} />, tooltip: 'Kanban Board' },
    { id: 'calendar', icon: <CalendarIcon className="h-4 w-4" strokeWidth={1.5} />, tooltip: 'Calendar' },
    { id: 'timeline', icon: <Clock className="h-4 w-4" strokeWidth={1.5} />, tooltip: 'Timeline' },
];

export default function Dashboard({ stats, tasks, activityTypes, filters, departmentUsers = [], backdatePermission, allowedDateRange, backdateEnabled, prioritizedActivityTypes }: DashboardProps) {
    const [view, setView] = useState<ViewType>('list');
    const [localFilters, setLocalFilters] = useState<TaskFilters>(filters);
    const [isFiltering, setIsFiltering] = useState(false);
    const [showTaskModal, setShowTaskModal] = useState(false);
    const [initialTaskDate, setInitialTaskDate] = useState<string | null>(null);

    const handleCreateTaskClick = (options?: { date?: string }) => {
        setInitialTaskDate(options?.date ?? null);

        router.reload({
            only: ['departmentUsers', 'backdatePermission', 'allowedDateRange', 'backdateEnabled', 'prioritizedActivityTypes'],
            onSuccess: () => setShowTaskModal(true),
        });
    };

    const { currentBusinessUnit, isSwitching: isBuLoading } = useBusinessUnit([
        'stats', 'tasks', 'filters'
    ]);

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const urlView = urlParams.get('view') as ViewType;
        const savedView = localStorage.getItem('activity-view') as ViewType;
        const validViews: ViewType[] = ['list', 'board', 'calendar', 'timeline'];

        if (urlView && validViews.includes(urlView)) {
            setView(urlView);
        } else if (savedView && validViews.includes(savedView)) {
            setView(savedView);
        }
    }, []);

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (JSON.stringify(localFilters) !== JSON.stringify(filters)) {
                setIsFiltering(true);
                router.get(
                    route('activity.task.index'),
                    {
                        ...Object.fromEntries(
                            Object.entries(localFilters).filter(([_, v]) => v !== '')
                        ),
                        view: view,
                    },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        only: ['stats', 'tasks', 'filters'],
                        onFinish: () => setIsFiltering(false),
                    }
                );
            }
        }, 300);
        return () => clearTimeout(timeout);
    }, [localFilters]);

    const handleViewChange = useCallback((newView: ViewType) => {
        setView(newView);
        localStorage.setItem('activity-view', newView);
        const url = new URL(window.location.href);
        if (newView === 'list') {
            url.searchParams.delete('view');
        } else {
            url.searchParams.set('view', newView);
        }
        window.history.pushState({}, '', url.toString());
    }, []);

    const handleTaskClick = useCallback((task: Task) => {
        router.visit(route('activity.task.show', { task: task.id }));
    }, []);

    const isLoading = isBuLoading || isFiltering;
    const taskData = tasks?.data ?? [];
    const safeStats = stats ?? { total: 0, planned: 0, in_progress: 0, completed: 0, overdue: 0 };

    // Calculate completion rate
    const completionRate = safeStats.total > 0
        ? Math.round((safeStats.completed / safeStats.total) * 100)
        : 0;

    return (
        <div className="w-full font-sans text-slate-800">
            <Head title="Tasks" />

            {isLoading && <LoadingOverlay message="Syncing workspace..." />}

            {/* Main Content Area */}
            <div className="w-full px-6 py-6 lg:px-8">
                <div className="w-full flex flex-col gap-6">
                    
                    {/* Header Inline with the Page (Linear/Notion style) */}
                    <div className="flex flex-col xl:flex-row xl:items-end justify-between gap-4">
                        <div className="flex flex-col gap-1.5">
                            <h1 className="text-2xl font-bold text-slate-900 tracking-tight">My Tasks</h1>
                            <div className="flex flex-wrap items-center gap-2 mt-1">
                                <span className="inline-flex items-center rounded-md bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 border border-slate-200 shadow-sm">
                                    {safeStats.total} Total
                                </span>
                                {safeStats.in_progress > 0 && (
                                    <span className="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-[11px] font-semibold text-blue-700 border border-blue-100 shadow-sm">
                                        {safeStats.in_progress} Active
                                    </span>
                                )}
                                {safeStats.overdue > 0 && (
                                    <span className="inline-flex items-center rounded-md bg-rose-50 px-2 py-1 text-[11px] font-semibold text-rose-700 border border-rose-100 shadow-sm">
                                        {safeStats.overdue} Overdue
                                    </span>
                                )}
                            </div>
                        </div>

                        {/* Control Bar (Filters & Views) */}
                        <div className="flex flex-wrap items-center gap-3 bg-white p-1.5 rounded-xl border border-slate-200 shadow-sm">
                            
                            {/* View Switcher Tabs */}
                            <div className="flex bg-slate-50/80 p-1 rounded-lg border border-slate-100">
                                {viewConfig.map(({ id, icon, tooltip }) => (
                                    <button
                                        key={id}
                                        onClick={() => handleViewChange(id)}
                                        title={tooltip}
                                        className={cn(
                                            "flex items-center gap-2 px-3 py-1.5 text-[13px] font-medium rounded-md transition-all duration-200",
                                            view === id
                                                ? "bg-white text-[#16599c] shadow-sm font-semibold ring-1 ring-slate-200/50"
                                                : "text-slate-500 hover:text-slate-800 hover:bg-slate-100/50"
                                        )}
                                    >
                                        <span className={cn(view === id ? "text-[#16599c]" : "text-slate-400")}>
                                            {icon}
                                        </span>
                                        <span className="capitalize hidden md:inline-block">{id}</span>
                                    </button>
                                ))}
                            </div>
                            
                            <div className="w-px h-6 bg-slate-200 hidden sm:block"></div>

                            {/* Scope Toggle (My Tasks vs Team) */}
                            <div className="flex items-center bg-slate-50/80 p-1 rounded-lg border border-slate-100">
                                <button 
                                    onClick={() => setLocalFilters(prev => ({ ...prev, scope: 'my' }))}
                                    className={cn("px-4 py-1.5 text-[13px] font-medium rounded-md transition-all duration-200", (!localFilters.scope || localFilters.scope === 'my') ? "bg-white text-[#16599c] shadow-sm font-semibold ring-1 ring-slate-200/50" : "text-slate-500 hover:text-slate-800")}
                                >
                                    My Tasks
                                </button>
                                <button 
                                    onClick={() => setLocalFilters(prev => ({ ...prev, scope: 'department' }))}
                                    className={cn("px-4 py-1.5 text-[13px] font-medium rounded-md transition-all duration-200", localFilters.scope === 'department' ? "bg-white text-[#16599c] shadow-sm font-semibold ring-1 ring-slate-200/50" : "text-slate-500 hover:text-slate-800")}
                                >
                                    Team
                                </button>
                            </div>

                            <div className="w-px h-6 bg-slate-200 hidden md:block"></div>
                            
                            {/* Filter Dropdown */}
                            <div className="hidden sm:block">
                                <FilterDropdown
                                    filters={localFilters}
                                    onChange={setLocalFilters}
                                    activityTypes={activityTypes}
                                    isFiltering={isFiltering}
                                />
                            </div>

                            <div className="w-px h-6 bg-slate-200 hidden sm:block"></div>

                            <button 
                                onClick={() => handleCreateTaskClick()}
                                className="ml-auto sm:ml-0 flex items-center gap-2 bg-[#16599c] hover:bg-[#124a82] text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors border-none cursor-pointer shadow-sm h-[36px]"
                            >
                                <Plus className="h-4 w-4" strokeWidth={2.5} />
                                <span className="hidden sm:inline">Create Task</span>
                            </button>
                        </div>
                    </div>

                    {/* Workspace Area - Rendering the views */}
                    <div className="w-full mt-2">
                        <Suspense
                            fallback={
                                <div className="flex h-64 items-center justify-center text-slate-400">
                                    <div className="flex flex-col items-center gap-2">
                                        <div className="h-8 w-8 animate-spin rounded-full border-2 border-slate-200 border-t-[#16599c]"></div>
                                        <span className="text-sm font-medium">Loading workspace...</span>
                                    </div>
                                </div>
                            }
                        >
                            <AnimatePresence mode="wait" initial={false}>
                                {view === 'list' && (
                                    <motion.div key="list" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="h-full">
                                        <ActivityDataTable tasks={tasks} stats={safeStats} filters={filters} showHeader={false} compact={true} />
                                    </motion.div>
                                )}
                                {view === 'board' && (
                                    <motion.div key="board" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="h-full min-h-[500px]">
                                        <KanbanBoard tasks={taskData} onCreateTask={() => handleCreateTaskClick()} />
                                    </motion.div>
                                )}
                                {view === 'calendar' && (
                                    <motion.div key="calendar" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="h-full bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden p-2 sm:p-4">
                                        <ActivityCalendar tasks={taskData} onEventClick={handleTaskClick} onCreateTask={handleCreateTaskClick} />
                                    </motion.div>
                                )}
                                {view === 'timeline' && (
                                    <motion.div key="timeline" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="h-full bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden p-2 sm:p-4">
                                        <ActivityTimeline tasks={taskData} onTaskClick={handleTaskClick} onCreateTask={() => handleCreateTaskClick()} />
                                    </motion.div>
                                )}
                            </AnimatePresence>
                        </Suspense>
                    </div>
                </div>
            </div>

            {/* Create Task Modal */}
            <TaskFormModal 
                open={showTaskModal} 
                onClose={() => {
                    setShowTaskModal(false);
                    setInitialTaskDate(null);
                }}
                task={null}
                activityTypes={prioritizedActivityTypes || activityTypes}
                departmentUsers={departmentUsers}
                backdatePermission={backdatePermission}
                allowedDateRange={allowedDateRange || { from: '', to: '' }}
                backdateEnabled={backdateEnabled}
                initialTaskDate={initialTaskDate}
            />
        </div>
    );
}
