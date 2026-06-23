import * as React from 'react';
import { Link, router } from '@inertiajs/react';
import { format, isToday, isPast, differenceInDays } from 'date-fns';
import { id as idLocale } from 'date-fns/locale';
import { motion, AnimatePresence } from 'framer-motion';
import {
    Calendar,
    Clock,
    Building2,
    DollarSign,
    ChevronRight,
    CheckCircle2,
    Circle,
    PlayCircle,
    Info,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import type { AdminTask } from './types';

interface PurchasingTaskTimelineProps {
    tasks: AdminTask[];
    onTaskClick?: (task: AdminTask) => void;
    showDateHeaders?: boolean;
    readonly?: boolean;
}

// Status styling
const statusStyles: Record<string, { dot: string; label: string }> = {
    pending_followup: { dot: 'bg-slate-400', label: 'Pending' },
    in_progress: { dot: 'bg-amber-500', label: 'In Progress' },
    done: { dot: 'bg-emerald-500', label: 'Completed' },
};

// Format currency
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

// Group tasks by date
function groupTasksByDate(tasks: AdminTask[]): Map<string, AdminTask[]> {
    const grouped = new Map<string, AdminTask[]>();

    const sortedTasks = [...tasks].sort((a, b) =>
        new Date(b.entered_at).getTime() - new Date(a.entered_at).getTime()
    );

    sortedTasks.forEach((task) => {
        const dateKey = format(new Date(task.entered_at), 'yyyy-MM-dd');
        if (!grouped.has(dateKey)) {
            grouped.set(dateKey, []);
        }
        grouped.get(dateKey)!.push(task);
    });

    return grouped;
}

// Date header component
function DateHeader({ date }: { date: Date }) {
    const today = isToday(date);
    const past = isPast(date) && !today;
    const daysAgo = differenceInDays(new Date(), date);

    return (
        <div className="flex items-center gap-3 py-2">
            <div
                className={cn(
                    'flex items-center justify-center w-10 h-10 rounded-full text-sm font-bold',
                    today
                        ? 'bg-primary text-white'
                        : past
                            ? 'bg-gray-200 text-gray-600'
                            : 'bg-blue-100 text-blue-600'
                )}
            >
                {format(date, 'dd')}
            </div>
            <div>
                <p
                    className={cn(
                        'text-sm font-semibold',
                        today ? 'text-primary' : 'text-gray-900'
                    )}
                >
                    {today ? 'Today' : format(date, 'EEEE', { locale: idLocale })}
                </p>
                <p className="text-xs text-gray-500">
                    {format(date, 'MMMM yyyy', { locale: idLocale })}
                    {!today && daysAgo !== 0 && (
                        <span className="ml-2 text-gray-400">
                            ({daysAgo > 0 ? `${daysAgo}d ago` : `in ${Math.abs(daysAgo)}d`})
                        </span>
                    )}
                </p>
            </div>
        </div>
    );
}

// Status icon component
function StatusIcon({ status }: { status: string }) {
    const iconMap: Record<string, React.ReactNode> = {
        pending_followup: <Circle className="h-4 w-4 text-slate-500" />,
        in_progress: <PlayCircle className="h-4 w-4 text-amber-500" />,
        done: <CheckCircle2 className="h-4 w-4 text-emerald-500" />,
    };
    return iconMap[status] || <Circle className="h-4 w-4 text-gray-400" />;
}

// Timeline item component
interface TimelineItemProps {
    task: AdminTask;
    isLast: boolean;
    onTaskClick?: (task: AdminTask) => void;
}

function TimelineItem({ task, isLast, onTaskClick }: TimelineItemProps) {
    const isPR = task.taskable_type?.includes('PurchaseRequest');
    const number = isPR ? task.taskable?.pr_number : task.taskable?.st_number;
    const typeColor = isPR ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700';
    const statusStyle = statusStyles[task.status] || statusStyles.pending_followup;

    return (
        <div className="relative pl-8 pb-6 last:pb-0">
            {/* Timeline line */}
            {!isLast && (
                <div className="absolute left-[11px] top-6 bottom-0 w-0.5 bg-gray-200" />
            )}

            {/* Timeline dot */}
            <div
                className={cn(
                    'absolute left-0 top-1 w-6 h-6 rounded-full flex items-center justify-center bg-white border-2',
                    task.status === 'done'
                        ? 'border-emerald-500'
                        : task.status === 'in_progress'
                            ? 'border-amber-500'
                            : 'border-slate-400'
                )}
            >
                <StatusIcon status={task.status} />
            </div>

            {/* Content */}
            <motion.div
                initial={{ opacity: 0, x: -10 }}
                animate={{ opacity: 1, x: 0 }}
                className="bg-white rounded-lg border p-4 shadow-sm hover:shadow-md transition-all cursor-pointer"
                onClick={() => onTaskClick?.(task)}
            >
                {/* Header */}
                <div className="flex items-start justify-between gap-3">
                    <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 mb-1">
                            <span className={cn(
                                'px-1.5 py-0.5 rounded text-[10px] font-semibold',
                                typeColor
                            )}>
                                {isPR ? 'PR' : 'ST'}
                            </span>
                            <span className={cn(
                                'px-1.5 py-0.5 rounded text-[10px] font-medium',
                                task.status === 'done' ? 'bg-emerald-50 text-emerald-700' :
                                    task.status === 'in_progress' ? 'bg-amber-50 text-amber-700' :
                                        'bg-slate-100 text-slate-700'
                            )}>
                                {statusStyle.label}
                            </span>
                        </div>
                        <h4 className="font-medium text-gray-900 line-clamp-1">
                            {number || 'Loading...'}
                        </h4>
                    </div>
                    <ChevronRight className="h-5 w-5 text-gray-400" />
                </div>

                {/* Meta info */}
                <div className="flex items-center gap-4 mt-2 text-xs text-gray-500">
                    <div className="flex items-center gap-1">
                        <Building2 className="h-3 w-3" />
                        <span>{task.department?.name || 'Unknown'}</span>
                    </div>
                    <div className="flex items-center gap-1">
                        <DollarSign className="h-3 w-3" />
                        <span>{formatCurrency(task.estimated_total_price || 0)}</span>
                    </div>
                    <div className="flex items-center gap-1">
                        <Clock className="h-3 w-3" />
                        <span>{format(new Date(task.entered_at), 'HH:mm', { locale: idLocale })}</span>
                    </div>
                </div>
            </motion.div>
        </div>
    );
}

// Main Timeline Component
export function PurchasingTaskTimeline({
    tasks,
    onTaskClick,
    showDateHeaders = true,
}: PurchasingTaskTimelineProps) {
    const groupedTasks = React.useMemo(() => groupTasksByDate(tasks), [tasks]);
    const dateKeys = Array.from(groupedTasks.keys());

    // Count tasks by status
    const statusCounts = React.useMemo(() => {
        const counts: Record<string, number> = {
            pending_followup: 0,
            in_progress: 0,
            done: 0,
        };
        tasks.forEach((task) => {
            if (counts[task.status] !== undefined) {
                counts[task.status]++;
            }
        });
        return counts;
    }, [tasks]);

    const handleTaskClick = (task: AdminTask) => {
        if (onTaskClick) {
            onTaskClick(task);
        } else {
            router.visit(route('purchasing.admin.tasks.show', { taskId: task.id }));
        }
    };

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            {/* Header */}
            <div className="px-5 py-4 border-b border-gray-100">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900">Task Timeline</h2>
                        <p className="text-sm text-gray-500 mt-0.5">
                            Chronological view of purchasing tasks
                        </p>
                    </div>
                </div>
            </div>

            {/* Status Legend */}
            <div className="px-5 py-2.5 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                <div className="flex items-center gap-4 text-xs">
                    {Object.entries(statusCounts).map(([status, count]) => (
                        <div key={status} className="flex items-center gap-1.5">
                            <span
                                className={cn(
                                    'w-2.5 h-2.5 rounded-full',
                                    statusStyles[status]?.dot || 'bg-gray-400'
                                )}
                            />
                            <span className="text-gray-700 font-medium">
                                {statusStyles[status]?.label || status}
                            </span>
                            <span className="text-gray-500">({count})</span>
                        </div>
                    ))}
                </div>
                <div className="text-xs text-gray-600 font-medium">
                    {tasks.length} task{tasks.length !== 1 ? 's' : ''} total
                </div>
            </div>

            {/* Timeline Content */}
            <div className="p-6">
                {tasks.length === 0 ? (
                    <div className="text-center py-12">
                        <Calendar className="h-12 w-12 text-gray-300 mx-auto mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 mb-2">
                            No tasks found
                        </h3>
                        <p className="text-gray-500 mb-4">
                            No purchasing tasks in timeline
                        </p>
                    </div>
                ) : (
                    <div className="space-y-6">
                        {dateKeys.map((dateKey) => {
                            const date = new Date(dateKey);
                            const dateTasks = groupedTasks.get(dateKey) || [];

                            return (
                                <div key={dateKey}>
                                    {showDateHeaders && <DateHeader date={date} />}
                                    <div className="mt-2">
                                        {dateTasks.map((task, taskIndex) => (
                                            <TimelineItem
                                                key={task.id}
                                                task={task}
                                                isLast={taskIndex === dateTasks.length - 1}
                                                onTaskClick={handleTaskClick}
                                            />
                                        ))}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>

            {/* Footer Help */}
            <div className="px-5 py-3 border-t border-gray-100 bg-gray-50/50">
                <div className="flex items-center gap-4 text-xs text-gray-600">
                    <span className="flex items-center gap-1.5">
                        <Info className="h-3.5 w-3.5 text-gray-400" />
                        Click task to view details
                    </span>
                    <span className="text-gray-300">•</span>
                    <span>Tasks sorted by newest first</span>
                </div>
            </div>
        </div>
    );
}

export default PurchasingTaskTimeline;
