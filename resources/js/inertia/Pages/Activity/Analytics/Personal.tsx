import { Head, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { ArrowLeft } from 'lucide-react';
import { Card, CardHeader, CardBody } from '@/components/ui/Card';
import type { PageProps, ActivityStat } from '@/types';

interface PersonalAnalyticsProps extends PageProps {
    stats: ActivityStat[];
    weeklyProgress: Array<{ day: string; completed: number; created: number }>;
    tasksByType: Array<{ type: string; count: number }>;
}

export default function Personal({ stats, weeklyProgress, tasksByType }: PersonalAnalyticsProps) {
    const maxTasks = Math.max(...weeklyProgress.map(d => Math.max(d.completed, d.created)), 1);
    const maxByType = Math.max(...tasksByType.map(t => t.count), 1);

    return (
        <>
            <Head title="Personal Analytics" />

            <div className="py-6">
                <div className="px-4 sm:px-6 lg:px-8">
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
                        <h1 className="text-2xl font-bold text-gray-900">Personal Analytics</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Track your productivity and task completion patterns
                        </p>
                    </div>

                    {/* Stats Overview */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        {stats.map((stat, index) => (
                            <motion.div
                                key={stat.label}
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: index * 0.1 }}
                            >
                                <Card>
                                    <CardBody className="text-center">
                                        <span className="text-3xl font-bold text-indigo-600">{stat.value}</span>
                                        <p className="text-sm text-gray-500">{stat.label}</p>
                                    </CardBody>
                                </Card>
                            </motion.div>
                        ))}
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Weekly Progress */}
                        <Card>
                            <CardHeader>
                                <h3 className="text-lg font-semibold text-gray-900">Weekly Progress</h3>
                            </CardHeader>
                            <CardBody>
                                <div className="space-y-4">
                                    {weeklyProgress.map((day) => (
                                        <div key={day.day}>
                                            <div className="flex justify-between text-sm mb-1">
                                                <span className="font-medium text-gray-700">{day.day}</span>
                                                <span className="text-gray-500">
                                                    {day.completed} completed / {day.created} created
                                                </span>
                                            </div>
                                            <div className="flex gap-2">
                                                <div className="flex-1 bg-gray-200 rounded-full h-2">
                                                    <motion.div
                                                        className="bg-green-500 h-2 rounded-full"
                                                        initial={{ width: 0 }}
                                                        animate={{ width: `${(day.completed / maxTasks) * 100}%` }}
                                                        transition={{ duration: 0.5 }}
                                                    />
                                                </div>
                                                <div className="flex-1 bg-gray-200 rounded-full h-2">
                                                    <motion.div
                                                        className="bg-blue-500 h-2 rounded-full"
                                                        initial={{ width: 0 }}
                                                        animate={{ width: `${(day.created / maxTasks) * 100}%` }}
                                                        transition={{ duration: 0.5 }}
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                <div className="flex gap-4 mt-4 text-xs">
                                    <div className="flex items-center gap-1">
                                        <span className="w-3 h-3 bg-green-500 rounded-full"></span>
                                        <span>Completed</span>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <span className="w-3 h-3 bg-blue-500 rounded-full"></span>
                                        <span>Created</span>
                                    </div>
                                </div>
                            </CardBody>
                        </Card>

                        {/* Tasks by Type */}
                        <Card>
                            <CardHeader>
                                <h3 className="text-lg font-semibold text-gray-900">Tasks by Type</h3>
                            </CardHeader>
                            <CardBody>
                                <div className="space-y-4">
                                    {tasksByType.map((item) => (
                                        <div key={item.type}>
                                            <div className="flex justify-between text-sm mb-1">
                                                <span className="font-medium text-gray-700 capitalize">{item.type}</span>
                                                <span className="text-gray-500">{item.count} tasks</span>
                                            </div>
                                            <div className="bg-gray-200 rounded-full h-3">
                                                <motion.div
                                                    className="bg-indigo-500 h-3 rounded-full"
                                                    initial={{ width: 0 }}
                                                    animate={{ width: `${(item.count / maxByType) * 100}%` }}
                                                    transition={{ duration: 0.5 }}
                                                />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardBody>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

