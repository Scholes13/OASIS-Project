import { Head, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    Activity,
    CheckCircle2,
    Clock,
    AlertTriangle,
    TrendingUp,
    Users,
    Calendar,
    ArrowRight,
    BarChart3,
} from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';

interface Stats {
    total: number;
    completed: number;
    in_progress: number;
    overdue: number;
    planned?: number;
    completed_this_month?: number;
}

interface DashboardProps extends PageProps {
    personalStats: Stats;
    departmentStats: Stats | null;
    canViewReports?: boolean;
}

const smoothTransition = { duration: 0.35, ease: [0.4, 0, 0.2, 1] };

export default function ActivityDashboard({ personalStats, departmentStats, canViewReports }: DashboardProps) {
    return (
        <>
            <Head title="Activity Dashboard" />
            <div className="min-h-screen bg-slate-50">
                <div className="w-full px-4 sm:px-6 lg:px-8 py-6">
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                        <div>
                            <h1 className="text-2xl font-semibold text-gray-900">Activity Dashboard</h1>
                            <p className="mt-1 text-sm text-gray-500">
                                Overview of your tasks and team performance
                            </p>
                        </div>
                        <Link href={route('activity.task.index')}>
                            <Button className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                <Activity className="h-4 w-4 mr-2" />
                                View All Tasks
                            </Button>
                        </Link>
                    </div>

                    {/* Personal Stats Section */}
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={smoothTransition}
                        className="mb-8"
                    >
                        <div className="flex items-center gap-2 mb-4">
                            <div className="p-2 bg-indigo-100 rounded-lg">
                                <Activity className="h-5 w-5 text-indigo-600" />
                            </div>
                            <h2 className="text-lg font-semibold text-gray-900">My Performance</h2>
                        </div>
                        
                        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <StatsCard
                                title="Total Tasks"
                                value={personalStats.total}
                                icon={<Activity className="h-5 w-5" />}
                                iconBg="bg-indigo-50"
                                iconColor="text-indigo-600"
                            />
                            <StatsCard
                                title="In Progress"
                                value={personalStats.in_progress}
                                icon={<Clock className="h-5 w-5" />}
                                iconBg="bg-amber-50"
                                iconColor="text-amber-600"
                            />
                            <StatsCard
                                title="Overdue"
                                value={personalStats.overdue}
                                icon={<AlertTriangle className="h-5 w-5" />}
                                iconBg={personalStats.overdue > 0 ? "bg-rose-50" : "bg-gray-50"}
                                iconColor={personalStats.overdue > 0 ? "text-rose-600" : "text-gray-400"}
                                highlight={personalStats.overdue > 0}
                            />
                            <StatsCard
                                title="Completed"
                                value={personalStats.completed}
                                icon={<CheckCircle2 className="h-5 w-5" />}
                                iconBg="bg-emerald-50"
                                iconColor="text-emerald-600"
                            />
                        </div>

                        {/* Completion Rate */}
                        {personalStats.total > 0 && (
                            <div className="mt-4 bg-white rounded-xl border border-gray-200 p-4">
                                <div className="flex items-center justify-between mb-2">
                                    <span className="text-sm font-medium text-gray-700">Completion Rate</span>
                                    <span className="text-sm font-semibold text-indigo-600">
                                        {Math.round((personalStats.completed / personalStats.total) * 100)}%
                                    </span>
                                </div>
                                <div className="w-full bg-gray-100 rounded-full h-2">
                                    <div
                                        className="bg-indigo-600 h-2 rounded-full transition-all duration-500"
                                        style={{ width: `${(personalStats.completed / personalStats.total) * 100}%` }}
                                    />
                                </div>
                            </div>
                        )}
                    </motion.div>

                    {/* Department Stats Section */}
                    {departmentStats && (
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ ...smoothTransition, delay: 0.1 }}
                            className="mb-8"
                        >
                            <div className="flex items-center gap-2 mb-4">
                                <div className="p-2 bg-violet-100 rounded-lg">
                                    <Users className="h-5 w-5 text-violet-600" />
                                </div>
                                <h2 className="text-lg font-semibold text-gray-900">Department Overview</h2>
                            </div>
                            
                            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                <StatsCard
                                    title="Total Tasks"
                                    value={departmentStats.total}
                                    icon={<Activity className="h-5 w-5" />}
                                    iconBg="bg-violet-50"
                                    iconColor="text-violet-600"
                                />
                                <StatsCard
                                    title="In Progress"
                                    value={departmentStats.in_progress}
                                    icon={<Clock className="h-5 w-5" />}
                                    iconBg="bg-amber-50"
                                    iconColor="text-amber-600"
                                />
                                <StatsCard
                                    title="Overdue"
                                    value={departmentStats.overdue}
                                    icon={<AlertTriangle className="h-5 w-5" />}
                                    iconBg={departmentStats.overdue > 0 ? "bg-rose-50" : "bg-gray-50"}
                                    iconColor={departmentStats.overdue > 0 ? "text-rose-600" : "text-gray-400"}
                                    highlight={departmentStats.overdue > 0}
                                />
                                <StatsCard
                                    title="Completed"
                                    value={departmentStats.completed}
                                    icon={<CheckCircle2 className="h-5 w-5" />}
                                    iconBg="bg-emerald-50"
                                    iconColor="text-emerald-600"
                                />
                            </div>
                        </motion.div>
                    )}

                    {/* Quick Actions */}
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ ...smoothTransition, delay: 0.2 }}
                    >
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <QuickActionCard
                                href={route('activity.task.create')}
                                icon={<Activity className="h-6 w-6" />}
                                title="Create New Task"
                                description="Add a new activity or task"
                                color="indigo"
                            />
                            <QuickActionCard
                                href={route('activity.task.index', { view: 'board' })}
                                icon={<TrendingUp className="h-6 w-6" />}
                                title="Kanban Board"
                                description="View tasks in board layout"
                                color="violet"
                            />
                            <QuickActionCard
                                href={route('activity.task.index', { view: 'calendar' })}
                                icon={<Calendar className="h-6 w-6" />}
                                title="Calendar View"
                                description="See tasks on calendar"
                                color="emerald"
                            />
                            {canViewReports && (
                                <QuickActionCard
                                    href={route('activity.reporting')}
                                    icon={<BarChart3 className="h-6 w-6" />}
                                    title="BOD Reporting"
                                    description="View aggregated metrics"
                                    color="indigo"
                                />
                            )}
                        </div>
                    </motion.div>
                </div>
            </div>
        </>
    );
}

