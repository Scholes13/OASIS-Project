import * as React from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { motion } from 'framer-motion';
import { Building2, Clock, User } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { AdminTask } from '../types';

interface TaskBoardCardProps {
    task: AdminTask;
    isDragging?: boolean;
    onTaskClick?: (task: AdminTask) => void;
}

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

const formatDateShort = (dateString: string | null) => {
    if (!dateString) return '-';

    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
    });
};

export function TaskBoardCard({ task, isDragging, onTaskClick }: TaskBoardCardProps) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging: isSortableDragging,
    } = useSortable({ id: task.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    const isPR = task.taskable_type?.includes('PurchaseRequest');
    const number = isPR ? task.taskable?.pr_number : task.taskable?.st_number;
    const typeColor = isPR ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700';
    const hasDragged = React.useRef(false);

    React.useEffect(() => {
        if (!isDragging) {
            const timeout = setTimeout(() => {
                hasDragged.current = false;
            }, 50);

            return () => clearTimeout(timeout);
        }

        hasDragged.current = true;
    }, [isDragging]);

    const handleClick = (e: React.MouseEvent) => {
        if (hasDragged.current || isSortableDragging || isDragging) {
            e.preventDefault();
            return;
        }

        onTaskClick?.(task);
    };

    return (
        <motion.div
            ref={setNodeRef}
            style={style}
            layoutId={`board-task-${task.id}`}
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
            className={cn(
                'group bg-white p-3 rounded-lg border border-slate-200 shadow-sm cursor-pointer transition-all duration-200',
                'hover:shadow-md hover:border-slate-300',
                isSortableDragging && 'opacity-50 shadow-none',
                isDragging && 'ring-2 ring-primary ring-offset-2 z-50'
            )}
            {...attributes}
            {...listeners}
            onClick={handleClick}
        >
            <div className="mb-2 flex items-center justify-between">
                <span
                    className={cn(
                        'inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold',
                        typeColor
                    )}
                >
                    {isPR ? 'PR' : 'ST'}
                </span>
                {task.assigned_admin?.name && (
                    <span className="text-[10px] text-gray-500 flex items-center gap-1">
                        <User className="h-3 w-3" />
                        {task.assigned_admin.name.split(' ')[0]}
                    </span>
                )}
            </div>

            <h4 className="text-sm font-semibold text-slate-900 leading-snug mb-2 line-clamp-1">
                {number || 'Loading...'}
            </h4>

            <p className="text-xs text-slate-500 line-clamp-1 mb-2 flex items-center gap-1">
                <Building2 className="h-3 w-3" />
                {task.department?.name || 'Unknown'}
            </p>

            <div className="flex items-center justify-between pt-2 border-t border-slate-100">
                <div className="flex items-center gap-2 text-[11px] text-slate-500">
                    <Clock className="h-3 w-3" strokeWidth={2} />
                    <span>{formatDateShort(task.entered_at)}</span>
                </div>
                <span className="text-[11px] font-medium text-slate-700">
                    {formatCurrency(task.estimated_total_price || 0)}
                </span>
            </div>
        </motion.div>
    );
}

export function TaskCardOverlay({ task }: { task: AdminTask }) {
    const isPR = task.taskable_type?.includes('PurchaseRequest');
    const number = isPR ? task.taskable?.pr_number : task.taskable?.st_number;
    const typeColor = isPR ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700';

    return (
        <div className="bg-white p-3 rounded-lg border border-slate-200 shadow-xl ring-2 ring-primary/20 transform rotate-2 cursor-grabbing w-[280px]">
            <div className="mb-2">
                <span
                    className={cn(
                        'inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold',
                        typeColor
                    )}
                >
                    {isPR ? 'PR' : 'ST'}
                </span>
            </div>
            <h4 className="text-sm font-semibold text-slate-900 leading-snug mb-2">
                {number || 'Loading...'}
            </h4>
            <div className="flex items-center justify-between pt-2 border-t border-slate-100">
                <div className="flex items-center gap-2 text-[11px] text-slate-500">
                    <Clock className="h-3 w-3" strokeWidth={2} />
                    <span>{formatDateShort(task.entered_at)}</span>
                </div>
                <span className="text-[11px] font-medium text-slate-700">
                    {formatCurrency(task.estimated_total_price || 0)}
                </span>
            </div>
        </div>
    );
}
