import { Link } from '@inertiajs/react';
import { Calendar } from 'lucide-react';
import { cn } from '@/lib/utils';
import { format, isToday, isTomorrow, parseISO } from 'date-fns';

interface TaskBasic {
    id: number;
    title: string;
    due_date: string;
    is_critical?: boolean;
}

interface UpcomingTasksProps {
    tasks: TaskBasic[];
}

export function UpcomingTasks({ tasks }: UpcomingTasksProps) {
    return (
        <div className="bg-white rounded-xl border border-gray-200 p-5 shadow-sm flex-1 flex flex-col">
            <h3 className="text-base font-bold text-gray-900 mb-4 flex items-center justify-between">
                Upcoming
                <span className="text-[10px] text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full font-medium">
                    Next 7 Days
                </span>
            </h3>
            <div className="space-y-3">
                {tasks?.length > 0 ? (
                    tasks.map(task => (
                        <Link key={task.id} href={route('activity.task.show', { task: task.id })}>
                            <div className="flex items-start gap-3 p-2.5 rounded-lg hover:bg-gray-50 transition-all cursor-pointer border border-transparent hover:border-gray-100 group">
                                <div className={cn(
                                    "mt-1.5 w-2 h-2 rounded-full flex-shrink-0 ring-2 ring-white shadow-sm", 
                                    task.is_critical ? "bg-red-500" : "bg-amber-400"
                                )} />
                                <div className="min-w-0 flex-1">
                                    <p className="text-base font-medium text-gray-900 truncate group-hover:text-indigo-600 transition-colors">
                                        {task.title}
                                    </p>
                                    <p className={cn(
                                        "text-sm font-medium mt-0.5", 
                                        task.is_critical ? "text-red-500" : "text-gray-400"
                                    )}>
                                        {formatDueDate(task.due_date)}
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
    );
}

function formatDueDate(dateStr: string): string {
    const date = parseISO(dateStr);
    if (isToday(date)) return 'Today';
    if (isTomorrow(date)) return 'Tomorrow';
    return format(date, 'EEE, MMM d');
}
