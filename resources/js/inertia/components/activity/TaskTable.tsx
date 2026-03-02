import { Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { StatusBadge, ActivityTypeBadge } from '../ui/Badge';
import type { Task, PaginatedData } from '@/types';

interface TaskTableProps {
    tasks: PaginatedData<Task>;
    view: 'overview' | 'list';
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

export default function TaskTable({ tasks, view }: TaskTableProps) {
    // Safe access with fallback to empty array
    const taskData = tasks?.data ?? [];
    const displayedTasks = view === 'overview' ? taskData.slice(0, 5) : taskData;

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            {/* Header */}
            {view === 'overview' && (
                <div className="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                    <h3 className="font-semibold text-gray-900">Recent Tasks</h3>
                    <Link
                        href={route('activity.task.index', { view: 'list' })}
                        className="text-sm text-primary hover:text-primary"
                    >
                        View all
                    </Link>
                </div>
            )}

            {/* Table */}
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Task
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Due Date
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Participants
                            </th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {displayedTasks.length === 0 ? (
                            <tr>
                                <td colSpan={5} className="px-4 py-8 text-center text-gray-500">
                                    No tasks found
                                </td>
                            </tr>
                        ) : (
                            displayedTasks.map((task, index) => {
                                const overdue = isOverdue(task.due_date, task.status);
                                return (
                                    <motion.tr
                                        key={task.id}
                                        initial={{ opacity: 0 }}
                                        animate={{ opacity: 1 }}
                                        transition={{ duration: 0.2, delay: index * 0.02 }}
                                        className="hover:bg-gray-50 cursor-pointer"
                                        onClick={() => window.location.href = route('activity.task.show', { task: task.id })}
                                    >
                                        <td className="px-4 py-4">
                                            <div className="flex flex-col">
                                                <span className="font-medium text-gray-900 line-clamp-1">
                                                    {task.task_title}
                                                </span>
                                                {overdue && (
                                                    <span className="text-xs text-red-600 mt-0.5">Overdue</span>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-4 py-4">
                                            <ActivityTypeBadge 
                                                name={task.activity_type?.name ?? 'Unknown'} 
                                                color={task.activity_type?.color ?? '#6B7280'} 
                                            />
                                        </td>
                                        <td className={`px-4 py-4 text-sm ${overdue ? 'text-red-600 font-medium' : 'text-gray-600'}`}>
                                            {formatDate(task.due_date)}
                                        </td>
                                        <td className="px-4 py-4">
                                            <StatusBadge status={task.status} />
                                        </td>
                                        <td className="px-4 py-4">
                                            {task.participants && task.participants.length > 0 ? (
                                                <div className="flex -space-x-2">
                                                    {task.participants.slice(0, 3).map((participant: any) => {
                                                        // Handle both formats: direct User or TaskParticipant with user relation
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
                                                    {task.participants.length > 3 && (
                                                        <div className="w-7 h-7 rounded-full bg-gray-200 border-2 border-white flex items-center justify-center text-xs font-medium text-gray-600">
                                                            +{task.participants.length - 3}
                                                        </div>
                                                    )}
                                                </div>
                                            ) : (
                                                <span className="text-sm text-gray-400">-</span>
                                            )}
                                        </td>
                                    </motion.tr>
                                );
                            })
                        )}
                    </tbody>
                </table>
            </div>

            {/* Pagination (List view only) */}
            {view === 'list' && tasks?.meta?.last_page && tasks.meta.last_page > 1 && (
                <div className="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
                    <div className="text-sm text-gray-600">
                        Showing {tasks.meta.from ?? 0} to {tasks.meta.to ?? 0} of {tasks.meta.total ?? 0} results
                    </div>
                    <div className="flex gap-1">
                        {tasks.meta.links?.map((link, index) => (
                            <Link
                                key={index}
                                href={link.url || '#'}
                                className={`px-3 py-1.5 text-sm rounded-md ${
                                    link.active
                                        ? 'bg-primary text-white'
                                        : link.url
                                        ? 'text-gray-600 hover:bg-gray-100'
                                        : 'text-gray-300 cursor-not-allowed'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                preserveScroll
                            />
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

