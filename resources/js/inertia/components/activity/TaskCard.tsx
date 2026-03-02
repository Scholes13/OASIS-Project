import { Link, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { StatusBadge, ActivityTypeBadge } from '../ui/Badge';
import type { Task, PageProps } from '@/types';

interface TaskCardProps {
    task: Task;
    index?: number;
}

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function isOverdue(dueDate: string | null, status: string): boolean {
    if (!dueDate) return false;
    if (status === 'completed' || status === 'cancelled') return false;
    return new Date(dueDate) < new Date();
}

export default function TaskCard({ task, index = 0 }: TaskCardProps) {
    const { availableDepartments } = usePage<PageProps>().props;
    const hasMultipleDepartments = (availableDepartments as any[])?.length > 1;
    const overdue = isOverdue(task.due_date, task.status);
    const participants = task.participants || [];

    return (
        <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.2, delay: index * 0.03 }}
        >
            <Link
                href={route('activity.task.show', { task: task.id })}
                className="block bg-white rounded-lg border border-gray-200 hover:border-primary hover:shadow-md transition-all p-4"
            >
                {/* Header: Activity Type + Department Badge (if multi-dept) + Overdue Badge */}
                <div className="flex items-center gap-2 mb-2 flex-wrap">
                    <ActivityTypeBadge 
                        name={task.activity_type?.name ?? 'Unknown'} 
                        color={task.activity_type?.color ?? '#6B7280'} 
                    />
                    {hasMultipleDepartments && task.department?.code && (
                        <span className="px-1.5 py-0.5 text-[10px] font-medium bg-gray-100 text-gray-600 rounded" title={task.department.name}>
                            {task.department.code}
                        </span>
                    )}
                    {overdue && (
                        <span className="px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700 rounded-full">
                            Overdue
                        </span>
                    )}
                </div>

                {/* Task Title */}
                <h3 className="font-medium text-gray-900 line-clamp-2 mb-2">{task.task_title}</h3>

                {/* Due Date */}
                <p className={`text-sm mb-3 ${overdue ? 'text-red-600' : 'text-gray-500'}`}>
                    Due: {formatDate(task.due_date)}
                    {participants.length > 0 && (
                        <span className="ml-2">· {participants.length} participants</span>
                    )}
                </p>

                {/* Footer: Status + Participant Avatars */}
                <div className="flex items-center justify-between">
                    <StatusBadge status={task.status} />
                    
                    {/* Participant Avatars */}
                    {participants.length > 0 && (
                        <div className="flex -space-x-2">
                            {participants.slice(0, 3).map((participant: any) => {
                                const name = participant.name || participant.user?.name || 'U';
                                const id = participant.id || participant.user_id;
                                return (
                                    <div
                                        key={id}
                                        className="w-7 h-7 rounded-full bg-blue-600 border-2 border-white flex items-center justify-center text-xs font-medium text-white"
                                        title={name}
                                    >
                                        {name.charAt(0).toUpperCase()}
                                    </div>
                                );
                            })}
                            {participants.length > 3 && (
                                <div className="w-7 h-7 rounded-full bg-gray-200 border-2 border-white flex items-center justify-center text-xs font-medium text-gray-600">
                                    +{participants.length - 3}
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </Link>
        </motion.div>
    );
}

