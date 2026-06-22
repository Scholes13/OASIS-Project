import { Head, Link, router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { ArrowLeft, Pencil, Trash2, Calendar, Clock, User, Users, List, Columns, Layout } from 'lucide-react';
import { StatusBadge, PriorityBadge, ActivityTypeBadge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/button';
import type { PageProps, Task } from '@/types';
import { useState } from 'react';

interface TaskDetailProps extends PageProps {
    task: Task;
    departmentUsers?: any[];
    backdatePermission?: any;
    allowedDateRange?: any;
    backdateEnabled?: boolean;
    prioritizedActivityTypes?: any;
}

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
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

function isOverdue(dueDate: string | null, status: string): boolean {
    if (!dueDate) return false;
    if (status === 'completed' || status === 'cancelled') return false;
    return new Date(dueDate) < new Date();
}

export default function TaskDetail({ task, departmentUsers = [], backdatePermission, allowedDateRange, backdateEnabled, prioritizedActivityTypes }: TaskDetailProps) {
    const [isDeleting, setIsDeleting] = useState(false);
    const overdue = isOverdue(task.due_date, task.status);

    const handleEditClick = () => {
        router.visit(route('activity.task.index', { task: task.id, modal: 'edit' }));
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this task?')) {
            setIsDeleting(true);
            router.delete(route('activity.task.destroy', { task: task.id }), {
                onFinish: () => setIsDeleting(false),
            });
        }
    };

    return (
        <div className="w-full pb-12">
            <Head title={task.task_title} />

            {/* Top Navigation Bar */}
            <div className="bg-white border-b border-gray-200 sticky top-0 z-30">
                <div className="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between h-16">
                        <div className="flex items-center gap-4">
                            <Link
                                href={route('activity.task.index')}
                                className="p-2 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                            >
                                <ArrowLeft className="w-5 h-5" />
                            </Link>
                            <div className="h-6 w-px bg-gray-200 mx-2 hidden sm:block"></div>
                            <nav className="hidden sm:flex" aria-label="Breadcrumb">
                                <ol className="flex items-center space-x-2">
                                    <li>
                                        <Link href={route('activity.task.index')} className="text-sm font-medium text-gray-500 hover:text-gray-700">Tasks</Link>
                                    </li>
                                    <li>
                                        <div className="flex items-center">
                                            <span className="text-gray-300 mx-2">/</span>
                                            <span className="text-sm font-medium text-gray-900" aria-current="page">TASK-{task.id}</span>
                                        </div>
                                    </li>
                                </ol>
                            </nav>
                        </div>

                        <div className="flex items-center gap-3">
                            <Button variant="secondary" size="sm" className="shadow-sm border-gray-300" onClick={handleEditClick}>
                                <Pencil className="w-4 h-4 mr-2" />
                                Edit Task
                            </Button>
                            <Button
                                variant="danger"
                                size="sm"
                                onClick={handleDelete}
                                isLoading={isDeleting}
                                className="shadow-sm"
                            >
                                <Trash2 className="w-4 h-4 mr-2" />
                                Delete
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div className="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3 }}
                >
                    {/* Header Section */}
                    <div className="mb-8">
                        <div className="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                            <div className="flex-1 min-w-0">
                                <div className="flex items-center gap-3 mb-4">
                                    {task.activity_type && (
                                        <ActivityTypeBadge
                                            name={task.activity_type.name}
                                            color={task.activity_type.color}
                                        />
                                    )}
                                    <StatusBadge status={task.status} />
                                    <PriorityBadge priority={task.priority} />
                                    {overdue && (
                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Overdue
                                        </span>
                                    )}
                                </div>
                                <h1 className="text-3xl sm:text-4xl font-bold text-gray-900 tracking-tight leading-tight mb-2">
                                    {task.task_title}
                                </h1>
                                {task.sub_activity && (
                                    <p className="text-lg text-gray-500 font-medium flex items-center gap-2">
                                        <span className="w-2 h-2 rounded-full bg-gray-300"></span>
                                        {task.sub_activity.name}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 xl:grid-cols-3 gap-8">
                        {/* Main Content Column (2/3 width) */}
                        <div className="xl:col-span-2 space-y-8">
                            {/* Description Card */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                                <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center gap-2">
                                    <div className="p-1.5 bg-blue-50 text-blue-600 rounded-lg">
                                        <List className="w-5 h-5" />
                                    </div>
                                    <h3 className="text-lg font-semibold text-gray-900">Description</h3>
                                </div>
                                <div className="p-6 md:p-8">
                                    {task.task_description ? (
                                        <div className="prose prose-slate max-w-none text-gray-600 leading-relaxed text-base">
                                            {task.task_description.split('\n').map((line, i) => (
                                                <p key={i} className="mb-4 last:mb-0">{line}</p>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="flex flex-col items-center justify-center py-12 text-center bg-gray-50 rounded-lg border border-dashed border-gray-200">
                                            <p className="text-gray-400 mb-1">No description provided</p>
                                            <p className="text-sm text-gray-400">Add more details to this task to help your team.</p>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Participants Section */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                                <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <div className="p-1.5 bg-blue-50 text-blue-600 rounded-lg">
                                            <Users className="w-5 h-5" />
                                        </div>
                                        <h3 className="text-lg font-semibold text-gray-900">Participants</h3>
                                    </div>
                                    <span className="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                        {(task.participants ?? []).length} Members
                                    </span>
                                </div>
                                <div className="p-6">
                                    {(task.participants ?? []).length > 0 ? (
                                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                            {(task.participants ?? []).map((participant) => (
                                                <div
                                                    key={participant.id}
                                                    className="flex items-center gap-4 p-3 rounded-xl border border-gray-100 bg-gray-50 hover:bg-white hover:border-gray-200 hover:shadow-sm transition-all duration-200 group"
                                                >
                                                    <div className="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow-sm ring-2 ring-white group-hover:ring-primary transition-all">
                                                        {participant.name?.charAt(0)?.toUpperCase() || '?'}
                                                    </div>
                                                    <div className="min-w-0">
                                                        <p className="font-semibold text-gray-900 truncate">
                                                            {participant.name || 'Unknown User'}
                                                        </p>
                                                        <p className="text-xs text-gray-500 truncate">
                                                            {participant.email || '-'}
                                                        </p>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-gray-500 italic text-center py-6">No participants assigned to this task.</p>
                                    )}
                                </div>
                            </div>

                            {/* Attachments Section */}
                            {task.attachments && task.attachments.length > 0 && (
                                <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                                    <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center gap-2">
                                        <div className="p-1.5 bg-amber-50 text-amber-600 rounded-lg">
                                            <Columns className="w-5 h-5" />
                                        </div>
                                        <h3 className="text-lg font-semibold text-gray-900">Attachments</h3>
                                    </div>
                                    <div className="p-6">
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            {task.attachments.map((attachment) => (
                                                <a
                                                    key={attachment.id}
                                                    href={`/storage/${attachment.filepath}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="group flex items-start p-4 rounded-xl border border-gray-200 hover:border-primary/30 hover:shadow-md hover:bg-slate-50/50 transition-all duration-300"
                                                >
                                                    <div className="p-3 bg-red-50 text-red-500 rounded-lg mr-4 group-hover:bg-red-100 transition-colors">
                                                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <p className="font-semibold text-gray-900 truncate group-hover:text-primary transition-colors">
                                                            {attachment.filename}
                                                        </p>
                                                        <p className="text-xs text-gray-500 mt-1">
                                                            FILE • {(attachment.filesize / 1024).toFixed(1)} KB
                                                        </p>
                                                    </div>
                                                </a>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Sidebar Column (1/3 width) */}
                        <div className="space-y-8">
                            {/* Key Details Card */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden sticky top-24">
                                <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                    <h3 className="text-lg font-semibold text-gray-900">Task Details</h3>
                                </div>
                                <div className="p-6 space-y-6">
                                    <div className="flex gap-4">
                                        <div className="p-2 bg-blue-50 text-blue-600 rounded-lg h-fit">
                                            <Calendar className="w-5 h-5" />
                                        </div>
                                        <div>
                                            <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">Due Date</p>
                                            <p className={`text-base font-semibold mt-0.5 ${overdue ? 'text-red-600' : 'text-gray-900'}`}>
                                                {formatDate(task.due_date)}
                                            </p>
                                            {overdue && <p className="text-xs text-red-500 mt-1 font-medium">Due date passed</p>}
                                        </div>
                                    </div>

                                    <div className="border-t border-gray-100 my-4"></div>

                                    <div className="flex gap-4">
                                        <div className="p-2 bg-purple-50 text-purple-600 rounded-lg h-fit">
                                            <User className="w-5 h-5" />
                                        </div>
                                        <div>
                                            <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">Owner</p>
                                            <p className="text-base font-semibold text-gray-900 mt-0.5">
                                                {task.creator?.name || 'Unknown'}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex gap-4">
                                        <div className="p-2 bg-orange-50 text-orange-600 rounded-lg h-fit">
                                            <Layout className="w-5 h-5" />
                                        </div>
                                        <div>
                                            <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">Department</p>
                                            <p className="text-base font-semibold text-gray-900 mt-0.5">
                                                {task.department?.name || 'N/A'}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="border-t border-gray-100 my-4 pt-4">
                                        <h4 className="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                            <Clock className="w-4 h-4 text-gray-400" />
                                            Timeline Activity
                                        </h4>
                                        <div className="space-y-4 relative pl-4 border-l-2 border-gray-100 ml-2">
                                            <div className="relative">
                                                <div className="absolute -left-[21px] top-1.5 w-3 h-3 rounded-full bg-gray-200 border-2 border-white"></div>
                                                <p className="text-xs text-gray-500">Created</p>
                                                <p className="text-sm font-medium text-gray-900">{formatDateTime(task.created_at)}</p>
                                            </div>
                                            <div className="relative">
                                                <div className="absolute -left-[21px] top-1.5 w-3 h-3 rounded-full bg-blue-100 border-2 border-white"></div>
                                                <p className="text-xs text-gray-500">Last Updated</p>
                                                <p className="text-sm font-medium text-gray-900">{formatDateTime(task.updated_at)}</p>
                                            </div>
                                            {task.completed_at && (
                                                <div className="relative">
                                                    <div className="absolute -left-[21px] top-1.5 w-3 h-3 rounded-full bg-green-500 border-2 border-white shadow-sm"></div>
                                                    <p className="text-xs text-gray-500">Completed</p>
                                                    <p className="text-sm font-medium text-green-600">{formatDateTime(task.completed_at)}</p>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </motion.div>
            </div>

        </div>
    );
}
