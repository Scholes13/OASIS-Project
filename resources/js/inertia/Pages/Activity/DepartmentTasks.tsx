import { Head, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { ArrowLeft } from 'lucide-react';
import { Card, CardBody } from '@/components/ui/Card';
import type { PageProps, PaginatedData, Task } from '@/types';
import TaskCard from '@/components/activity/TaskCard';

interface DepartmentTasksProps extends PageProps {
    tasks: PaginatedData<Task>;
}

export default function DepartmentTasks({ tasks }: DepartmentTasksProps) {
    return (
        <>
            <Head title="Department Tasks" />

            <div className="w-full px-6 py-6 lg:px-8">
                <div className="w-full">
                    {/* Back Button */}
                    <div className="mb-6">
                        <Link
                            href={route('activity.task.index')}
                            className="inline-flex items-center text-sm text-gray-600 hover:text-gray-900"
                        >
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Back to My Tasks
                        </Link>
                    </div>

                    {/* Header */}
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Department Tasks</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Join tasks from your department colleagues
                        </p>
                    </div>

                    {/* Task Grid */}
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
                    >
                        {tasks.data.length === 0 ? (
                            <Card className="col-span-full">
                                <CardBody>
                                    <div className="text-center py-8">
                                        <svg className="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <h3 className="text-lg font-medium text-gray-900 mb-1">No joinable tasks</h3>
                                        <p className="text-gray-500">
                                            There are no tasks from your department that you can join.
                                        </p>
                                    </div>
                                </CardBody>
                            </Card>
                        ) : (
                            tasks.data.map((task, index) => (
                                <TaskCard key={task.id} task={task} index={index} />
                            ))
                        )}
                    </motion.div>

                    {/* Pagination */}
                    {tasks.meta.last_page > 1 && (
                        <div className="mt-6 flex items-center justify-center gap-1">
                            {tasks.meta.links.map((link, index) => (
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
                    )}
                </div>
            </div>
        </>
    );
}

