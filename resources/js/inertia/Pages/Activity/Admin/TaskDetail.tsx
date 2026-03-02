import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Calendar, Clock, FileText, Paperclip, User, Users } from 'lucide-react';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { PageProps, Task } from '@/types';

const statusConfig: Record<string, { label: string; variant: 'success' | 'info' | 'warning' | 'danger' | 'default' }> = {
    planned: { label: 'Planned', variant: 'warning' },
    in_progress: { label: 'In Progress', variant: 'info' },
    completed: { label: 'Completed', variant: 'success' },
    cancelled: { label: 'Cancelled', variant: 'danger' },
};

function formatDate(d: string) {
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatDateTime(d: string) {
    return new Date(d).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

interface TaskDetailProps extends PageProps {
    task: Task;
}

export default function TaskDetail({ task }: TaskDetailProps) {
    return (
        <>
            <Head title={`Task: ${task.task_title}`} />

            <div className="w-full px-6 py-6 lg:px-8 max-w-4xl">
                {/* Header */}
                <div className="flex items-center gap-4 mb-6">
                    <button onClick={() => window.history.back()}>
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="w-4 h-4 mr-1" strokeWidth={1.5} />
                            Back
                        </Button>
                    </button>
                    <div className="flex-1">
                        <div className="flex items-center gap-3">
                            <h1 className="text-xl font-bold text-gray-900">{task.task_title}</h1>
                            <Badge variant={statusConfig[task.status]?.variant || 'default'}>
                                {statusConfig[task.status]?.label || task.status}
                            </Badge>
                            <Badge variant={task.priority === 'high' ? 'danger' : task.priority === 'medium' ? 'warning' : 'default'}>
                                {task.priority ? task.priority.charAt(0).toUpperCase() + task.priority.slice(1) : 'Medium'}
                            </Badge>
                        </div>
                        <p className="text-sm text-gray-500 mt-1">
                            {task.department?.name} · Read-only view
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main Content */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Description */}
                        <div className="bg-white rounded-xl border border-gray-200 p-5">
                            <h3 className="text-sm font-semibold text-gray-900 flex items-center gap-2 mb-3">
                                <FileText className="w-4 h-4 text-primary" strokeWidth={1.5} />
                                Deskripsi
                            </h3>
                            <p className="text-sm text-gray-700 whitespace-pre-wrap">
                                {task.task_description || 'Tidak ada deskripsi.'}
                            </p>
                        </div>

                        {/* Participants */}
                        <div className="bg-white rounded-xl border border-gray-200 p-5">
                            <h3 className="text-sm font-semibold text-gray-900 flex items-center gap-2 mb-3">
                                <Users className="w-4 h-4 text-primary" strokeWidth={1.5} />
                                Participants ({task.participants?.length || 0})
                            </h3>
                            {(!task.participants || task.participants.length === 0) ? (
                                <p className="text-sm text-gray-400">Tidak ada participant.</p>
                            ) : (
                                <div className="space-y-2">
                                    {task.participants.map((p) => (
                                        <div key={p.id} className="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50">
                                            <div className="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-xs font-medium text-white">
                                                {(p.name || p.user?.name || '?').charAt(0).toUpperCase()}
                                            </div>
                                            <span className="text-sm text-gray-900">{p.name || p.user?.name || 'Unknown'}</span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* Attachments */}
                        {task.attachments && task.attachments.length > 0 && (
                            <div className="bg-white rounded-xl border border-gray-200 p-5">
                                <h3 className="text-sm font-semibold text-gray-900 flex items-center gap-2 mb-3">
                                    <Paperclip className="w-4 h-4 text-primary" strokeWidth={1.5} />
                                    Attachments ({task.attachments.length})
                                </h3>
                                <div className="space-y-2">
                                    {task.attachments.map((att) => (
                                        <a
                                            key={att.id}
                                            href={att.url || '#'}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 text-sm text-primary hover:text-primary"
                                        >
                                            <Paperclip className="w-4 h-4" />
                                            {att.original_name || att.filename}
                                            <span className="text-xs text-gray-400 ml-auto">
                                                {(att.filesize / 1024).toFixed(1)} KB
                                            </span>
                                        </a>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        <div className="bg-white rounded-xl border border-gray-200 p-5">
                            <h3 className="text-sm font-semibold text-gray-900 mb-4">Detail</h3>
                            <dl className="space-y-3">
                                {[
                                    { label: 'Activity Type', value: task.activity_type?.name, color: task.activity_type?.color },
                                    { label: 'Sub Activity', value: task.sub_activity?.name || '-' },
                                    { label: 'Department', value: task.department?.name },
                                    { label: 'Pembuat', value: task.creator?.name },
                                    { label: 'Tanggal Task', value: task.task_date ? formatDate(task.task_date) : '-' },
                                    { label: 'Due Date', value: task.due_date ? formatDate(task.due_date) : '-' },
                                    { label: 'Mulai', value: task.created_at ? formatDateTime(task.created_at) : '-' },
                                    { label: 'Selesai', value: task.completed_at ? formatDateTime(task.completed_at) : '-' },
                                ].map((item) => (
                                    <div key={item.label}>
                                        <dt className="text-xs text-gray-500">{item.label}</dt>
                                        <dd className="text-sm font-medium text-gray-900 flex items-center gap-1.5">
                                            {item.color && <span className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: item.color }} />}
                                            {item.value || '-'}
                                        </dd>
                                    </div>
                                ))}
                                <div>
                                    <dt className="text-xs text-gray-500">Created</dt>
                                    <dd className="text-sm text-gray-700">{formatDateTime(task.created_at)}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