interface StatsCardProps {
    title: string;
    value: number;
    icon: React.ReactNode;
    iconBg?: string;
    iconColor?: string;
    highlight?: boolean;
}

function StatsCard({ title, value, icon, iconBg = "bg-gray-100", iconColor = "text-gray-600", highlight }: StatsCardProps) {
    return (
        <div className={cn(
            "bg-white rounded-xl border p-4 hover:shadow-md transition-shadow",
            highlight ? "border-rose-200" : "border-gray-200"
        )}>
            <div className="flex items-center justify-between mb-3">
                <span className="text-sm font-medium text-gray-500">{title}</span>
                <div className={cn("p-2 rounded-lg", iconBg)}>
                    <span className={iconColor}>{icon}</span>
                </div>
            </div>
            <p className={cn(
                "text-3xl font-bold tracking-tight",
                highlight ? "text-rose-600" : "text-gray-900"
            )}>{value}</p>
        </div>
    );
}

interface QuickActionCardProps {
    href: string;
    icon: React.ReactNode;
    title: string;
    description: string;
    color: 'indigo' | 'violet' | 'emerald';
}

function QuickActionCard({ href, icon, title, description, color }: QuickActionCardProps) {
    const colorClasses = {
        indigo: 'bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100',
        violet: 'bg-violet-50 text-violet-600 group-hover:bg-violet-100',
        emerald: 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100',
    };

    return (
        <Link
            href={href}
            className="group bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md hover:border-gray-300 transition-all flex items-center gap-4"
        >
            <div className={cn("p-3 rounded-xl transition-colors", colorClasses[color])}>
                {icon}
            </div>
            <div className="flex-1">
                <h3 className="font-medium text-gray-900 group-hover:text-indigo-600 transition-colors">{title}</h3>
                <p className="text-sm text-gray-500">{description}</p>
            </div>
            <ArrowRight className="h-5 w-5 text-gray-300 group-hover:text-indigo-500 group-hover:translate-x-1 transition-all" />
        </Link>
    );
}
