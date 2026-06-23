import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Building2,
    Calendar,
    Clock,
    DollarSign,
    User,
    FileText,
    Package,
    ExternalLink,
    CheckCircle2,
    PlayCircle,
    AlertTriangle,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { showToast } from '@/components/ui/toast';
import { CompleteTaskModal } from '@/components/purchasing-admin/CompleteTaskModal';
import type { AdminTask } from '@/components/purchasing-admin/types';
import type { PageProps } from '@/types';

interface TaskDetailProps extends PageProps {
    task: AdminTask & {
        business_unit?: { id: number; name: string };
        taskable?: {
            id: number;
            pr_number?: string;
            st_number?: string;
            used_for?: string;
            items?: Array<{
                id: number;
                description: string;
                quantity: number;
                estimated_unit_price: number;
                estimated_total_price: number;
                realized_unit_price?: number;
                realized_total_price?: number;
            }>;
        };
    };
}

// Format currency
const formatCurrency = (amount: number | null | undefined) => {
    if (amount === null || amount === undefined) return '-';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

// Format date
const formatDate = (dateString: string | null | undefined) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Format duration
const formatDuration = (minutes: number | null | undefined) => {
    if (minutes === null || minutes === undefined) return '-';
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}m`;
};

// Status badge component
function StatusBadge({ status }: { status: string }) {
    const styles: Record<string, { bg: string; text: string; label: string }> = {
        pending_followup: { bg: 'bg-slate-100', text: 'text-slate-700', label: 'Pending' },
        in_progress: { bg: 'bg-amber-100', text: 'text-amber-700', label: 'In Progress' },
        done: { bg: 'bg-emerald-100', text: 'text-emerald-700', label: 'Completed' },
    };
    const style = styles[status] || styles.pending_followup;

    return (
        <span className={cn('inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium', style.bg, style.text)}>
            {style.label}
        </span>
    );
}

export default function TaskDetail({ task }: TaskDetailProps) {
    const { auth } = usePage<PageProps>().props;
    const [showCompleteModal, setShowCompleteModal] = useState(false);
    const [isLoading, setIsLoading] = useState(false);

    if (!task) {
        return (
            <>
                <Head title="Task Not Found" />
                <div className="py-6">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center py-12">
                            <h2 className="text-xl font-semibold text-gray-900">Task Not Found</h2>
                            <p className="text-gray-500 mt-2">The requested task could not be found.</p>
                            <Link
                                href={route('purchasing.admin.tasks')}
                                className="mt-4 inline-flex items-center text-primary hover:text-primary"
                            >
                                <ArrowLeft className="w-4 h-4 mr-2" />
                                Back to Tasks
                            </Link>
                        </div>
                    </div>
                </div>
            </>
        );
    }

    const isPR = task.taskable_type?.includes('PurchaseRequest');
    const number = isPR ? task.taskable?.pr_number : task.taskable?.st_number;
    const isAssignedToMe = task.assigned_admin_id === auth?.user?.id;
    const isReadonly = Boolean(auth?.user?.is_purchasing_readonly);
    const canClaim = !isReadonly && task.status === 'pending_followup' && !task.assigned_admin_id;
    const canStart = !isReadonly && task.status === 'pending_followup' && isAssignedToMe;
    const canComplete = !isReadonly && task.status === 'in_progress' && isAssignedToMe;

    const handleClaim = () => {
        setIsLoading(true);
        router.post(route('purchasing.admin.tasks.claim', { taskId: task.id }), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showToast.success('Task claimed', 'You have claimed this task');
            },
            onFinish: () => setIsLoading(false),
        });
    };

    const handleStart = () => {
        setIsLoading(true);
        router.post(route('purchasing.admin.tasks.start', { taskId: task.id }), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showToast.success('Task started', 'Task is now in progress');
            },
            onFinish: () => setIsLoading(false),
        });
    };

    return (
        <>
            <Head title={`Task - ${number}`} />
            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <Link
                            href={route('purchasing.admin.tasks')}
                            className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4"
                        >
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Back to Tasks
                        </Link>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Left Column: Task Details */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Task Overview Card */}
                            <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                                <div className="px-5 py-4 border-b border-gray-100">
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <div className="flex items-center gap-3 mb-3">
                                                {/* Type Badge */}
                                                <span className={cn(
                                                    'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium',
                                                    isPR ? 'bg-blue-50 text-blue-700' : 'bg-purple-100 text-purple-700'
                                                )}>
                                                    {isPR ? (
                                                        <><FileText className="w-3.5 h-3.5 mr-1" /> Purchase Request</>
                                                    ) : (
                                                        <><Package className="w-3.5 h-3.5 mr-1" /> Stock Request</>
                                                    )}
                                                </span>
                                                <StatusBadge status={task.status} />
                                            </div>
                                            <h2 className="text-xl font-semibold text-gray-900">{number || 'N/A'}</h2>
                                        </div>
                                    </div>
                                </div>

                                <div className="p-6 space-y-6">
                                    {/* Task Information Grid */}
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-500 mb-1">Business Unit</label>
                                            <p className="text-base text-gray-900">{task.business_unit?.name || '-'}</p>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-500 mb-1">Department</label>
                                            <p className="text-base text-gray-900">{task.department?.name || '-'}</p>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-500 mb-1">Assigned To</label>
                                            <p className="text-base text-gray-900">
                                                {task.assigned_admin?.name || <span className="text-amber-600">Unassigned</span>}
                                            </p>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-500 mb-1">Entered Date</label>
                                            <p className="text-base text-gray-900">{formatDate(task.entered_at)}</p>
                                        </div>
                                        {task.started_at && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-500 mb-1">Started Date</label>
                                                <p className="text-base text-gray-900">{formatDate(task.started_at)}</p>
                                            </div>
                                        )}
                                        {task.completed_at && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-500 mb-1">Completed Date</label>
                                                <p className="text-base text-gray-900">{formatDate(task.completed_at)}</p>
                                            </div>
                                        )}
                                        {task.followup_time_minutes !== null && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-500 mb-1">Follow-up Time</label>
                                                <p className="text-base text-gray-900">{formatDuration(task.followup_time_minutes)}</p>
                                            </div>
                                        )}
                                        {task.completion_time_minutes !== null && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-500 mb-1">Completion Time</label>
                                                <p className="text-base text-gray-900">{formatDuration(task.completion_time_minutes)}</p>
                                            </div>
                                        )}
                                    </div>

                                    {/* Price Information */}
                                    <div className="pt-6 border-t border-gray-100">
                                        <h3 className="text-base font-semibold text-gray-900 mb-4">Price Information</h3>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-500 mb-1">Estimated Price</label>
                                                <p className="text-lg font-semibold text-gray-900">
                                                    {formatCurrency(task.estimated_total_price)}
                                                </p>
                                            </div>
                                            {task.realized_total_price !== null && (
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-500 mb-1">Realized Price</label>
                                                    <p className="text-lg font-semibold text-gray-900">
                                                        {formatCurrency(task.realized_total_price)}
                                                    </p>
                                                </div>
                                            )}
                                            {task.savings_amount !== null && (
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-500 mb-1">Savings Amount</label>
                                                    <p className={cn(
                                                        'text-lg font-semibold',
                                                        task.savings_amount >= 0 ? 'text-emerald-600' : 'text-red-600'
                                                    )}>
                                                        {formatCurrency(task.savings_amount)}
                                                    </p>
                                                </div>
                                            )}
                                            {task.savings_percentage !== null && (
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-500 mb-1">Savings Percentage</label>
                                                    <p className={cn(
                                                        'text-lg font-semibold',
                                                        task.savings_percentage >= 0 ? 'text-emerald-600' : 'text-red-600'
                                                    )}>
                                                        {task.savings_percentage?.toFixed(2)}%
                                                    </p>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Notes */}
                                    {task.notes && (
                                        <div className="pt-6 border-t border-gray-100">
                                            <h3 className="text-base font-semibold text-gray-900 mb-2">Notes</h3>
                                            <p className="text-sm text-gray-600 whitespace-pre-wrap">{task.notes}</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Right Column: Actions */}
                        <div className="lg:col-span-1">
                            <div className="bg-white rounded-xl border border-gray-100 overflow-hidden sticky top-6">
                                <div className="px-5 py-4 border-b border-gray-100">
                                    <h3 className="text-base font-semibold text-gray-900">Actions</h3>
                                </div>
                                <div className="p-5 space-y-4">
                                    {/* Claim Task Button */}
                                    {canClaim && (
                                        <div>
                                            <button
                                                onClick={handleClaim}
                                                disabled={isLoading}
                                                className="w-full inline-flex items-center justify-center px-4 py-2.5 bg-primary text-white rounded-lg hover:bg-blue-600 transition-colors font-medium shadow-sm disabled:opacity-50"
                                            >
                                                <User className="w-5 h-5 mr-2" />
                                                Claim Task
                                            </button>
                                            <p className="text-xs text-center mt-2 text-gray-500">
                                                Claim this task to start working on it
                                            </p>
                                        </div>
                                    )}

                                    {/* Start Task Button */}
                                    {canStart && (
                                        <div>
                                            <button
                                                onClick={handleStart}
                                                disabled={isLoading}
                                                className="w-full inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium shadow-sm disabled:opacity-50"
                                            >
                                                <PlayCircle className="w-5 h-5 mr-2" />
                                                Start Task
                                            </button>
                                            <p className="text-xs text-center mt-2 text-gray-500">
                                                Begin working on this task
                                            </p>
                                        </div>
                                    )}

                                    {/* Complete Task Button */}
                                    {canComplete && (
                                        <div>
                                            <button
                                                onClick={() => setShowCompleteModal(true)}
                                                className="w-full inline-flex items-center justify-center px-4 py-2.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors font-medium shadow-sm"
                                            >
                                                <CheckCircle2 className="w-5 h-5 mr-2" />
                                                Complete Task
                                            </button>
                                            <p className="text-xs text-center mt-2 text-gray-500">
                                                Mark this task as completed
                                            </p>
                                        </div>
                                    )}

                                    {/* Task assigned to someone else */}
                                    {task.status !== 'done' && task.assigned_admin_id && !isAssignedToMe && (
                                        <div className="text-center py-3 bg-gray-50 rounded-lg">
                                            <p className="text-sm text-gray-500">
                                                This task is assigned to{' '}
                                                <span className="font-medium text-gray-700">{task.assigned_admin?.name}</span>
                                            </p>
                                        </div>
                                    )}

                                    {/* View Source Document */}
                                    <div className="pt-3 border-t border-gray-100">
                                        <a
                                            href={isPR
                                                ? route('purchase-requests.pdf-public', { purchaseRequest: task.taskable_id })
                                                : route('stock-requests.pdf-public', { stockRequest: task.taskable_id })
                                            }
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg transition-colors shadow-sm border border-gray-300 bg-gray-50 hover:bg-gray-100 text-gray-700"
                                        >
                                            <ExternalLink className="w-4 h-4 mr-2" />
                                            View {isPR ? 'Purchase Request' : 'Stock Request'}
                                        </a>
                                    </div>

                                    {/* Task Completed Status */}
                                    {task.status === 'done' && (
                                        <div className="pt-3 border-t border-gray-100">
                                            <div className="flex items-center justify-center text-sm text-emerald-600">
                                                <CheckCircle2 className="w-5 h-5 mr-2" />
                                                Task Completed
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Complete Task Modal */}
            <CompleteTaskModal
                task={task}
                open={showCompleteModal}
                onClose={() => setShowCompleteModal(false)}
            />
        </>
    );
}
