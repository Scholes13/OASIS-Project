import { useState, useEffect, useCallback } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    Plus,
    List,
    Columns3,
    Calendar,
    Clock,
} from 'lucide-react';
import { useBusinessUnit } from '@/hooks/useBusinessUnit';
import FilterDropdown from '@/components/activity/FilterDropdown';
import { LoadingOverlay } from '@/components/ui/LoadingSpinner';
import { Button } from '@/components/ui/button';
import { ActivityDataTable } from '@/components/activity/ActivityDataTable';
import { KanbanBoard } from '@/components/activity/KanbanBoard';
import { ActivityCalendar } from '@/components/activity/ActivityCalendar';
import { ActivityTimeline } from '@/components/activity/ActivityTimeline';
import { Toaster } from '@/components/ui/toast';
import { cn } from '@/lib/utils';
import type { PageProps, Task, TaskStats, TaskFilters, ActivityType, PaginatedData, ActivityByType } from '@/types';

interface DashboardProps extends PageProps {
    stats: TaskStats;
    tasks: PaginatedData<Task>;
    activityTypes: ActivityType[];
    filters: TaskFilters;
    byActivityType: ActivityByType[];
}

type ViewType = 'list' | 'board' | 'calendar' | 'timeline';

const viewConfig: { id: ViewType; icon: React.ReactNode; tooltip: string }[] = [
    { id: 'list', icon: <List className="h-4 w-4" strokeWidth={1.5} />, tooltip: 'List View' },
    { id: 'board', icon: <Columns3 className="h-4 w-4" strokeWidth={1.5} />, tooltip: 'Board' },
    { id: 'calendar', icon: <Calendar className="h-4 w-4" strokeWidth={1.5} />, tooltip: 'Calendar' },
    { id: 'timeline', icon: <Clock className="h-4 w-4" strokeWidth={1.5} />, tooltip: 'Timeline' },
];

// Animation variants for different views
const viewAnimations = {
    list: {
        initial: { opacity: 0, y: 20, scale: 0.98 },
        animate: { opacity: 1, y: 0, scale: 1 },
        exit: { opacity: 0, y: -20, scale: 0.98 },
    },
    board: {
        initial: { opacity: 0, x: 40 },
        animate: { opacity: 1, x: 0 },
        exit: { opacity: 0, x: -40 },
    },
    calendar: {
        initial: { opacity: 0, scale: 0.95 },
        animate: { opacity: 1, scale: 1 },
        exit: { opacity: 0, scale: 0.95 },
    },
    timeline: {
        initial: { opacity: 0, y: 30 },
        animate: { opacity: 1, y: 0 },
        exit: { opacity: 0, y: -30 },
    },
};

const springTransition = { type: "spring" as const, stiffness: 300, damping: 30 };
const smoothTransition = { duration: 0.35, ease: "easeInOut" as const };

// ============================================================================
// STATS CARD COMPONENT - Enterprise Style with Large Metrics
// ============================================================================

interface StatsCardProps {
    title: string;
    value: number;
    icon: React.ReactNode;
    iconBg?: string;
    iconColor?: string;
}

function StatsCard({ title, value, icon, iconBg = "bg-gray-100", iconColor = "text-gray-600" }: StatsCardProps) {
    return (
        <div className="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow">
            <div className="flex items-center justify-between mb-3">
                <span className="text-sm font-medium text-gray-500">{title}</span>
                <div className={cn("p-2 rounded-lg", iconBg)}>
                    <span className={iconColor}>{icon}</span>
                </div>
            </div>
            <p className="text-3xl font-bold tracking-tight text-gray-900">{value}</p>
        </div>
    );
}

export default function Dashboard({ stats, tasks, activityTypes, filters, byActivityType }: DashboardProps) {
    const [view, setView] = useState<ViewType>('list');
    const [localFilters, setLocalFilters] = useState<TaskFilters>(filters);
    const [isFiltering, setIsFiltering] = useState(false);

    const { currentBusinessUnit, isSwitching: isBuLoading } = useBusinessUnit([
        'stats', 'tasks', 'byActivityType'
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
                        only: ['stats', 'tasks', 'byActivityType'],
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

    return (
        <>
            <Head title="Activity Tasks" />
            <Toaster />

            {/* Page content - AppLayout handles the outer container */}
            <div className="relative min-h-screen bg-gray-50">
                {isLoading && <LoadingOverlay message="Loading..." />}

                <div className="w-full p-6">
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <div>
                            <h1 className="text-xl font-semibold text-gray-900">Activity Tasks</h1>
                            <p className="mt-0.5 text-sm text-gray-500">
                                Track and manage your work activities
                                {currentBusinessUnit && (
                                    <span className="text-indigo-600"> · {currentBusinessUnit.name}</span>
                                )}
                            </p>
                        </div>
                        <Link href={route('activity.task.create')}>
                            <Button className="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm">
                                <Plus className="h-4 w-4 mr-1.5" strokeWidth={2} />
                                New Activity
                            </Button>
                        </Link>
                    </div>

                    {/* View Switcher & Filter - NO redundant search here */}
                    <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                        <div className="inline-flex items-center p-1 bg-white rounded-lg border border-gray-200 shadow-sm">
                            {viewConfig.map(({ id, icon, tooltip }) => (
                                <motion.button
                                    key={id}
                                    onClick={() => handleViewChange(id)}
                                    title={tooltip}
                                    className={cn(
                                        "relative p-2 rounded-md transition-colors duration-150",
                                        view === id
                                            ? "text-white"
                                            : "text-gray-500 hover:text-gray-700 hover:bg-gray-50"
                                    )}
                                    whileHover={{ scale: view === id ? 1 : 1.05 }}
                                    whileTap={{ scale: 0.95 }}
                                >
                                    {view === id && (
                                        <motion.div
                                            layoutId="activeViewIndicator"
                                            className="absolute inset-0 bg-indigo-600 rounded-md shadow-sm"
                                            transition={springTransition}
                                        />
                                    )}
                                    <span className="relative z-10">{icon}</span>
                                </motion.button>
                            ))}
                        </div>

                        {view !== 'calendar' && view !== 'list' && (
                            <FilterDropdown
                                filters={localFilters}
                                onChange={setLocalFilters}
                                activityTypes={activityTypes}
                                isFiltering={isFiltering}
                            />
                        )}
                    </div>

                    {/* Main Content Area */}
                    <AnimatePresence mode="wait">
                        {view === 'list' && (
                            <motion.div 
                                key="list" 
                                {...viewAnimations.list}
                                transition={smoothTransition}
                            >
                                <ActivityDataTable tasks={tasks} stats={safeStats} />
                            </motion.div>
                        )}

                        {view === 'board' && (
                            <motion.div 
                                key="board" 
                                {...viewAnimations.board}
                                transition={smoothTransition}
                            >
                                <KanbanBoard tasks={taskData} />
                            </motion.div>
                        )}

                        {view === 'calendar' && (
                            <motion.div 
                                key="calendar" 
                                {...viewAnimations.calendar}
                                transition={smoothTransition}
                            >
                                <ActivityCalendar tasks={taskData} onEventClick={handleTaskClick} />
                            </motion.div>
                        )}

                        {view === 'timeline' && (
                            <motion.div 
                                key="timeline" 
                                {...viewAnimations.timeline}
                                transition={smoothTransition}
                            >
                                <ActivityTimeline tasks={taskData} onTaskClick={handleTaskClick} />
                            </motion.div>
                        )}
                    </AnimatePresence>
                </div>
            </div>
        </>
    );
}



