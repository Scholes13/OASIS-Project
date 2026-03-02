import { Head, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { ArrowLeft } from 'lucide-react';
import { Card, CardHeader, CardBody } from '@/components/ui/Card';
import type { PageProps } from '@/types';

interface TeamMember {
    id: number;
    name: string;
    avatar?: string;
    completed: number;
    pending: number;
    overdue: number;
}

interface DepartmentAnalyticsProps extends PageProps {
    teamStats: {
        totalTasks: number;
        completedTasks: number;
        completionRate: number;
        avgTaskDuration: string;
    };
    members: TeamMember[];
    tasksByStatus: Array<{ status: string; count: number }>;
}

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-500',
    in_progress: 'bg-blue-500',
    completed: 'bg-green-500',
    cancelled: 'bg-gray-500',
    overdue: 'bg-red-500',
};

export default function Department({ teamStats, members, tasksByStatus }: DepartmentAnalyticsProps) {
    const maxMemberTasks = Math.max(...members.map(m => m.completed + m.pending + m.overdue), 1);
    const totalTasksByStatus = tasksByStatus.reduce((sum, s) => sum + s.count, 0) || 1;

    return (
        <>
            <Head title="Department Analytics" />

            <div className="w-full px-6 py-6 lg:px-8">
                    {/* Back Button */}
                    <div className="mb-6">
                        <Link
                            href={route('activity.task.index')}
                            className="inline-flex items-center text-sm text-gray-600 hover:text-gray-900"
                        >
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Back to Dashboard
                        </Link>
                    </div>

                    {/* Header */}
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Department Analytics</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Overview of your department's task performance
                        </p>
                    </div>

                    {/* Team Stats */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                        >
                            <Card>
                                <CardBody className="text-center">
                                    <span className="text-3xl font-bold text-primary">{teamStats.totalTasks}</span>
                                    <p className="text-sm text-gray-500">Total Tasks</p>
                                </CardBody>
                            </Card>
                        </motion.div>
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: 0.1 }}
                        >
                            <Card>
                                <CardBody className="text-center">
                                    <span className="text-3xl font-bold text-green-600">{teamStats.completedTasks}</span>
                                    <p className="text-sm text-gray-500">Completed</p>
                                </CardBody>
                            </Card>
                        </motion.div>
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: 0.2 }}
                        >
                            <Card>
                                <CardBody className="text-center">
                                    <span className="text-3xl font-bold text-blue-600">{teamStats.completionRate}%</span>
                                    <p className="text-sm text-gray-500">Completion Rate</p>
                                </CardBody>
                            </Card>
                        </motion.div>
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: 0.3 }}
                        >
                            <Card>
                                <CardBody className="text-center">
                                    <span className="text-3xl font-bold text-purple-600">{teamStats.avgTaskDuration}</span>
                                    <p className="text-sm text-gray-500">Avg Duration</p>
                                </CardBody>
                            </Card>
                        </motion.div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Team Members Performance */}
                        <Card>
                            <CardHeader>
                                <h3 className="text-lg font-semibold text-gray-900">Team Performance</h3>
                            </CardHeader>
                            <CardBody>
                                <div className="space-y-4">
                                    {members.map((member, index) => {
                                        const total = member.completed + member.pending + member.overdue;
                                        return (
                                            <motion.div
                                                key={member.id}
                                                initial={{ opacity: 0, x: -20 }}
                                                animate={{ opacity: 1, x: 0 }}
                                                transition={{ delay: index * 0.05 }}
                                            >
                                                <div className="flex items-center gap-3 mb-2">
                                                    <div className="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold text-sm">
                                                        {member.name.charAt(0)}
                                                    </div>
                                                    <div className="flex-1">
                                                        <span className="font-medium text-gray-700">{member.name}</span>
                                                    </div>
                                                    <span className="text-sm text-gray-500">{total} tasks</span>
                                                </div>
                                                <div className="flex h-2 rounded-full overflow-hidden bg-gray-200">
                                                    {member.completed > 0 && (
                                                        <motion.div
                                                            className="bg-green-500"
                                                            initial={{ width: 0 }}
                                                            animate={{ width: `${(member.completed / maxMemberTasks) * 100}%` }}
                                                            transition={{ duration: 0.5 }}
                                                        />
                                                    )}
                                                    {member.pending > 0 && (
                                                        <motion.div
                                                            className="bg-blue-500"
                                                            initial={{ width: 0 }}
                                                            animate={{ width: `${(member.pending / maxMemberTasks) * 100}%` }}
                                                            transition={{ duration: 0.5, delay: 0.1 }}
                                                        />
                                                    )}
                                                    {member.overdue > 0 && (
                                                        <motion.div
                                                            className="bg-red-500"
                                                            initial={{ width: 0 }}
                                                            animate={{ width: `${(member.overdue / maxMemberTasks) * 100}%` }}
                                                            transition={{ duration: 0.5, delay: 0.2 }}
                                                        />
                                                    )}
                                                </div>
                                            </motion.div>
                                        );
                                    })}
                                </div>
                                <div className="flex gap-4 mt-4 text-xs">
                                    <div className="flex items-center gap-1">
                                        <span className="w-3 h-3 bg-green-500 rounded-full"></span>
                                        <span>Completed</span>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <span className="w-3 h-3 bg-blue-500 rounded-full"></span>
                                        <span>Pending</span>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <span className="w-3 h-3 bg-red-500 rounded-full"></span>
                                        <span>Overdue</span>
                                    </div>
                                </div>
                            </CardBody>
                        </Card>

                        {/* Tasks by Status */}
                        <Card>
                            <CardHeader>
                                <h3 className="text-lg font-semibold text-gray-900">Tasks by Status</h3>
                            </CardHeader>
                            <CardBody>
                                <div className="space-y-4">
                                    {tasksByStatus.map((item, index) => (
                                        <motion.div
                                            key={item.status}
                                            initial={{ opacity: 0, x: 20 }}
                                            animate={{ opacity: 1, x: 0 }}
                                            transition={{ delay: index * 0.1 }}
                                        >
                                            <div className="flex justify-between text-sm mb-1">
                                                <span className="font-medium text-gray-700 capitalize">
                                                    {item.status.replace('_', ' ')}
                                                </span>
                                                <span className="text-gray-500">
                                                    {item.count} ({Math.round((item.count / totalTasksByStatus) * 100)}%)
                                                </span>
                                            </div>
                                            <div className="bg-gray-200 rounded-full h-3">
                                                <motion.div
                                                    className={`h-3 rounded-full ${statusColors[item.status] || 'bg-gray-500'}`}
                                                    initial={{ width: 0 }}
                                                    animate={{ width: `${(item.count / totalTasksByStatus) * 100}%` }}
                                                    transition={{ duration: 0.5 }}
                                                />
                                            </div>
                                        </motion.div>
                                    ))}
                                </div>
                            </CardBody>
                        </Card>
                </div>
            </div>
        </>
    );
}