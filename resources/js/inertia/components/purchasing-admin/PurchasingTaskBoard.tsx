import * as React from 'react';
import { createPortal } from 'react-dom';
import {
    DndContext,
    DragOverlay,
    closestCorners,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
    DragStartEvent,
    DragEndEvent,
    DragOverEvent,
    UniqueIdentifier,
    useDroppable,
} from '@dnd-kit/core';
import {
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    Clock,
    Building2,
    DollarSign,
    MoreHorizontal,
    User,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { showToast } from '@/components/ui/toast';
import { CompleteTaskModal } from './CompleteTaskModal';
import type { AdminTask } from './types';

// ============================================================================
// TYPES & CONFIGURATION
// ============================================================================

interface KanbanColumn {
    id: string;
    title: string;
    borderColor: string;
    headerBg: string;
    headerText: string;
    status: AdminTask['status'];
}

interface PurchasingTaskBoardProps {
    tasks: AdminTask[];
    onStatusChange?: (taskId: number, newStatus: string) => void;
    onTaskClick?: (task: AdminTask) => void;
    onClaim?: (taskId: number) => void;
    onStart?: (taskId: number) => void;
    onComplete?: (task: AdminTask) => void; // Called when task dropped to Complete column
}

// Column configuration
const columns: KanbanColumn[] = [
    {
        id: 'pending_followup',
        title: 'Pending',
        borderColor: 'border-t-slate-400',
        headerBg: 'bg-slate-100',
        headerText: 'text-slate-700',
        status: 'pending_followup',
    },
    {
        id: 'in_progress',
        title: 'In Progress',
        borderColor: 'border-t-amber-500',
        headerBg: 'bg-amber-50',
        headerText: 'text-amber-700',
        status: 'in_progress',
    },
    {
        id: 'done',
        title: 'Completed',
        borderColor: 'border-t-emerald-500',
        headerBg: 'bg-emerald-50',
        headerText: 'text-emerald-700',
        status: 'done',
    },
];

// Format currency
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

// Format date short
const formatDateShort = (dateString: string | null) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
    });
};

// ============================================================================
// DRAGGABLE TASK CARD
// ============================================================================

interface DraggableTaskCardProps {
    task: AdminTask;
    isDragging?: boolean;
    onTaskClick?: (task: AdminTask) => void;
}

function DraggableTaskCard({ task, isDragging, onTaskClick }: DraggableTaskCardProps) {
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
        } else {
            hasDragged.current = true;
        }
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
            {/* Type Badge */}
            <div className="mb-2 flex items-center justify-between">
                <span className={cn(
                    'inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold',
                    typeColor
                )}>
                    {isPR ? 'PR' : 'ST'}
                </span>
                {task.assigned_admin?.name && (
                    <span className="text-[10px] text-gray-500 flex items-center gap-1">
                        <User className="h-3 w-3" />
                        {task.assigned_admin.name.split(' ')[0]}
                    </span>
                )}
            </div>

            {/* Number */}
            <h4 className="text-sm font-semibold text-slate-900 leading-snug mb-2 line-clamp-1">
                {number || 'Loading...'}
            </h4>

            {/* Department */}
            <p className="text-xs text-slate-500 line-clamp-1 mb-2 flex items-center gap-1">
                <Building2 className="h-3 w-3" />
                {task.department?.name || 'Unknown'}
            </p>

            {/* Footer */}
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

// ============================================================================
// TASK CARD OVERLAY (for dragging)
// ============================================================================

