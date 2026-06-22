import { Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import type { RecentAdminTask } from './dashboardTypes';

interface RecentTasksListProps {
    recentTasks: RecentAdminTask[];
}

export function RecentTasksList({ recentTasks }: RecentTasksListProps) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3, delay: 1.0 }}
            className="bg-white rounded-xl border border-gray-100 overflow-hidden"
        >
            <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 className="text-base font-semibold text-gray-900">Recent Tasks</h3>
                <Link
                    href={route('purchasing.admin.tasks')}
                    className="text-sm text-primary hover:text-primary font-medium"
                >
                    View All →
                </Link>
            </div>
            <div className="divide-y divide-gray-100">
                {recentTasks.length === 0 ? (
                    <div className="px-6 py-8 text-center text-gray-500">No recent tasks</div>
                ) : (
                    recentTasks.map((task) => (
                        <div key={task.id} className="px-6 py-4 hover:bg-gray-50 transition-colors">
                            <div className="flex items-center justify-between">
                                <div className="flex-1">
                                    <p className="text-sm font-medium text-gray-900">
                                        {task.taskable.pr_number || task.taskable.st_number}
                                    </p>
                                    <p className="text-xs text-gray-500 mt-1">
                                        {task.department.name}
                                        {task.assigned_admin && ` • ${task.assigned_admin.name}`}
                                    </p>
                                </div>
                                <span
                                    className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${task.status === 'pending_followup'
                                        ? 'bg-amber-100 text-amber-700'
                                        : task.status === 'in_progress'
                                            ? 'bg-blue-100 text-blue-700'
                                            : 'bg-emerald-100 text-emerald-700'
                                        }`}
                                >
                                    {task.status === 'pending_followup' ? 'Pending' : task.status === 'in_progress' ? 'In Progress' : 'Done'}
                                </span>
                            </div>
                        </div>
                    ))
                )}
            </div>
        </motion.div>
    );
}
