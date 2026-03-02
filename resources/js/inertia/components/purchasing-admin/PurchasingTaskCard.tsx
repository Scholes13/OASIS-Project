import { Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    Clock,
    Building2,
    DollarSign,
    User,
    FileText,
    Package,
    Shield,
    Eye,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import type { AdminTask } from './types';

interface PurchasingTaskCardProps {
    task: AdminTask;
    onTaskClick?: (task: AdminTask) => void;
    showActions?: boolean;
    onClaim?: (taskId: number) => void;
    onStart?: (taskId: number) => void;
}

// Status styling
const statusStyles: Record<string, { bg: string; text: string; border: string; label: string }> = {
    pending_followup: {
        bg: 'bg-slate-50',
        text: 'text-slate-700',
        border: 'border-slate-200',
        label: 'Pending'
    },
    in_progress: {
        bg: 'bg-amber-50',
        text: 'text-amber-700',
        border: 'border-amber-200',
        label: 'In Progress'
    },
    done: {
        bg: 'bg-emerald-50',
        text: 'text-emerald-700',
        border: 'border-emerald-200',
        label: 'Completed'
    },
};

// Format currency
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

// Format date
const formatDate = (dateString: string | null) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

export function PurchasingTaskCard({
    task,
    onTaskClick,
    showActions = true,
    onClaim,
    onStart,
}: PurchasingTaskCardProps) {
    const statusStyle = statusStyles[task.status] || statusStyles.pending_followup;
    const taskable = task.taskable;

    // Determine if it's a PR or ST
    const isPR = task.taskable_type?.includes('PurchaseRequest');
    const number = isPR ? taskable?.pr_number : taskable?.st_number;
    const typeLabel = isPR ? 'PR' : 'ST';
    const typeColor = isPR ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700';

    const handleClick = () => {
        if (onTaskClick) {
            onTaskClick(task);
        }
    };

    const canClaim = task.status === 'pending_followup' && !task.assigned_admin_id;
    const canStart = task.status === 'pending_followup' && task.assigned_admin_id;
    const isOfflineApproved = taskable?.offline_approved_at != null;

    return (
        <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            className={cn(
                "bg-white rounded-lg border shadow-sm hover:shadow-md transition-all cursor-pointer",
                statusStyle.border
            )}
            onClick={handleClick}
        >
            {/* Header */}
            <div className="p-4">
                <div className="flex items-start justify-between gap-2 mb-3">
                    <div className="flex items-center gap-2 flex-wrap">
                        <span className={cn(
                            "px-2 py-0.5 rounded text-xs font-semibold",
                            typeColor
                        )}>
                            {typeLabel}
                        </span>
                        <span className={cn(
                            "px-2 py-0.5 rounded text-xs font-medium",
                            statusStyle.bg,
                            statusStyle.text
                        )}>
                            {statusStyle.label}
                        </span>
                        {isOfflineApproved && (
                            <span className="px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700 flex items-center gap-1">
                                <Shield className="h-3 w-3" />
                                Offline
                            </span>
                        )}
                    </div>
                    {task.assigned_admin?.name && (
                        <div className="flex items-center gap-1 text-xs text-gray-500">
                            <User className="h-3 w-3" />
                            <span>{task.assigned_admin.name}</span>
                        </div>
                    )}
                </div>

                {/* PR/ST Number */}
                <h4 className="font-semibold text-gray-900 mb-2">
                    {number || 'Loading...'}
                </h4>

                {/* Department */}
                <div className="flex items-center gap-2 text-sm text-gray-600 mb-2">
                    <Building2 className="h-4 w-4 text-gray-400" />
                    <span>{task.department?.name || 'Unknown Department'}</span>
                </div>

                {/* Amount */}
                <div className="flex items-center gap-2 text-sm font-medium text-gray-900 mb-2">
                    <DollarSign className="h-4 w-4 text-gray-400" />
                    <span>{formatCurrency(task.estimated_total_price || 0)}</span>
                </div>

                {/* Date */}
                <div className="flex items-center gap-2 text-xs text-gray-500">
                    <Clock className="h-3.5 w-3.5" />
                    <span>{formatDate(task.entered_at)}</span>
                </div>

                {/* Offline Approval Info */}
                {isOfflineApproved && (
                    <div className="mt-3 pt-3 border-t border-purple-100 bg-purple-50 -mx-4 px-4 py-2 -mb-4 rounded-b-lg">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-1.5 text-xs text-purple-700">
                                <Shield className="h-3.5 w-3.5" />
                                <span className="font-medium">Offline Approved</span>
                            </div>
                            {taskable?.offline_approval_document_path && (
                                <a
                                    href={`/storage/${taskable.offline_approval_document_path}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    onClick={(e) => e.stopPropagation()}
                                    className="flex items-center gap-1 px-2 py-1 text-xs font-medium text-purple-700 bg-purple-100 rounded hover:bg-purple-200 transition-colors"
                                >
                                    <Eye className="h-3 w-3" />
                                    View Doc
                                </a>
                            )}
                        </div>
                        <p className="text-xs text-purple-600 mt-1">
                            {taskable?.offline_approved_at && new Date(taskable.offline_approved_at).toLocaleDateString('en-GB', {
                                day: 'numeric',
                                month: 'short',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            })}
                        </p>
                    </div>
                )}
            </div>

            {/* Actions */}
            {showActions && (
                <div className="px-4 py-3 bg-gray-50 border-t border-gray-100 flex gap-2">
                    {canClaim && onClaim && (
                        <button
                            onClick={(e) => {
                                e.stopPropagation();
                                onClaim(task.id);
                            }}
                            className="flex-1 px-3 py-1.5 text-xs font-medium text-primary bg-primary rounded hover:bg-blue-600 transition-colors"
                        >
                            Claim Task
                        </button>
                    )}
                    {canStart && onStart && (
                        <button
                            onClick={(e) => {
                                e.stopPropagation();
                                onStart(task.id);
                            }}
                            className="flex-1 px-3 py-1.5 text-xs font-medium text-amber-600 bg-amber-50 rounded hover:bg-amber-100 transition-colors"
                        >
                            Start Task
                        </button>
                    )}
                    <Link
                        href={route('purchasing.admin.tasks.show', { taskId: task.id })}
                        onClick={(e) => e.stopPropagation()}
                        className="flex-1 px-3 py-1.5 text-xs font-medium text-center text-gray-600 bg-white border border-gray-200 rounded hover:bg-gray-50 transition-colors"
                    >
                        View Details
                    </Link>
                </div>
            )}
        </motion.div>
    );
}

export default PurchasingTaskCard;