function TaskCardOverlay({ task }: { task: AdminTask }) {
    const isPR = task.taskable_type?.includes('PurchaseRequest');
    const number = isPR ? task.taskable?.pr_number : task.taskable?.st_number;
    const typeColor = isPR ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700';

    return (
        <div className="bg-white p-3 rounded-lg border border-slate-200 shadow-xl ring-2 ring-primary/20 transform rotate-2 cursor-grabbing w-[280px]">
            <div className="mb-2">
                <span className={cn(
                    'inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold',
                    typeColor
                )}>
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

// ============================================================================
// BOARD COLUMN COMPONENT
// ============================================================================

interface BoardColumnProps {
    column: KanbanColumn;
    tasks: AdminTask[];
    onTaskClick?: (task: AdminTask) => void;
}

function BoardColumn({ column, tasks, onTaskClick }: BoardColumnProps) {
    const { setNodeRef, isOver } = useDroppable({
        id: column.id,
    });

    return (
        <div className={cn(
            'flex flex-col rounded-xl overflow-hidden min-h-[500px]',
            'bg-slate-50/80 border border-slate-200/60',
            'border-t-4',
            column.borderColor,
            isOver && 'ring-2 ring-blue-400 ring-offset-2 bg-blue-50'
        )}>
            {/* Column Header */}
            <div className={cn(
                'flex items-center justify-between px-4 py-3',
                column.headerBg
            )}>
                <div className="flex items-center gap-2">
                    <h3 className={cn('text-sm font-bold', column.headerText)}>
                        {column.title}
                    </h3>
                    <span className={cn(
                        'px-2 py-0.5 rounded-full text-xs font-semibold',
                        column.headerBg,
                        column.headerText,
                        'bg-white/60'
                    )}>
                        {tasks.length}
                    </span>
                </div>
                <button className="p-1 rounded hover:bg-white/50 text-slate-400 hover:text-slate-600 transition-colors">
                    <MoreHorizontal className="h-4 w-4" strokeWidth={1.5} />
                </button>
            </div>

            {/* Tasks Container */}
            <SortableContext items={tasks.map(t => t.id)} strategy={verticalListSortingStrategy}>
                <div
                    ref={setNodeRef}
                    className={cn(
                        'flex-1 flex flex-col gap-2 p-3 min-h-[200px] max-h-[calc(100vh-300px)] overflow-y-auto transition-colors',
                        isOver && 'bg-blue-50'
                    )}
                >
                    <AnimatePresence mode="popLayout">
                        {tasks.length === 0 ? (
                            <motion.div
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                className={cn(
                                    'flex flex-col items-center justify-center py-12 text-center rounded-lg border-2 border-dashed',
                                    isOver ? 'border-primary bg-primary/10' : 'border-transparent'
                                )}
                            >
                                <div className="h-10 w-10 rounded-full bg-slate-200/50 flex items-center justify-center mb-3">
                                    <div className="h-2 w-2 rounded-full bg-slate-300" />
                                </div>
                                <p className="text-slate-400 text-xs font-medium">No tasks</p>
                                <p className="text-slate-300 text-[10px] mt-1">Drag tasks here</p>
                            </motion.div>
                        ) : (
                            tasks.map((task) => (
                                <DraggableTaskCard
                                    key={task.id}
                                    task={task}
                                    onTaskClick={onTaskClick}
                                />
                            ))
                        )}
                    </AnimatePresence>
                </div>
            </SortableContext>
        </div>
    );
}

// ============================================================================
// MAIN KANBAN BOARD COMPONENT
// ============================================================================

export function PurchasingTaskBoard({
    tasks,
    onStatusChange,
    onTaskClick,
    onComplete,
}: PurchasingTaskBoardProps) {
    const [activeTask, setActiveTask] = React.useState<AdminTask | null>(null);
    const [localTasks, setLocalTasks] = React.useState<AdminTask[]>(tasks);
    const [pendingCompleteTask, setPendingCompleteTask] = React.useState<AdminTask | null>(null);

    React.useEffect(() => {
        setLocalTasks(tasks);
    }, [tasks]);

    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: { distance: 5 },
        }),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    // Group tasks by status
    const tasksByStatus = React.useMemo(() => {
        const grouped: Record<string, AdminTask[]> = {};
        columns.forEach(col => {
            grouped[col.id] = localTasks.filter(task => task.status === col.id);
        });
        return grouped;
    }, [localTasks]);

    const findColumnByTaskId = (taskId: UniqueIdentifier): string | null => {
        for (const column of columns) {
            if (tasksByStatus[column.id].some(t => t.id === taskId)) {
                return column.id;
            }
        }
        return null;
    };

    const handleDragStart = (event: DragStartEvent) => {
        // Store the task with its ORIGINAL status before any drag updates
        const task = tasks.find(t => t.id === event.active.id); // Use props.tasks, not localTasks
        if (task) setActiveTask({ ...task }); // Clone to preserve original status
    };

    const handleDragOver = (event: DragOverEvent) => {
        const { active, over } = event;
        if (!over) return;

        const activeColumn = findColumnByTaskId(active.id);
        const overColumn = columns.find(col => col.id === over.id)?.id || findColumnByTaskId(over.id);

        if (!activeColumn || !overColumn || activeColumn === overColumn) return;

        setLocalTasks(prev => prev.map(task =>
            task.id === active.id ? { ...task, status: overColumn as AdminTask['status'] } : task
        ));
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;
        const originalTask = activeTask; // This has the ORIGINAL status from props.tasks
        setActiveTask(null);

        if (!over || !originalTask) return;

        const activeId = active.id as number;
        const overColumn = columns.find(col => col.id === over.id)?.id || findColumnByTaskId(over.id);
        const originalStatus = originalTask.status;

        // Check if status actually changed (compare original with target)
        if (overColumn && originalStatus !== overColumn) {
            // If dropping to 'done' column, trigger complete modal
            if (overColumn === 'done') {
                // Revert local status until modal is completed
                setLocalTasks(prev => prev.map(t =>
                    t.id === activeId ? { ...originalTask } : t
                ));
                // Trigger complete modal with original task
                if (onComplete) {
                    onComplete(originalTask);
                } else {
                    setPendingCompleteTask(originalTask);
                }
            } else if (onStatusChange) {
                onStatusChange(activeId, overColumn);
            } else {
                // Default: call API to update status
                router.put(
                    route('purchasing.admin.tasks.update-status', { taskId: activeId }),
                    { status: overColumn },
                    {
                        preserveScroll: true,
                        onSuccess: () => {
                            showToast.success('Status updated', `Task moved to ${columns.find(c => c.id === overColumn)?.title}`);
                        },
                        onError: () => {
                            setLocalTasks(tasks);
                            showToast.error('Failed to update status');
                        },
                    }
                );
            }
        }
    };

    // Handler for when complete modal succeeds
    const handleCompleteSuccess = () => {
        setPendingCompleteTask(null);
        // Refresh data from props
        setLocalTasks(tasks);
    };

    return (
        <>
            <DndContext
                sensors={sensors}
                collisionDetection={closestCorners}
                onDragStart={handleDragStart}
                onDragOver={handleDragOver}
                onDragEnd={handleDragEnd}
            >
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pb-6 px-1">
                    {columns.map((column) => (
                        <BoardColumn
                            key={column.id}
                            column={column}
                            tasks={tasksByStatus[column.id] || []}
                            onTaskClick={onTaskClick}
                        />
                    ))}
                </div>

                {typeof window !== 'undefined' && createPortal(
                    <DragOverlay dropAnimation={{ duration: 200, easing: 'cubic-bezier(0.18, 0.67, 0.6, 1.22)' }}>
                        {activeTask ? <TaskCardOverlay task={activeTask} /> : null}
                    </DragOverlay>,
                    document.body
                )}
            </DndContext>

            {/* Complete Task Modal - shown when dragging task to Complete column */}
            <CompleteTaskModal
                task={pendingCompleteTask}
                open={!!pendingCompleteTask}
                onClose={() => setPendingCompleteTask(null)}
                onComplete={handleCompleteSuccess}
            />
        </>
    );
}

export default PurchasingTaskBoard;
