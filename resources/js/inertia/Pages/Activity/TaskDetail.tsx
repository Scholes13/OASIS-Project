import { Head, Link, router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { ArrowLeft, Pencil, Trash2, Calendar, Clock, User } from 'lucide-react';
import { StatusBadge, PriorityBadge, ActivityTypeBadge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardBody } from '@/components/ui/Card';
import type { PageProps, Task } from '@/types';
import { useState } from 'react';

interface TaskDetailProps extends PageProps {
    task: Task;
}

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
}

function formatDateTime(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function isOverdue(dueDate: string, status: string): boolean {
    if (status === 'completed' || status === 'cancelled') return false;
    return new Date(dueDate) < new Date();
}

export default function TaskDetail({ task }: TaskDetailProps) {
    const [isDeleting, setIsDeleting] = useState(false);
    const overdue = isOverdue(task.due_date, task.status);

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this task?')) {
            setIsDeleting(true);
            router.delete(route('activity.task.destroy', { task: task.id }), {
                onFinish: () => setIsDeleting(false),
            });
        }
    };

    return (
        <>
            <Head title={task.task_title} />

            <div className="py-6">
                <div className="px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
                    {/* Back Button */}
                    <div className="mb-6">
                        <Link
                            href={route('activity.task.index')}
                            className="inline-flex items-center text-sm text-gray-600 hover:text-gray-900"
                        >
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Back to Tasks
                        </Link>
                    </div>

                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.3 }}
                    >
                        {/* Header Card */}
                        <Card className="mb-6">
                            <CardBody>
                                <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                    <div className="flex-1">
                                        {/* Badges */}
                                        <div className="flex flex-wrap items-center gap-2 mb-3">
                                            <ActivityTypeBadge 
                                                name={task.activity_type.name} 
                                                color={task.activity_type.color} 
                                            />
                                            <StatusBadge status={task.status} />
                                            <PriorityBadge priority={task.priority} />
                                            {overdue && (
                                                <span className="px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700 rounded-full">
                                                    Overdue
                                                </span>
                                            )}
                                        </div>

                                        {/* Title */}
                                        <h1 className="text-2xl font-bold text-gray-900 mb-2">
                                            {task.task_title}
                                        </h1>

                                        {/* Sub Activity */}
                                        {task.sub_activity && (
                                            <p className="text-sm text-gray-500">
                                                {task.sub_activity.name}
                                            </p>
                                        )}
                                    </div>

                                    {/* Actions */}
                                    <div className="flex items-center gap-2">
                                        <Link href={route('activity.task.edit', { task: task.id })}>
                                            <Button variant="secondary" size="sm">
                                                <Pencil className="w-4 h-4 mr-1" />
                                                Edit
                                            </Button>
                                        </Link>
                                        <Button 
                                            variant="danger" 
                                            size="sm" 
                                            onClick={handleDelete}
                                            isLoading={isDeleting}
                                        >
                                            <Trash2 className="w-4 h-4 mr-1" />
                                            Delete
                                        </Button>
                                    </div>
                                </div>
                            </CardBody>
                        </Card>

                        {/* Details Grid */}
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            {/* Main Content */}
                            <div className="lg:col-span-2 space-y-6">
                                {/* Description */}
                                <Card>
                                    <CardBody>
                                        <h2 className="font-semibold text-gray-900 mb-3">Description</h2>
                                        {task.task_description ? (
                                            <p className="text-gray-700 whitespace-pre-wrap">
                                                {task.task_description}
                                            </p>
                                        ) : (
                                            <p className="text-gray-400 italic">No description provided</p>
                                        )}
                                    </CardBody>
                                </Card>

                                {/* Participants */}
                                <Card>
                                    <CardBody>
                                        <h2 className="font-semibold text-gray-900 mb-3">
                                            Participants ({task.participants.length})
                                        </h2>
                                        {task.participants.length > 0 ? (
                                            <div className="space-y-3">
                                                {task.participants.map((participant) => (
                                                    <div 
                                                        key={participant.id}
                                                        className="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50"
                                                    >
                                                        <div className="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white font-medium">
                                                            {participant.user.name.charAt(0).toUpperCase()}
                                                        </div>
                                                        <div>
                                                            <p className="font-medium text-gray-900">
                                                                {participant.user.name}
                                                            </p>
                                                            <p className="text-sm text-gray-500">
                                                                {participant.user.email}
                                                            </p>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        ) : (
                                            <p className="text-gray-400 italic">No participants assigned</p>
                                        )}
                                    </CardBody>
                                </Card>

                                {/* Attachments */}
                                {task.attachments && task.attachments.length > 0 && (
                                    <Card>
                                        <CardBody>
                                            <h2 className="font-semibold text-gray-900 mb-3">
                                                Attachments ({task.attachments.length})
                                            </h2>
                                            <div className="space-y-2">
                                                {task.attachments.map((attachment) => (
                                                    <a
                                                        key={attachment.id}
                                                        href={`/storage/${attachment.filepath}`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="flex items-center gap-3 p-2 rounded-lg border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition-colors"
                                                    >
                                                        <svg className="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                        </svg>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-sm font-medium text-gray-900 truncate">
                                                                {attachment.filename}
                                                            </p>
                                                            <p className="text-xs text-gray-500">
                                                                {(attachment.filesize / 1024).toFixed(1)} KB
                                                            </p>
                                                        </div>
                                                    </a>
                                                ))}
                                            </div>
                                        </CardBody>
                                    </Card>
                                )}
                            </div>

                            {/* Sidebar */}
                            <div className="space-y-6">
                                {/* Date Info */}
                                <Card>
                                    <CardBody>
                                        <h2 className="font-semibold text-gray-900 mb-3">Details</h2>
                                        <div className="space-y-4">
                                            <div className="flex items-start gap-3">
                                                <Calendar className="w-5 h-5 text-gray-400 mt-0.5" />
                                                <div>
                                                    <p className="text-xs text-gray-500">Due Date</p>
                                                    <p className={`font-medium ${overdue ? 'text-red-600' : 'text-gray-900'}`}>
                                                        {formatDate(task.due_date)}
                                                    </p>
                                                </div>
                                            </div>

                                            {task.start_date && (
                                                <div className="flex items-start gap-3">
                                                    <Clock className="w-5 h-5 text-gray-400 mt-0.5" />
                                                    <div>
                                                        <p className="text-xs text-gray-500">Start Date</p>
                                                        <p className="font-medium text-gray-900">
                                                            {formatDate(task.start_date)}
                                                        </p>
                                                    </div>
                                                </div>
                                            )}

                                            <div className="flex items-start gap-3">
                                                <User className="w-5 h-5 text-gray-400 mt-0.5" />
                                                <div>
                                                    <p className="text-xs text-gray-500">Created By</p>
                                                    <p className="font-medium text-gray-900">
                                                        {task.creator.name}
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="pt-3 border-t border-gray-200">
                                                <p className="text-xs text-gray-500">Department</p>
                                                <p className="font-medium text-gray-900">
                                                    {task.department.name}
                                                </p>
                                            </div>
                                        </div>
                                    </CardBody>
                                </Card>

                                {/* Timestamps */}
                                <Card>
                                    <CardBody>
                                        <h2 className="font-semibold text-gray-900 mb-3">Timeline</h2>
                                        <div className="space-y-3 text-sm">
                                            <div>
                                                <p className="text-xs text-gray-500">Created</p>
                                                <p className="text-gray-700">{formatDateTime(task.created_at)}</p>
                                            </div>
                                            <div>
                                                <p className="text-xs text-gray-500">Last Updated</p>
                                                <p className="text-gray-700">{formatDateTime(task.updated_at)}</p>
                                            </div>
                                            {task.completed_at && (
                                                <div>
                                                    <p className="text-xs text-gray-500">Completed</p>
                                                    <p className="text-green-600">{formatDateTime(task.completed_at)}</p>
                                                </div>
                                            )}
                                        </div>
                                    </CardBody>
                                </Card>
                            </div>
                        </div>
                    </motion.div>
                </div>
            </div>
        </>
    );
}

